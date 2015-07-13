<?php

namespace App\Presenters;

class ChecklistPresenter extends BasePresenter
{
    /** @var \Model\Repository\ChecklistRepository @inject */
    public $checklistRepository;

    public function renderDefault($id)
    {
    	$this->template->checklist = $this->checklistRepository->find($id);
    }
    
    public function handleSaveState($id) 
    {
        $httpRequest = $this->context->getByType('Nette\Http\Request');
        $state = $httpRequest->getPost('state');

        $checklist = $this->checklistRepository->find($id);
        $checklist->state = serialize($state);
        $this->checklistRepository->persist($checklist);
        
        $this->terminate();
    }
}
