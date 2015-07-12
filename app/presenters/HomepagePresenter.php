<?php

namespace App\Presenters;

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{
    /** @var \Model\Repository\EntryRepository @inject */
    public $entryRepository;

    /** @var \Model\Repository\TagRepository @inject */
    public $tagRepository;

    /** @var \Model\TableManager @inject */
    public $tableManager;

    public function renderDefault()
    {
        $this->template->entries = $this->entryRepository->findAll();
        $this->template->tags = $this->tagRepository->findAll('text');
        $this->template->tables = $this->tableManager->findAllTables();
    }
}
