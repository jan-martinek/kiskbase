<?php

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Form;
use Model\Entity\Entry;
use Model\Entity\Answer;
use Model\Entity\Question;
use Model\Entity\Tag;
use Model\Entity\Checklist;
use Model\Entity\EditorHistory;
use DateTime;

class EntryPresenter extends BasePresenter
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

    /** @var \Model\Repository\ChecklistRepository @inject */
    public $checklistRepository;
    
    /** @var \Model\Repository\EditorHistoryRepository @inject */
    public $editorHistoryRepository;

    private $acceptedDomains;

    public function renderDefault($id)
    {
        if (!$entry = $this->entryRepository->find($id)) {
            throw new BadRequestException();
        }
        $this->template->entry = $entry;
        $this->template->related = $this->entryRepository->lookupRelated($entry);
    }

    public function renderEdit($id)
    {
        if (!$entry = $this->entryRepository->find($id)) {
            throw new BadRequestException();
        }
        $this->template->allTags = $this->tagRepository->findAll();
        $this->template->entry = $entry;
    }

    public function renderParams($id)
    {
        if (!$entry = $this->entryRepository->find($id)) {
            throw new BadRequestException();
        }
        $this->template->entry = $entry;
    }

    public function renderNew()
    {
    }
    
    public function renderPrint() {
        $this->template->entries = $this->entryRepository->findAll();
    }

    public function handleCreateNew($question)
    {
        $httpRequest = $this->context->getByType('Nette\Http\Request');
        $questionText = $httpRequest->isAjax() ? trim($httpRequest->getPost('question')) : $question;
        $user = $this->userRepository->find($this->getUser()->getId());

        $entry = new Entry();
        $entry->editor = $user;
        $this->entryRepository->persist($entry);

        $question = new Question();
        $question->entry = $entry;
        $question->authoredBy = $user;
        $question->created_at = new DateTime();
        $question->text = $questionText;
        $this->questionRepository->persist($question);

        $answer = new Answer();
        $answer->entry = $entry;
        $answer->authoredBy = $user;
        $answer->created_at = new DateTime();
        $answer->text = '';
        $this->answerRepository->persist($answer);

        $entry->answer = $answer;
        $entry->question = $question;
        $this->entryRepository->persist($entry);

        if ($httpRequest->isAjax()) {
            $this->sendResponse(new JsonResponse(array('redirect' => $this->link('Entry:edit', $entry->id))));
        } else {
            $this->redirect('Entry:edit', $entry->id);
        }
    }

    public function handleSave($id)
    {
        $httpRequest = $this->context->getByType('Nette\Http\Request');

        $entry = $this->entryRepository->find($id);
        $user = $this->userRepository->find($this->getUser()->getId());

        $question = new Question();
        $question->entry = $entry;
        $question->authoredBy = $user;
        $question->created_at = new DateTime();
        $question->text = trim($httpRequest->getPost('question'));
        $this->questionRepository->persist($question);

        $answer = new Answer();
        $answer->entry = $entry;
        $answer->authoredBy = $user;
        $answer->created_at = new DateTime();
        $answer->text = trim($httpRequest->getPost('answer'));
        $this->answerRepository->persist($answer);

        $entry->answer = $answer;
        $entry->question = $question;
        $entry->namespace = $question->extractNamespace();
        $this->entryRepository->persist($entry);

        preg_match_all('/#([\p{L}-]+)/u', $answer->text, $tags);
        $this->saveTags($entry, $tags[1]);

        if ($httpRequest->isAjax()) {
            $this->sendResponse(new JsonResponse(array('success' => true)));
        }
    }
    
    protected function createComponentParamsForm()
    {
        $users = $this->userRepository->findAllSimple();
        
        $form = new Form();
        $form->addSelect('editor', 
            $this->translator->translate('messages.kb.params.editor'), $users);
        $form->addSubmit('submit', $this->translator->translate('messages.kb.params.save'));
        $form->addHidden('entry_id');
        $form->onSuccess[] = array($this, 'paramsFormSucceeded');
        return $form;
    }
    
    public function paramsFormSucceeded(Form $form, $values)
    {
        $entry = $this->entryRepository->find($values->entry_id);
        
        if ($entry->editor->id != $values->editor) {
            $editor = $this->userRepository->find($values->editor);
            $entry->editor = $editor;
            $this->entryRepository->persist($entry);
            
            $editorHistory = new EditorHistory();
            $editorHistory->entry = $entry;
            $editorHistory->editor = $editor;
            $editorHistory->assignedBy = $this->userRepository->find($this->user->getId());
            $editorHistory->date = new DateTime;
            $this->editorHistoryRepository->persist($editorHistory);
        }
    }
    
    public function handleImageUpload($id) {
        $httpRequest = $this->context->getByType('Nette\Http\Request');
        
        $uploadDir = __DIR__ . '/../../www/upload/KB' . $id . '/';
        $uploadUrl = $httpRequest->url->baseUrl . '/upload/KB' . $id . '/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777);
        }
        
        $uploadHandler = new \Model\UploadHandler(array(
            'upload_dir' => $uploadDir,
            'upload_url' => $uploadUrl,
        ));
        
        $this->terminate();
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
                    $tag = new Tag();
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
    
    public function handleCreateChecklist($id) {
        $httpRequest = $this->context->getByType('Nette\Http\Request');
        $name = $httpRequest->getPost('name');
        $data = $httpRequest->getPost('data');

        $checklist = new Checklist;
        $checklist->owner = $this->userRepository->find($this->user->getId());
        $checklist->source = $this->entryRepository->find($id);
        $checklist->created_at = new DateTime;
        $checklist->updated_at = new DateTime;
        $checklist->name = $name;
        $checklist->text = $data;
        $checklistId = $this->checklistRepository->persist($checklist);
        
        $this->redirect('Checklist:default', $checklistId);
    }
}
