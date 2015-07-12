<?php

namespace App\Presenters;

use Nette\Application\Responses\JsonResponse;

/**
 * Homepage presenter.
 */
class ApiPresenter extends BasePresenter {

	/** @var \Model\TableManager @inject */
	public $tableManager;

	
	/** @var \Model\Repository\TagRepository @inject */
	public $tagRepository;

	public function actionDefault() {

	}

	public function actionTable($table) {
		$data = $this->tableManager->getData($table, 'id');
		//$this->sendResponse(new JsonResponse(array('sfd','sd')));
		$this->sendResponse(new JsonResponse($data));	
	}

	public function actionTags() {
		$tags = array();
		$tagResult = $this->tagRepository->findAll();
		foreach ($tagResult as $tag) {
			$tags[] = $tag->text;
		}

		$this->sendResponse(new JsonResponse($tags));
	}

	public function actionPeople() {
		$data = $this->tableManager->getData('person', 'name');
		$people = array_keys($data);
		$this->sendResponse(new JsonResponse($people));
	}

}
