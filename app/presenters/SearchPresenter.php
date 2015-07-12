<?php

namespace App\Presenters;

/**
 * Homepage presenter.
 */
class SearchPresenter extends BasePresenter
{
    /** @var \Model\Repository\EntryRepository @inject */
    public $entryRepository;

    /** @var \Model\Repository\TagRepository @inject */
    public $tagRepository;

    public function renderDefault($query)
    {
        $this->template->query = $query;

        $this->template->entries = $this->entryRepository->lookup($query);

        $this->template->tags = $this->tagRepository->lookup($query);
    }

    public function renderTag($tagText)
    {
        $this->template->tag = $tag = $this->tagRepository->findByText($tagText);

        $this->template->entries = $tag->entries;
    }
}
