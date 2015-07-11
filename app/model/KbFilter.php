<?php

namespace App\Model;

use Nette\Utils\Html;
use Tracy\Debugger;

class KbFilter extends \Nette\Object {
	private $queries;
	private $text;
	private $questionStartsWith = 'Jak|Co|Na koho|Koho|Kdo|Kde|S jakým|Kam|Z čeho';

	/** @var \DibiConnection @inject */
	public $db;

	/** @var \Kdyby\Translation\Translator @inject */
	public $translator;

	private $presenter;

	public function __construct( \DibiConnection $db, \Kdyby\Translation\Translator $translator) {	
		$this->db = $db;
		$this->translator = $translator;
	}

	public function process($text, $presenter) {
		$this->text = $text;
		$this->presenter = $presenter;

		$this->markSql();
		$this->insertResults();
		$this->activateSql();
		$this->activateQuestions();
		$this->activateHashtags();

		$el = Html::el();
		$el->setHtml($this->text);
		return $el;
	}

	public function activateQuestions() {
		$questions = $this->db->query('SELECT [entry_id], [text] FROM [question]')->fetchPairs('entry_id', 'text');

		$replacements = array();
		preg_match_all('/((' . $this->questionStartsWith . ')[^?]+\?)/', $this->text, $matches);
		foreach ($matches[0] as $question) {
			if (in_array($question, $questions)) {
				$link = $this->presenter->link('default', array_search($question, $questions));
				$class = '';
			} else {
				$link = $this->presenter->link('createNew!', $question);
				$class = 'nonexistent';
			}
			$replacements[$question] = '<a href="' . $link . '" class="' . $class . '">' . $question . '</a>';
		}

		$this->text = strtr($this->text, $replacements);
	}

	public function activateSql() { 
		$replacements = array();
		preg_match_all('#\(select.+?;\)#i', $this->text, $matches);

		foreach ($matches[0] as $i => $query) {
			$queryNo = $i + 1;
						
			$replacements[$query] = '<strong>[' . $this->translator->translate('messages.sqlRender.seeQueryNo', NULL, array('queryNo' => $queryNo)) . ']</strong>';
		}

		$this->text = strtr($this->text, $replacements);
	}

	public function activateHashtags() {
		$replacements = array();
		preg_match_all('/#([\p{L}-]+)/u', $this->text, $matches);

		foreach ($matches[1] as $tag) {
			$replacements['#' . $tag] = '<a class="hashtag" href="' . $this->presenter->link('Search:tag', $tag) . '">#' . $tag . '</a>';
		}

		$this->text = strtr($this->text, $replacements);
	}

	public function insertResults() {
		if (!$this->queries) {
			return false;
		}

		foreach ($this->queries as $i => $query) {
			$queryNo = $i + 1;
			$code = '<h4>' . $this->translator->translate('messages.sqlRender.query') . '</h4>'
				. '<pre style="margin-bottom: 2em"><code class="sql">' . $query->sql . '</code></pre>';
			$headline = $this->translator->translate('messages.sqlRender.queryNo', NULL, array('queryNo' => $queryNo));
			$tables = '';
			
			if ($query->result instanceof \Exception) {
				$output = '<div class="panel"><p class="alert-box alert">' 
				. $this->translator->translate('messages.sqlRender.error', NULL, array('sql' => '<b>' . $query->sql . '</b>')) 
				. '</p><p>'
				. $this->translator->translate('messages.sqlRender.errorMessage') . ':<br> '
				. $query->result->getMessage() . '</p></div>';
			} else {
				$tables = '<h4>' . $this->translator->translate('messages.sqlRender.usedTables') . '</h4>' 
					. '<p>' . implode(', ', $this->getTablesUsedInQuery($query->sql)) . '</p>';
				$output = $query->result;
			}
			
			
			$output = '<div class="sqlRender queryNo' . $queryNo . '">' 
				. '<span class="label info">' . $headline . '</span>'
				. ' <span class="label secondary" onclick="$(this).next().toggle(); return false;"><a>' . $this->translator->translate('messages.sqlRender.showQueryDetails') . '</a></span>' 
				. '<div class="panel queryDetails" style="display: none">'
					. $code 
					. $tables
				. '</div>'
				. $output 
			. '</div>';
			$this->text = substr($this->text, 0, -$query->precedingElFromEos) . $output . substr($this->text, -$query->precedingElFromEos);
		}

		return true;
	}

	public function getTablesUsedInQuery($query) {
		$tables = array();
		$explanation = $this->db->query('explain ' . $query)->fetchAll();
	
		foreach ($explanation as $row) {
			$tables[$row->table] = '<a href="' . $this->presenter->link('Table:table', $row->table) . '">' . $row->table . '</a>';	
		}	
		
		sort($tables);
		return $tables;	
	}

	public function markSql() {
		$needle = "(select ";
		$lastPos = 0;
		$positions = array();

		while (($lastPos = strpos($this->text, $needle, $lastPos)) !== false) {
			$positions[] = $lastPos;
			$lastPos = $lastPos + strlen($needle);
		}

		foreach ($positions as $position) {
			preg_match('#\((select.+?;)\)#i', substr($this->text, $position), $matches);

			if (isset($matches[1])) {
				$sql = strip_tags($matches[1]);
				try {
					$result = $this->renderTable($this->db->query($sql)->fetchAll());
				} catch (\Exception $e) {
					$result = $e;
				}

				// preceding block element's opening tag lookup
				if (preg_match('#^([\s\S]+)<(p|ul|ol|blockquote)#', substr($this->text, 0, $position), $matches)) {
					$precedingElStartsAt = strlen($matches[1]);
				} else {
					$precedingElStartsAt = 0;
				}

				$this->queries[] = (object) array(
					'sql' => $sql,
					'positionFromEos' => strlen($this->text) - $position,
					'precedingElFromEos' => strlen($this->text) - $precedingElStartsAt,
					'result' => $result,
				);
			}
		}
		return true;
	}

	private function renderTable(array $results) {
		$table = Html::el('table');
		foreach ($results as $n => $row) {
			if ($n == 0) {
				$tr = Html::el('tr');
				foreach ($row as $key => $val) {
					if ($key !== 'id') $tr->add(Html::el('th')->setHtml($key));
				}
				$table->add($tr);
			}
			$tr = Html::el('tr');
			foreach ($row as $key => $val) {
				if ($key !== 'id') $tr->add(Html::el('td')->setHtml($val));
			}
			$table->add($tr);
		}
		return $table->render();
	}
}
