"use strict";

var KiskBase = {
	baseUri: '',
	
	init: function() {
		$.nette.init();
		
		this.ThirdParty.init();
		this.Editor.init();
		this.Editor.autoSave();
		this.TableManager.init();
		this.ChecklistManager.init();
	},
	
	ThirdParty: {
		init: function() {
			$(document).foundation();
			
			$('code').each(function(i, block) {
    	        hljs.highlightBlock(block);
	        });
        
        	$(".tablesorter").tablesorter(); 
		}	
	},

	Editor: {
		init: function() {
			var editor = this;

			var mediumEditor = new MediumEditor('#answer[contenteditable]', {
				buttons: ['bold', 'italic', 'anchor', 'quote', 'header1', 'header2', 'unorderedlist', 'orderedlist', 'outdent', 'indent'],
				firstHeader: 'h2',
				secondHeader: 'h3',
				buttonLabels: 'fontawesome'
			});	
			
			
			$('#answer[contenteditable]').mediumInsert({
				editor: mediumEditor,
				addons: { 
        			images: { 
        				preview: false,
						fileUploadOptions: {
							url: window.location.pathname + '?do=imageUpload',
						},
					},
				},
			});
			

			$('#answer').atwho({
			    at: "@",
			    data: KiskBase.baseUri + '/api/people',
			    insertTpl: '${atwho-at}${name}'
			}).atwho({
			    at: "#",
			    data: KiskBase.baseUri + '/api/tags',
			    insertTpl: '${atwho-at}${name}'
			}).atwho({
			    at: "KB",
			    data: KiskBase.baseUri + '/api/questions',
			    displayTpl: "<li>KB ${id} <strong>${question}</strong></li>",
			    insertTpl: '${question}'
			});	

			editor.renderButton('success');
			$('body').on('input', '[contenteditable]', function() {
				editor.renderButton('unsavedChanges');
			});
			$('body').on('change', 'select', function() {
				editor.renderButton('unsavedChanges');
			});

			$('body').on('click', '#editor-save', function(e) {
				editor.save();
				e.preventDefault();
			});
		},

		renderButton: function(state) {
			var button = $('#editor-save');
			var stdClasses = 'button small';
			var additionalClasses = '';
			var text = '';
			var icon = '';

			switch (state) {
				case 'edit':
					icon = '<i class="fa fa-pencil-square-o"></i>';
					text = KiskBase.translations.edit;
					break;
				case 'inProgress':
					icon = '<i class="fa fa-spinner"></i>';
					additionalClasses = 'disabled';
					text = KiskBase.translations.saving;
					break;
				case 'success':	
					additionalClasses = 'success';
					icon = '<i class="fa fa-check"></i>';
					text = KiskBase.translations.saved;
					break;
				case 'unsavedChanges':
					icon = '<i class="fa fa-floppy-o"></i>';
					text = KiskBase.translations.save;
					break;
				case 'problem': // needs more care
					additionalClasses = 'alert';
					icon = '<i class="fa fa-exclamation-triangle"></i>';
					text = KiskBase.translations.error;
					break;
				case 'hidden':
					break;
			}

			if (state === 'hidden') {
				button.hide();
			} else {
				button.attr('class', stdClasses + ' ' + additionalClasses);
				button.data('state', state);
				button.html(icon + ' ' + text);
				button.show();
			}
		},

		save: function() {
			var editor = this;

			var answer = $('#answer').clone();

			$.nette.ajax({
				method: 'POST',
				cache: false,
				url: $('#editor-save').attr('href'),
				data: {
					answer: answer.html(),
					question: $('#question').text(),
					tags: $('#tags').val()
				}
			}).always(function() {
				editor.renderButton('inProgress');
			}).done(function(response) {
				editor.renderButton('success');
			}).fail(function() {
				editor.renderButton('problem');
			});
		},

		autoSave: function() {
			KiskBase.Editor.save();
			setTimeout(function(){ KiskBase.Editor.autoSave(); }, 20*1000);
		}
	},
	
	TableManager: {
		init: function() {
			$(document).on('blur', 'table.livedata td[contenteditable=true]', function() {
				KiskBase.TableManager.saveCellData($(this));
			});
		},
		
		saveCellData: function(cell) {
			var table = cell.closest('table').data('table');
			var column = cell.data('column');
			var id = cell.closest('tr').data('id');
			var data = cell.text();
			
			$.nette.ajax({
				method: 'POST',
				cache: false,
				url: window.location.pathname + '?do=saveData',
				data: {
					table: table,
					column: column,
					id: id,
					data: data
				}
			}).always(function() {
				cell.css({background: "rgba(255, 255, 0, .3)"});
			}).done(function(response) {
				setTimeout(function(){ cell.css({background: "rgba(0, 255, 0, .1)"}); }, 400);
				setTimeout(function(){ cell.css({background: "transparent"}); }, 900);
			}).fail(function() {
				cell.css({background: "rgba(255, 0, 0, .3)"});
			});
		}
	},
	
	ChecklistManager: {
		creationHandlerUri: '',
		state: [],
		
		init: function() {
			this.createLinks();
			this.initChecklist();
		},
		
		createLinks: function() {
			if ($('#answer').attr('contenteditable')) {
				return false;
			}
			
			$('#answer>ul,#answer>ol').before('<a href="#" class="createChecklist button secondary right">' + KiskBase.translations.createChecklist + ' &rarr;</a>');
			
			$('body').on('click', '.createChecklist', function(e) {
				var name = prompt(KiskBase.translations.nameChecklist);
				if (name) {
					KiskBase.ChecklistManager.sendCreateRequest($(this).next().html(), name);	
				}
				e.preventDefault();
			});
		},
		
		sendCreateRequest: function(data, name) {
			var form = $('<form action="' + this.creationHandlerUri + '" method="post">');
			$('<input type="hidden" name="data">').attr({value: data}).appendTo(form);
			$('<input type="hidden" name="name">').attr({value: name}).appendTo(form);
			form.submit();
		},
		
		initChecklist: function() {
			if ($('ul.checklist').data('state')) {
				this.state = $('ul.checklist').data('state').split(',');	
			}

			$('ul.checklist li').each(function(i, e) {
				$(this).html($('<label></label>').append('<input type="checkbox" id="checkbox'+i+'"> ').append($(this).html()));
				
				if (KiskBase.ChecklistManager.state[i] == 1) { 
					$(this).find('input').prop('checked', true); 
				}				
			});
			
			$('body').on('change', 'ul.checklist input', function() {
				KiskBase.ChecklistManager.saveState();
			});
		},
		
		saveState: function() {
			var state = {};
			$('ul.checklist input[type="checkbox"]').each(function(i, e) {
				state[i] = $(this).is(':checked');
			});
			
			$.nette.ajax({
				method: 'POST',
				cache: false,
				url: window.location.pathname + '?do=saveState',
				data: {
					state: state
				}
			}).always(function() {
				//cell.css({background: "rgba(255, 255, 0, .3)"});
			}).done(function(response) {
				//setTimeout(function(){ cell.css({background: "rgba(0, 255, 0, .1)"}); }, 400);
				//setTimeout(function(){ cell.css({background: "transparent"}); }, 900);
			}).fail(function() {
				//cell.css({background: "rgba(255, 0, 0, .3)"});
			});
		}
	}
}

$(document).ready(function() {
	KiskBase.init();
});
