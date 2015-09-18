<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use DibiConnection;
use DateTime;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @persistent */
    public $locale;

    /** @var \Kdyby\Translation\Translator @inject */
    public $translator;
    
    /** @var \Nette\Http\Response @inject */
    public $response;

    public function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn() && !($this->getName() === 'Login')) {
            $backlink = $this->storeRequest('+ 48 hour');
            $this->response->setCookie('googleBacklink', $backlink, new DateTime('+ 5 minute'));
            
            $this->redirect('Login:default', $backlink);
        }
    }

    protected function createTemplate($class = null)
    {
        $template = parent::createTemplate($class);

        $this->translator->createTemplateHelpers()
             ->register($template->getLatte());

        return $template;
    }

    protected function createComponentSearchForm()
    {
        $form = new Form();
        $form->addText('query', $this->translator->translate('messages.app.search'))
             ->setAttribute('placeholder', $this->translator->translate('messages.app.search'));
        $form->addSubmit('submit', $this->translator->translate('messages.app.performSearch'));
        $form->onSuccess[] = array($this, 'searchFormSucceeded');

        if (isset($this->params['query'])) {
            $form['query']->setDefaultValue($this->params['query']);
        }

        return $form;
    }

    public function searchFormSucceeded(Form $form, $values)
    {
        $this->redirect(':Search:default', $values->query);
    }

    public function handleLogout()
    {
        $user = $this->getUser();
        $user->logout();
        $this->redirect('Homepage:default');
    }
}
