<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class MessageController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/message_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/message_en.csv', 'en');
            parent::addUserTranslation('message');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function indexAction()
    {
        // action body
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/message.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;

        $page = $this->_getParam('page');

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $messageDaoImpl = new Dao_Message();
        $select         = $messageDaoImpl->getMessagesForPagination(parent::getUserInformation()->getId());

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $this->view->paginator = $paginator;
        $this->render();
    }


    public function outgoingAction()
    {
        // action body
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/message.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;

        $page = $this->_getParam('page');

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $messageDaoImpl = new Dao_Message();
        $select         = $messageDaoImpl->getOutMessagesForPagination(parent::getUserInformation()->getId());

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );


        // this is important to use the translation feature in our view!
        Zend_Registry::set('Zend_Translate', $this->translator);

        $this->view->paginator = $paginator;
        $this->render();
    }

    public function createAction()
    {
        $this->view->headScript()->appendFile(
            APPLICATION_URL . 'js/tinymce/jscripts/tiny_mce/tiny_mce.js',
            'text/javascript'
        );

        $userDaoImpl = new Dao_User();
        $users       = $userDaoImpl->getUsers();
        $userList    = array();

        foreach ($users as $user) {
            $userList[$user[Db_User::ID]] = $user[Db_User::USERNAME];
        }

        $form = new Zend_Form();
        $form->setTranslator($this->translator);

        $to = new Zend_Form_Element_Multiselect('to');
        $to->setLabel('receiver');
        $to->setMultiOptions($userList);

        $subject = new Zend_Form_Element_Text('subject');
        $subject->setLabel('subject');

        $message = new Zend_Form_Element_Textarea('message');
        $message->setLabel('message');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('submit');
        $submit->setAttrib('class', 'standard_button');

        $form->addElements(array($to, $subject, $message, $submit));

        $this->view->form = $form;


        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);
                $this->sendMessage($form);
                $this->_redirect('message/index');
            } else {
                $form->populate($formData);
            }
        }
    }


    private function sendMessage($form)
    {
        $messageDaoImpl = new Dao_Message();
        $values         = $form->getValues();

        $data                                  = array();
        $data[Db_PrivateMessage::FROM_USER_ID] = parent::getUserInformation()->getId();
        $data[Db_PrivateMessage::SUBJECT]      = $values['subject'];
        $data[Db_PrivateMessage::MESSAGE]      = $values['message'];
        $data[Db_PrivateMessage::SENT]         = date('Y-m-d H:i:s', time());

        foreach ($values['to'] as $receiver) {
            $data[Db_PrivateMessage::TO_USER_ID] = $receiver;
            $messageDaoImpl->insertMessage($data);
        }
    }


    public function readAction()
    {
        $messageId = $this->_getParam('messageId');

        $messageDaoImpl = new Dao_Message();
        $message        = $messageDaoImpl->getMessage($messageId);

        if (!$message[Db_PrivateMessage::READ]) {
            $data                          = array();
            $data[Db_PrivateMessage::READ] = date('Y-m-d H:i:s', time());
            $messageDaoImpl->updateMessage($messageId, $data);
        }

        $this->view->message = $message;
    }

    public function deleteAction()
    {
        $messageId = $this->_getParam('messageId');

        $messageDaoImpl = new Dao_Message();
        $message        = $messageDaoImpl->deleteMessage($messageId);

        $this->view->message = $message;
    }
}