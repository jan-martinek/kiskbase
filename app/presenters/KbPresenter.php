<?php

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Form;

use Model\Entity\Entry;
use Model\Entity\Answer;
use Model\Entity\Question;
use Model\Entity\Tag;

use DateTime;

class KbPresenter extends BasePresenter 
{
	
	/** @var \Model\Repository\EntryRepository @inject */
	public $entryRepository;

	/** @var \Model\Repository\AnswerRepository @inject */
	public $answerRepository;

	/** @var \Model\Repository\QuestionRepository @inject */
	public $questionRepository;

	/** @var \Model\Repository\TagRepository @inject */
	public $tagRepository;
	
	/** @var \Model\Repository\UserRepository @inject */
	public $userRepository;	
	
	private $acceptedDomains;

	public function renderDefault($id) 
	{
		if (!$entry = $this->entryRepository->find($id)) {
			throw new BadRequestException;
		}
		$this->template->entry = $entry;
		$this->template->related = $this->entryRepository->lookupRelated($entry);
	}

	public function renderEdit($id) 
	{
		if (!$entry = $this->entryRepository->find($id)) {
			throw new BadRequestException;
		}
		$this->template->allTags = $this->tagRepository->findAll();
		$this->template->entry = $entry;
	}

	public function renderNew() 
	{

	}

	public function handleCreateNew($question) 
	{
		$httpRequest = $this->context->getByType('Nette\Http\Request');		
		$questionText = $httpRequest->isAjax() ? trim($httpRequest->getPost('question')) : $question;			
		$user = $this->userRepository->find($this->getUser()->getId());
		
		$entry = new Entry;
		$entry->editor = $user;
		$this->entryRepository->persist($entry);
		
		$question = new Question;
		$question->entry = $entry;
		$question->authoredBy = $user;
		$question->created_at = new DateTime;
		$question->text = $questionText;
		$this->questionRepository->persist($question);	
		
		$answer = new Answer;
		$answer->entry = $entry;
		$answer->authoredBy = $user;
		$answer->created_at = new DateTime;
		$answer->text = '';
		$this->answerRepository->persist($answer);
		
		$entry->answer = $answer;
		$entry->question = $question;
		$this->entryRepository->persist($entry);
		
		if ($httpRequest->isAjax()) {
			$this->sendResponse(new JsonResponse(array('redirect' => $this->link('Kb:edit', $entry->id))));
		} else {
			$this->redirect('Kb:edit', $entry->id);
		}
	}

	public function handleSave($id) 
	{
		$httpRequest = $this->context->getByType('Nette\Http\Request');		

		$entry = $this->entryRepository->find($id);
		$user = $this->userRepository->find($this->getUser()->getId());

		$question = new Question;
		$question->entry = $entry;
		$question->authoredBy = $user;
		$question->created_at = new DateTime;
		$question->text = trim($httpRequest->getPost('question'));
		$this->questionRepository->persist($question);
		
		$answer = new Answer;
		$answer->entry = $entry;
		$answer->authoredBy = $user;
		$answer->created_at = new DateTime;
		$answer->text = trim($httpRequest->getPost('answer'));
		$this->answerRepository->persist($answer);
		
		$entry->answer = $answer;
		$entry->question = $question;
		$this->entryRepository->persist($entry);
		
		preg_match_all('/#(\w*[A-Za-z_]+\w*)/', strip_tags($answer->text), $tags);
		$this->saveTags($entry, $tags[1]);
		
		if ($httpRequest->isAjax()) {
			$this->sendResponse(new JsonResponse(array('success' => true)));
		}
	}

	private function saveTags($entry, $tagNames) 
	{
		if (!is_array($tagNames)) {
			return true;
		}
			
		$oldTags = $entry->tags;
		
		foreach ($tagNames as $tagName) {
			if (in_array($tagName, $oldTags)) {
				unset($oldTags[array_search($tagName, $oldTags)]);
			} else {
			 	if (!$tag = $this->tagRepository->findByText($tagName)) {
					$tag = new Tag;
					$tag->text = $tagName;
					$this->tagRepository->persist($tag);
				}
				$entry->addToTags($tag);
			}
		}
		foreach ($oldTags as $tag) {
			$entry->removeFromTags($tag);
		}
		$this->entryRepository->persist($entry);
		$this->tagRepository->purge();
	}

	protected function createComponentAnswerForm() 
	{
		$form = new Form;
		$form->addTextarea('answer', 'Odpověď');
		$form->addHidden('entry_id');
		$form->addSubmit('save', 'Uložit');
		$form->onSuccess[] = array($this, 'registrationFormSucceeded');
		return $form;
	}

	public function registrationFormSucceeded(Form $form, $values) 
	{
		$this->flashMessage('Informace byly uloženy.');
		$this->redirect('this');
	}
	
	public function setAcceptedDomains($domains) 
	{
		$this->acceptedDomains = $domains;
	}
}
