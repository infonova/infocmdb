<?php
require_once 'AbstractAppAction.php';

/**
 * this class is used to manage notification templates
 *
 *
 *
 */
class TemplateController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/template_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/template_en.csv', 'en');
            parent::addUserTranslation('template');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    // show index
    public function indexAction()
    {
        $this->logger->log('Template index action has been invoked', Zend_Log::DEBUG);

        $page      = $this->_getParam('page');
        $direction = $this->_getParam('direction');
        $orderBy   = $this->_getParam('orderBy');

        if (is_null($page)) {
            $this->logger->log('template page var was null. using default value 1 for user display', Zend_Log::DEBUG);
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

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $serviceTemplateGet = new Service_Template_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result             = $serviceTemplateGet->getTemplateList($page, $orderBy, $direction, $filter);

        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $result['paginator'];
        $this->view->searchForm = $result['searchForm']->setAction($this->view->url(array('filter' => null, 'page' => null)));
    }


    public function deleteAction()
    {
        $templateId = $this->_getParam('templateId');
        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" tries to delete Template "' . $templateId . '" ', Zend_Log::INFO);

        $notification = array();
        try {
            $serviceTemplateDelete = new Service_Template_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $serviceTemplateDelete->deleteTemplate($templateId);
            $this->logger->log('Template "' . $templateId . '" deleted by User "' . parent::getUserInformation()->getId() . '"', Zend_Log::INFO);
            $notification['success'] = $this->translator->translate('notificationDeleteSuccess');
        } catch (Exception_Template_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deleting a Template "' . $templateId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('notificationDeleteFailed');
        } catch (Exception_Template_DeleteFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to delete Template "' . $templateId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('notificationDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('template/index');
    }


    public function createAction()
    {
        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" tries to create Template', Zend_Log::DEBUG);

        $serviceTemplateCreate = new Service_Template_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form                  = $serviceTemplateCreate->getCreateTemplateForm();

        if ($this->_request->isPost()) {
            $formdata = $this->_request->getPost();
            if ($form->isValid($formdata)) {
                $notification = array();
                try {
                    $templateId = $serviceTemplateCreate->insertTemplate($form->getValues());
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" created Template "' . $templateId . '"', Zend_Log::INFO);
                    $notification['success'] = $this->translator->translate('notificationInsertSuccess');
                } catch (Exception_Template_InsertFailed $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('notificationInsertFailed');
                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('notificationInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('template/index');
            } else {
                $form->populate($formdata);
            }
        }
        $this->view->form = $form;
    }


    // FIXME: implement this method!
    public function editAction()
    {
        $notification['error'] = "Method not implemented!";

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('template/index');
    }
}