<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class MailimportController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/mailimport_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/mailimport_en.csv', 'en');
            parent::addUserTranslation('mailimport');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function indexAction()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/mailimport.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;

        $page = $this->_getParam('page');

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $mailDaoImpl = new Dao_Mail();
        $select      = $mailDaoImpl->getMailImportsForPagination();

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $this->view->paginator = $paginator;
        $this->render();
    }


    public function createAction()
    {
        $form             = new Form_Mailimport_Create($this->translator, false);
        $this->view->form = $form;

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);
                $this->addMailImport($form);
                $this->_redirect('mailimport/index');
            } else {
                $form->populate($formData);
            }
        }
    }

    public function deleteAction()
    {
        $mailImportId = $this->_getParam('mailImportId');

        if (!$mailImportId) {
            throw new Exception_InvalidParameter();
        }

        $mailDaoImpl = new Dao_Mail();
        $mailDaoImpl->deleteMailImport($mailImportId);

        $this->_redirect('mailimport/index');
    }

    public function editAction()
    {
        $mailImportId = $this->_getParam('mailImportId');

        if (!$mailImportId) {
            throw new Exception_InvalidParameter();
        }

        $form             = new Form_Mailimport_Create($this->translator, true);
        $this->view->form = $form;


        $mailDaoImpl = new Dao_Mail();
        $mail        = $mailDaoImpl->getMailImport($mailImportId);

        $formValues                          = array();
        $formValues['host']                  = $mail[Db_MailImport::HOST];
        $formValues['user']                  = $mail[Db_MailImport::USER];
        $formValues['password']              = $mail[Db_MailImport::PASSWORD];
        $formValues['ssl']                   = $mail[Db_MailImport::SSL];
        $formValues['ciField']               = $mail[Db_MailImport::CI_FIELD];
        $formValues['attachBody']            = $mail[Db_MailImport::IS_ATTACH_BODY];
        $formValues['bodyAttributeId']       = $mail[Db_MailImport::BODY_ATTRIBUTE_ID];
        $formValues['attachmentAttributeId'] = $mail[Db_MailImport::ATTACHMENT_ATTRIBUTE_ID];
        $formValues['enableCiMail']          = $mail[Db_MailImport::IS_CI_MAIL_ENABLED];
        $formValues['note']                  = $mail[Db_MailImport::NOTE];
        $formValues['active']                = $mail[Db_MailImport::IS_ACTIVE];
        $formValues['protocol']              = $mail[Db_MailImport::PROTOCOL];
        $formValues['port']                  = $mail[Db_MailImport::PORT];
        $formValues['move_folder']           = $mail[Db_MailImport::MOVE_FOLDER];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);
                $this->addMailImport($form, true, $mailImportId);
                $this->_redirect('mailimport/index');
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($formValues);
        }

        //$this->render('create');
    }


    private function addMailImport($form, $isUpdate = false, $mailImportId = null)
    {
        $values = $form->getValues();
        $data   = array();


        if ($mailImportId)
            $data[Db_MailImport::ID] = $mailImportId;

        $data[Db_MailImport::HOST]                    = $values['host'];
        $data[Db_MailImport::USER]                    = $values['user'];
        $data[Db_MailImport::PASSWORD]                = $values['password'];
        $data[Db_MailImport::SSL]                     = $values['ssl'];
        $data[Db_MailImport::CI_FIELD]                = $values['ciField'];
        $data[Db_MailImport::ATTACHMENT_ATTRIBUTE_ID] = $values['attachmentAttributeId'];
        $data[Db_MailImport::PROTOCOL]                = $values['protocol'];
        $data[Db_MailImport::PORT]                    = $values['port'];

        if (empty($data[Db_MailImport::PORT])) {
            if ($data[Db_MailImport::PROTOCOL] == 'IMAP') {
                if ($data[Db_MailImport::SSL] == 'SSL') {
                    $data[Db_MailImport::PORT] = 993;
                } else {
                    $data[Db_MailImport::PORT] = 143;
                }
            } elseif ($data[Db_MailImport::PROTOCOL] == 'POP3') {
                if ($data[Db_MailImport::SSL] == 'SSL') {
                    $data[Db_MailImport::PORT] = 995;
                } else {
                    $data[Db_MailImport::PORT] = 110;
                }
            }
        }

        if (!is_null($values['move_folder']))
            $data[Db_MailImport::MOVE_FOLDER] = $values['move_folder'];

        if (!is_null($values['attachBody']))
            $data[Db_MailImport::IS_ATTACH_BODY] = $values['attachBody'];

        if (!is_null($values['bodyAttributeId']))
            $data[Db_MailImport::BODY_ATTRIBUTE_ID] = $values['bodyAttributeId'];

        if (!is_null($values['enableCiMail']))
            $data[Db_MailImport::IS_CI_MAIL_ENABLED] = $values['enableCiMail'];

        if (!is_null($values['note']))
            $data[Db_MailImport::NOTE] = $values['note'];

        if (!is_null($values['active']))
            $data[Db_MailImport::IS_ACTIVE] = $values['active'];

        $mailDaoImpl = new Dao_Mail();

        if ($isUpdate) {
            $mailDaoImpl->updateMailImport($mailImportId, $data);
        } else {
            $mailDaoImpl->insertMailImport($data);
        }
    }


    public function retrievemailmessagesAction()
    {
        $mailDaoImpl = new Dao_Mail();
        $list        = $mailDaoImpl->getMailImportsForCronjob();

        foreach ($list as $mail) {
            $args                 = array();
            $args['mailImportId'] = $mail[Db_MailImport::ID];
            $args['host']         = $mail[Db_MailImport::HOST];

            $message = new Service_Queue_Message();
            $message->setQueueId(2); // TODO: replace me!
            $message->setArgs($args);
            Service_Queue_Handler::add($message);
        }
        $this->_redirect('mailimport/index');
    }


    public function editcronjobAction()
    {
        $mailimportId = $this->_getParam('mailimportId');

        if (!$mailimportId) {
            throw new Exception_InvalidParameter();
        }

        $mailDaoImpl = new Dao_Mail();
        $mail        = $mailDaoImpl->getMailImport($mailimportId);

        // display form
        $this->view->cronjob = $mail[Db_MailImport::EXECUTION_TIME];
        $this->view->form    = $this->generateCronForm($mailimportId, $mail[Db_MailImport::EXECUTION_TIME]);
    }


    public function changecronjobAction()
    {
        $mailimportId = $this->_getParam('mailimportId');

        if (!$mailimportId) {
            throw new Exception_InvalidParameter();
        }

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $this->logger->log('Form is valid', Zend_Log::DEBUG);


            $min   = "";
            $hour  = "";
            $day   = "";
            $month = "";
            $week  = "";

            // handle minutes
            if ($formData['minutes_radio'] == 0) {
                $min = '*';
            } else {
                // get from selection
                $min = $formData['minutes'][0];
            }

            // handle hours
            if ($formData['hours_radio'] == 0) {
                $hour = '*';
            } else {
                // get from selection
                $hour = $formData['hours'][0];
            }

            // handle days
            if ($formData['days_radio'] == 0) {
                $day = '*';
            } else {
                // get from selection
                $day = $formData['days'][0];
            }

            // handle months
            if ($formData['months_radio'] == 0) {
                $month = '*';
            } else {
                // get from selection
                $month = $formData['months'][0];
            }

            // handle weekdays
            if ($formData['weekdays_radio'] == 0) {
                $week = '*';
            } else {
                // get from selection
                $week = $formData['weekdays'][0];
            }


            if (is_null($min)) {
                $min = 0;
            }

            if (is_null($hour)) {
                $hour = 0;
            }

            if (is_null($day)) {
                $day = 0;
            }

            if (is_null($month)) {
                $month = 0;
            }

            if (is_null($week)) {
                $week = 0;
            }

            $cronjob     = $min . ' ' . $hour . ' ' . $day . ' ' . $month . ' ' . $week;
            $mailDaoImpl = new Dao_Mail();
            $mailDaoImpl->updateMailimportCronjob($mailimportId, $cronjob);
            $this->_redirect('mailimport/editcronjob/mailimportId/' . $mailimportId);
        }
    }

    private function generateCronForm($mailimportId, $values)
    {
        $action = APPLICATION_URL . 'mailimport/changecronjob/mailimportId/' . $mailimportId;
        $form   = new Form_Cronjob_Create($this->translator, $action, $values);
        return $form;
    }


    public function autocompleteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $query = $this->_getParam('name', '');

        $attributeDaoImpl = new Dao_Attribute();
        $results          = $attributeDaoImpl->getActiveAttributesAutoComplete($query);

        $data = new Zend_Dojo_Data('id', $results, 'name');

        // Send our output
        $this->_helper->autoCompleteDojo($data);
        echo $data->toJson();
    }
}