<?php
require_once 'AbstractAppAction.php';

class MailController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation(
                $this->languagePath . '/de/mail_de.csv', 'de');
            $this->translator->addTranslation(
                $this->languagePath . '/en/mail_en.csv', 'en');
            parent::addUserTranslation('mail');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function autocompletemultiselectAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $mailId = $this->_getParam('mailId');

        $serviceMailUpdate = new Service_Mail_Update($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $recipients        = $serviceMailUpdate->getRecipientsMultiSelectByMailId(
            $mailId);

        $output = "";
        foreach ($recipients as $recipient) {
            if (!empty($recipient["notificationId"])) {
                $output .= "s_";
            }
            $output .= $recipient["userId"] . "=" . $recipient["email"] . "\n";
        }

        print $output;
        exit();
    }

    public function indexAction()
    {
        $this->logger->log('Mail index action has been invoked',
            Zend_Log::DEBUG);

        $page      = $this->_getParam('page');
        $direction = $this->_getParam('direction');
        $orderBy   = $this->_getParam('orderBy');

        if (is_null($page)) {
            $this->logger->log(
                'mail page var was null. using default value 1 for user display',
                Zend_Log::DEBUG);
            $page = 1;
        }

        $filter = null;
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $filter   = str_replace('*', '%', $formData['search']);

            if (!$filter) {
                $filter = '%';
            }
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));
        }

        $serviceMailGet = new Service_Mail_Get($this->translator, $this->logger,
            parent::getUserInformation()->getThemeId());
        $result         = $serviceMailGet->getMailList($page, $orderBy, $direction,
            $filter);

        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $result['paginator'];
        $this->view->searchForm = $result['searchForm']->setAction(
            $this->view->url(
                array(
                    'filter' => null,
                    'page'   => null,
                )));
    }

    public function deleteAction()
    {
        $mailId = $this->_getParam('mailId');
        $this->logger->log(
            'User "' . parent::getUserInformation()->getId() .
            '" tries to delete Mail "' . $mailId . '" ',
            Zend_Log::INFO);

        $notification = array();
        try {
            $serviceMailDelete = new Service_Mail_Delete($this->translator,
                $this->logger, parent::getUserInformation()->getThemeId());
            $serviceMailDelete->deleteMail($mailId);
            $this->logger->log(
                'Template "' . $mailId . '" deleted by User "' .
                parent::getUserInformation()->getId() . '"',
                Zend_Log::INFO);
            $notification['success'] = $this->translator->translate(
                'notificationDeleteSuccess');
        } catch (Exception_Mail_Unknown $e) {
            $this->logger->log(
                'User "' . parent::getUserInformation()->getId() .
                '" encountered an unknown Exception while deleting a Mail "' .
                $mailId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate(
                'notificationDeleteFailed');
        } catch (Exception_Mail_DeleteFailed $e) {
            $this->logger->log(
                'User "' . parent::getUserInformation()->getId() .
                '" failed to delete Mail "' . $mailId . '" ',
                Zend_Log::ERR);
            $notification['error'] = $this->translator->translate(
                'notificationDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('mail/index');
    }

    public function createAction()
    {
        $this->view->headScript()->appendFile(
            APPLICATION_URL . 'js/tiny_mce/tiny_mce.js', 'text/javascript');
        $this->view->headScript()->appendFile(
            APPLICATION_URL . 'js/tiny_mce/tiny_mce_init.js', 'text/javascript');

        $this->logger->log(
            'User "' . parent::getUserInformation()->getId() .
            '" tries to create Mail Template', Zend_Log::DEBUG);

        $serviceMailCreate = new Service_Mail_Create($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $form              = $serviceMailCreate->getCreateMailForm();

        if ($this->_request->isPost()) {
            $formdata = $this->_request->getPost();
            if ($form->isValid($formdata)) {
                $notification = array();
                try {
                    $mailId = $serviceMailCreate->insertMail($form->getValues());
                    $this->logger->log(
                        'User "' . parent::getUserInformation()->getId() .
                        '" created Mail Template "' . $mailId . '"',
                        Zend_Log::INFO);
                    $notification['success'] = $this->translator->translate(
                        'notificationInsertSuccess');
                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate(
                        'notificationInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('mail/index');
            } else {
                $form->populate($formdata);
            }
        }

        $this->_helper->viewRenderer('form');
        $this->view->headerText = $this->view->translate('mailCreate');
        $this->view->form       = $form;
    }

    public function editAction()
    {
        $this->view->headScript()->appendFile(
            APPLICATION_URL . 'js/tiny_mce/tiny_mce.js', 'text/javascript');
        $this->view->headScript()->appendFile(
            APPLICATION_URL . 'js/tiny_mce/tiny_mce_init.js', 'text/javascript');

        $this->logger->log(
            'User "' . parent::getUserInformation()->getId() .
            '" tries to create Mail Template', Zend_Log::DEBUG);
        $mailId          = $this->_getParam('mailId');
        $this->elementId = $mailId;

        $serviceMailUpdate = new Service_Mail_Update($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $form              = $serviceMailUpdate->getUpdateMailForm();
        $formvalue         = $serviceMailUpdate->getMailForUpdateById($mailId);
        $this->elementId   = $formvalue[Db_Mail::NAME];

        if ($this->_request->isPost()) {
            $formdata = $this->_request->getPost();
            if ($form->isValid($formdata)) {
                $notification = array();
                try {
                    $mailId = $serviceMailUpdate->updateMail($form->getValues(),
                        $mailId);
                    $this->logger->log(
                        'User "' . parent::getUserInformation()->getId() .
                        '" created Mail Template "' . $mailId . '"',
                        Zend_Log::INFO);
                    $notification['success'] = $this->translator->translate(
                        'notificationInsertSuccess');
                } catch (Exception_Template_AddRecepientFailed $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate(
                        'notificationInsertFailed');
                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate(
                        'notificationInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('mail/index');
            } else {
                $form->populate($formdata);
            }
        } else {
            $form->populate($formvalue);
        }

        $this->_helper->viewRenderer('form');
        $this->view->headerText = $this->view->translate('mailUpdate');
        $this->view->form       = $form;
        $this->view->mailId     = $mailId;
    }

    /* get Email from DB and pattern match to find all occurrences of a placeholder. pass that information to the view for generating inputs */
    public function viewAction()
    {
        $mailId   = $this->_getParam('mailId');
        $dao_mail = new Dao_Mail();
        $mail     = $dao_mail->getMail($mailId);

        $dao_notifiaction = new Dao_Notification();
        $recipients       = $dao_notifiaction->getNotificationRecipients($mailId);

        $pattern = "/(:[A-z0-9]*:)/"; //pattern machtes every word consisting of letters from a-z and A-Z and digits from 0-9
        if (preg_match_all($pattern, $mail['body'], $matches)) { //find every occurence of a placeholder in the mail body
            $matches = array_unique($matches[0]);            //we only need every placeholder once
        }
        $placeholder = array();
        foreach ($matches as $match) {
            $inputLabel    = preg_replace('/:/', '', $match);
            $placeholder[] = $inputLabel;
        }

        $this->view->mail       = $mail;
        $this->view->recipients = $recipients;
        $this->view->inputs     = $placeholder;
    }

}