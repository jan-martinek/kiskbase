<?php

namespace App\Presenters;

use Model\Repository\GoogleUserRepository;
use Nette\Security\Identity;
use Kdyby\Google\Dialog\LoginDialog;

class LoginPresenter extends BasePresenter
{
    /** @var \Kdyby\Google\Google @inject */
    public $google;

    /** @var GoogleUserRepository @inject */
    public $userRepository;
    
    /** @persistent */
    public $backlink;
    
    /** @var \Nette\Http\Request @inject */
    public $request;
    
    public function actionDefault($backlink = null) {
        
    }

    public function renderDefault($backlink = null)
    {
        $this->template->userCreationAllowed = $this->userRepository->isUserCreationAllowed();
    }

    public function handleFakeLogin($backlink = null)
    {
        if ($this->context->parameters['debugMode']) {
            $this->user->login(new Identity(1, array(), array(
                'id' => 1,
                'roles' => array(),
                'name' => 'FakeLogin', ))
            );
            if (!empty($this->backlink)) {
                $this->redirect($this->restoreRequest($this->backlink));
            } else {
                $this->redirect('Homepage:default');    
            }
        }
    }

    /** @return \Kdyby\Google\Dialog\LoginDialog */
    protected function createComponentGoogleLogin()
    {
        $dialog = new LoginDialog($this->google);
        $self = $this;
        $dialog->onResponse[] = function (LoginDialog $dialog) use ($self) {
            $google = $dialog->getGoogle();

            if (empty($self->backlink)) {                
                if ($self->request->getCookie('googleBacklink')) {
                    $self->backlink = $self->request->getCookie('googleBacklink');
                }
            }

            if (!$google->getUser()) {
                $self->flashMessage('Sorry, Google authentication failed (1).');
                $self->redirect('Login:default', $self->backlink);
                return;
            }

            try {
                $me = $google->getProfile();
                $existing = $self->userRepository->findByEmail($me->email);
                if (!$existing) {
                    if ($self->userRepository->isUserCreationAllowed()) {
                        $existing = $self->userRepository->registerFromGoogle($google->getUser(), $me);
                    } else {
                        $self->flashMessage('Sorry, Google authentication failed (2).');
                        $self->user->logout();
                        $self->redirect('Login:default', $self->backlink);
                        return;
                    }
                }

                $self->userRepository->updateGoogleAccessToken($google->getUser(), serialize($google->getAccessToken()));
                $self->user->login(new Identity($existing->id, $existing->roles, $existing));
            } catch (\Exception $e) {
                $self->user->logout();
                \Tracy\Debugger::log($e, 'google');
                $self->flashMessage('Sorry, Google authentication failed hard.');
            }
            
            if (!empty($self->backlink)) {
                $self->redirect($self->restoreRequest($self->backlink));
            } else {
                $self->redirect('Homepage:default');    
            }
            
            
        };

        return $dialog;
    }
}
