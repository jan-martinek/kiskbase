<?php

namespace App\Presenters;

use Nette\Application\Responses\JsonResponse;

/**
 * Homepage presenter.
 */
class ApiPresenter extends BasePresenter {
	
	/** @var \Model\Repository\TagRepository @inject */
	public $tagRepository;

	public function actionDefault() {

	}

	public function actionSql($query) {

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
		$people = array(
			"Petr Škyřík",
			"Alžběta Strnadová",
			"Kateřina Blatná",
			"Markéta Bočková",
			"Tomáš Bouda",
			"Michal Černý",
			"Hana Habermannová",
			"Adam Hazdra",
			"Kateřina Hošková",
			"Dagmar Chytková",
			"Alžběta Karolyiová",
			"Michaela Kortyšová",
			"Pavla Kovářová",
			"Michal Lorenz",
			"Jan Martinek",
			"Pavlína Mazáčová",
			"Pavla Minaříková",
			"Jiří Stodola",
			"Tereza Stodolová",
			"Gabriela Šimková",
			"Alexandra Škyříková",
			"Alžběta Škytová",
			"Iva Zadražilová",
			"Ladislava Zbiejczuk Suchá",
			"Jiří Zeman",
			"Jan Zikuška",
		);

		$this->sendResponse(new JsonResponse($people));
	}

}
