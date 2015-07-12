<?php

namespace App\Presenters;

use Model\Repository\GoogleUserRepository;
use Nette\Application\UI\Form;
use Nette\Security\Identity;
use Kdyby\Google\Dialog\LoginDialog;

class LoginPresenter extends BasePresenter 
{

	/** @var \Kdyby\Google\Google @inject */
	public $google;

	/** @var GoogleUserRepository @inject */
	public $userRepository;
	
	public function renderDefault() 
	{
		$this->template->userCreationAllowed = $this->userRepository->isUserCreationAllowed();
	}
	
	public function handleFakeLogin() 
	{
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
	protected function createComponentGoogleLogin() 
	{
		$dialog = new LoginDialog($this->google);
		$self = $this;
		$dialog->onResponse[] = function (LoginDialog $dialog) use ($self) {
			$google = $dialog->getGoogle();

			if (!$google->getUser()) {
				$self->flashMessage("Sorry, Google authentication failed (1).");
				$self->redirect('Login:default');
				return;
			}
			
			try {
				$me = $google->getProfile();
				$existing = $self->userRepository->findByEmail($me->email);
				if (!$existing) { 
					if ($self->userRepository->isUserCreationAllowed()) {
						$existing = $self->userRepository->registerFromGoogle($google->getUser(), $me);
					} else {
						$self->flashMessage("Sorry, Google authentication failed (2).");
						$self->redirect('Login:default');
						return;
					}
				}

				$self->userRepository->updateGoogleAccessToken($google->getUser(), serialize($google->getAccessToken()));
				$self->user->login(new Identity($existing->id, $existing->roles, $existing));

			} catch (\Exception $e) {
				\Tracy\Debugger::log($e, 'google');
				$self->flashMessage("Sorry, Google authentication failed hard.");
			}
			
			$self->redirect('Homepage:default');
		};

		return $dialog;
	}

}
