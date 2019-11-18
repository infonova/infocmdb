<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class ValidationController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/validation_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/validation_en.csv', 'en');
            parent::addUserTranslation('validation');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }


    public function indexAction()
    {
        $this->logger->log('Validation index action has been invoked', Zend_Log::DEBUG);
        $page = $this->_getParam('page');

        try {
            $validationService = new Service_Validation_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $validationList    = $validationService->getImportFileList($page, $orderBy, $direction, $filter);
        } catch (Exception_Validation_RetrieveIndexListFailed $e) {
            $notification          = array();
            $notification['error'] = $e->getExceptionMessage();
            $this->_helper->FlashMessenger($notification);
            $this->_redirect(APPLICATION_URL . 'index/index');
        }

        $this->view->page      = $page;
        $this->view->paginator = $validationList;
    }


    public function detailAction()
    {
        $validationId    = $this->_getParam('validationId');
        $this->elementId = $validationId;

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->validationPage[$validationId])) {
            $page = $pageSession->validationPage[$validationId];
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                                       = $this->_getParam('page');
                $pageSession->validationPage[$validationId] = $page;
            }
        } else {
            $page                                       = $this->_getParam('page');
            $pageSession->validationPage[$validationId] = $page;
        }

        $type = $this->_getParam('type');

        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $isInsert = false;
        if ($type && $type == 'insert') {
            $isInsert = true;
        }

        $this->view->validationId = $validationId;

        try {
            if ($this->_request->isPost()) {
                $formdata = $this->_request->getPost();


                $ret = null;
                if (isset($formdata['match'])) {

                    $validationServiceUpdate = new Service_Validation_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

                    unset($formdata['match']);
                    if ($type == 'update') {
                        $ret = $validationServiceUpdate->matchUpdate($validationId, parent::getUserInformation()->getId(), $formdata);
                    } else {
                        $ret = $validationServiceUpdate->matchInsert($validationId, parent::getUserInformation()->getId(), $formdata);
                    }


                } elseif (isset($formdata['delete'])) {
                    // expect ciIds
                    unset($formdata['delete']);
                    $validationServiceDelete = new Service_Validation_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

                    if ($type == 'update') {
                        $ret = $validationServiceDelete->deleteUpdate($validationId, parent::getUserInformation()->getId(), $formdata);
                    } else {
                        $ret = $validationServiceDelete->deleteInsert($validationId, parent::getUserInformation()->getId(), $formdata);
                    }
                }

                $notification = $ret['notification'];
                $redirect     = $ret['redirect'];

                if ($notification)
                    $this->_helper->FlashMessenger($notification);

                if ($redirect)
                    $this->_redirect($redirect);

                $this->_redirect('validation/index');
            }

            $validationService = new Service_Validation_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

            if ($isInsert) {
                $resList = $validationService->getValidationFileDetailInsert($validationId, parent::getUserInformation()->getId(), $page, $orderBy, $direction);

                $paginator         = $resList['paginator'];
                $typeAttributeList = $resList['attributeList'];
                $ciList            = $resList['ciList'];

                $this->view->paginator     = $paginator;
                $this->view->attributeList = $typeAttributeList;
                $this->view->ciList        = $ciList;
                $this->view->validationId  = $validationId;
                $this->render('detailinsert');
            } else {
                $resList = $validationService->getValidationFileDetailUpdate($validationId, parent::getUserInformation()->getId(), $page, $orderBy, $direction);

                $paginator             = $resList['paginator'];
                $ciList                = $resList['ciList'];
                $this->view->paginator = $paginator;
                $this->view->ciList    = $ciList;
                if ($direction == 'ASC') {
                    $this->view->dir = 'DESC';
                } else {
                    $this->view->dir = 'ASC';
                }
            }

        } catch (Exception $e) {
            $notification          = array();
            $notification['error'] = $e->getExceptionMessage();
            $this->_helper->FlashMessenger($notification);
            $this->_redirect(APPLICATION_URL . 'validation/index');
        }
    }


    public function deleteAction()
    {
        $validationId          = $this->_getParam('validationId');
        $validationAttributeId = $this->_getParam('attributeId');

        $notification = array();
        $redirect     = 'validation/index/';

        try {
            $validationService = new Service_Validation_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

            if ($validationAttributeId) {
                $ret = $validationService->deleteSingleInsert($validationId, parent::getUserInformation()->getId(), $validationAttributeId);
            } else {
                $ret = $validationService->deleteFileValidation($validationId, parent::getUserInformation()->getId());
            }

            if ($ret) {
                $notification = $ret['notification'];
                $redirect     = $ret['redirect'];
            }
        } catch (Exception $e) {
            $notification          = array();
            $notification['error'] = $e->getExceptionMessage();
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect($redirect);
    }


    /**
     * match a Attributes
     *
     * // TODO: only files can be matched!! -> see detail post action
     */
    public function matchAction()
    {

        $validationId = $this->_getParam('validationId');

        $notification = null;
        $redirect     = null;

        try {

            $validationService = new Service_Validation_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $ret               = $validationService->match($validationId, parent::getUserInformation()->getId());

            $notification = $ret['notification'];
            $redirect     = $ret['redirect'];

        } catch (Exception $e) {
            $notification          = array();
            $notification['error'] = $e->getExceptionMessage();
            $redirect              = APPLICATION_URL . 'validation/index';
            $this->_helper->FlashMessenger($notification);
        }

        $this->_redirect($redirect);
    }


    // TODO: exception handling
    public function ajaxcidetailAction()
    {
        $this->logger->log('ajax add new ci plus attributes has been invoked', Zend_Log::DEBUG);
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->setLayout('clean', false);

        $view = new Zend_View();
        $view->setEscape('htmlentities');
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/validation');

        $validationId = $this->_getParam('validationid');
        $newCiId      = $this->_getParam('newcinr');

        $validationService = new Service_Validation_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $attributeList     = $validationService->getImportFileAttributesList($validationId, $newCiId);

        $view->attributeList = $attributeList;

        echo $view->render('ajaxcidetail.phtml');
        exit;
    }
}