<?php

namespace App\Presenters;

use Model\Repository\GoogleUserRepository;
use Nette\Application\UI\Form;

class LoginPresenter extends BasePresenter {

	/** @var \Kdyby\Google\Google @inject */
	public $google;

	/** @var GoogleUserRepository @inject */
	public $userRepository;
	
	public function handleFakeLogin() {
		if ($this->context->parameters['debugMode']) {
			$this->user->login(new \Nette\Security\Identity(1, array(), array(
				'id' => 1,
				'roles' => array(),
				'name' => 'FakeLogin'))
			);
			$this->redirect('Homepage:default');
		}
	}

	/** @return \Kdyby\Google\Dialog\LoginDialog */
	protected function createComponentGoogleLogin() {
		$dialog = new \Kdyby\Google\Dialog\LoginDialog($this->google);
		$self = $this;
		$dialog->onResponse[] = function (\Kdyby\Google\Dialog\LoginDialog $dialog) use ($self) {
			$google = $dialog->getGoogle();

			if (!$google->getUser()) {
				$self->flashMessage("Sorry bro, google authentication failed.");
				return;
			}

			try {
				$me = $google->getProfile();

				if (!$existing = $self->userRepository->findByGoogleId($google->getUser())) {
					/**
					 * Variable $me contains all the public information about the user
					 * including Google id, name and email, if he allowed you to see it.
					 */
					$existing = $self->userRepository->registerFromGoogle($google->getUser(), $me);
				}

				/**
				 * You should save the access token to database for later usage.
				 *
				 * You will need it when you'll want to call Google API,
				 * when the user is not logged in to your website,
				 * with the access token in his session.
				 */
				// $self->userRepository->updateGoogleAccessToken($google->getUser(), serialize(value)$google->getAccessToken());

				$self->user->login(new \Nette\Security\Identity($existing->id, $existing->roles, $existing));

			} catch (\Exception $e) {
				/**
				 * You might wanna know what happened, so let's log the exception.
				 *
				 * Rendering entire bluescreen is kind of slow task,
				 * so might wanna log only $e->getMessage(), it's up to you
				 */
				\Tracy\Debugger::log($e, 'google');
				$self->flashMessage("Sorry bro, google authentication failed hard.");
			}
			
			$self->redirect('Homepage:default');
		};

		return $dialog;
	}

}
