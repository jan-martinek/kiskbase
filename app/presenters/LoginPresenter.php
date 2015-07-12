<?php

namespace App\Presenters;

use Model\Repository\GoogleUserRepository;
use Nette\Application\UI\Form;
use Nette\Security\Identity;
use Kdyby\Google\Dialog\LoginDialog;

class LoginPresenter extends BasePresenter {

	/** @var \Kdyby\Google\Google @inject */
	public $google;

	/** @var GoogleUserRepository @inject */
	public $userRepository;
	
	public function handleFakeLogin() {
	public function renderDefault() 
	{
		$this->template->userCreationAllowed = $this->userRepository->isUserCreationAllowed();
	}
	
		if ($this->context->parameters['debugMode']) {
			$this->user->login(new Identity(1, array(), array(
				'id' => 1,
				'roles' => array(),
				'name' => 'FakeLogin'))
			);
			$this->redirect('Homepage:default');
		}
	}

	/** @return \Kdyby\Google\Dialog\LoginDialog */
	protected function createComponentGoogleLogin() {
		$dialog = new LoginDialog($this->google);
		$self = $this;
		$dialog->onResponse[] = function (LoginDialog $dialog) use ($self) {
			$google = $dialog->getGoogle();

			if (!$google->getUser()) {
				$self->flashMessage("Sorry bro, google authentication failed.");
				$self->redirect('Login:default');
				return;
			}

			try {
				$me = $google->getProfile();

				if (!$existing = $self->userRepository->findByGoogleId($google->getUser()) 
					&& $self->userRepository->isUserCreationAllowed()) {
					$existing = $self->userRepository->registerFromGoogle($google->getUser(), $me);
				} else {
					$self->flashMessage("Sorry, Google authentication failed.");
					$self->redirect('Login:default');
					return;
				}

				/**
				 * You should save the access token to database for later usage.
				 *
				 * You will need it when you'll want to call Google API,
				 * when the user is not logged in to your website,
				 * with the access token in his session.
				 */
				$self->userRepository->updateGoogleAccessToken($google->getUser(), serialize($google->getAccessToken()));
				$self->user->login(new Identity($existing->id, $existing->roles, $existing));

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
