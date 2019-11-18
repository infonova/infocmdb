<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class RelationtypeController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/relationtype_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/relationtype_en.csv', 'en');
            parent::addUserTranslation('relationtype');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function itemsperpageAction()
    {
        $itemCountPerPageSession                                   = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['relationtype'] = $this->_getParam('rowCount');
        $this->_redirect('relationtype/index');
        exit;
    }

    public function indexAction()
    {
        $this->logger->log('RelationController index action has been invoked', Zend_Log::DEBUG);

        $this->setupItemsCountPerPage('relationtype');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->relationtypePage)) {
            $page = $pageSession->relationtypePage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                          = $this->_getParam('page');
                $pageSession->relationtypePage = $page;
            }
        } else {
            $page                          = $this->_getParam('page');
            $pageSession->relationtypePage = $page;
        }

        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $filter = null;
        if ($this->_hasParam('search')) {
            if (!$this->_getParam('search')) {
                $filterString = '';
            } else {
                $filterString = '/filter/' . $this->_getParam('search') . '/';
            }
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'relationtype/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $relationtypeServiceGet = new Service_Relationtype_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $relationResult         = $relationtypeServiceGet->getRelationTypeList($page, $orderBy, $direction, $filter);

        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $relationResult['paginator'];
        $this->view->searchForm = $relationResult['searchForm']->setAction($this->view->url(array('filter' => null, 'page' => null)));
    }


    public function editAction()
    {
        $relationTypeId  = $this->_getParam('relationTypeId');
        $this->elementId = $relationTypeId;
        $this->logger->log('update relationtype "' . $relationTypeId . '" Action page invoked', Zend_Log::DEBUG);
        $relationtypeServiceUpdate = new Service_Relationtype_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $citypes                   = $relationtypeServiceUpdate->getCiTypes();
        $form                      = $relationtypeServiceUpdate->getUpdateRelationTypeForm($citypes);

        $relationtypeServiceGet = new Service_Relationtype_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $dbData                 = $relationtypeServiceGet->getRelationTypeData($relationTypeId);
        $this->elementId        = $dbData[Db_CiRelationType::NAME];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);
                $formValues = $form->getValues();

                $notification = array();
                try {
                    $check = $relationtypeServiceUpdate->updateRelationType($relationTypeId, $formValues, $dbData);
                    if ($check) $notification['success'] = $this->translator->translate('relationtypeUpdateSuccess');
                } catch (Exception_Relation_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating Relation Type "' . $relationTypeId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('relationtypeUpdateFailed');
                } catch (Exception_Relation_UpdateItemNotFound $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Relation Type "' . $relationTypeId . '". No items where updated!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('relationtypeUpdateFailed');
                } catch (Exception_Relation_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Relation Type "' . $relationTypeId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('relationtypeUpdateFailed');
                }
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('relationtype/index');

                exit;
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($dbData);
        }

        $this->view->citypes = $citypes;
        $this->view->form    = $form;
    }


    public function detailAction()
    {
        $relationTypeId = $page = $this->_getParam('relationTypeId');

        $relationtypeServiceGet = new Service_Relationtype_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $relationtype           = $relationtypeServiceGet->getRelationType($relationTypeId);
        $ciTypes                = $relationtypeServiceGet->getCurrentCiTypes($relationTypeId);
        $this->elementId        = $relationtype[Db_CiRelationType::NAME];

        $this->view->ciTypes      = $ciTypes;
        $this->view->relationtype = $relationtype;
    }


    public function createAction()
    {
        $this->logger->log('create relationtype Action page invoked', Zend_Log::DEBUG);
        $relationtypeServiceCreate = new Service_Relationtype_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ciTypes                   = $relationtypeServiceCreate->getCiTypes();
        $form                      = $relationtypeServiceCreate->getCreateRelationTypeForm($ciTypes);

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $relationId   = $relationtypeServiceCreate->createRelationType($formData);
                    $notification = array('success' => $this->translator->translate('relationCreateWizardSuccess'));

                } catch (Exception_Relation_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while adding relationtype to session ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('relationTypeInsertFailed');
                } catch (Exception_Relation_InsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create Relationtype. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('relationTypeInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('relationtype/index');
            } else {
                $form->populate($formData);
            }
        }

        $this->view->citypes = $ciTypes;
        $this->view->form    = $form;
    }


    public function deleteAction()
    {
        $relationTypeId = $this->_getParam('relationTypeId');

        $notification = array();
        try {
            $relationtypeServiceDelete = new Service_Relationtype_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $status                    = $relationtypeServiceDelete->deleteRelationType($relationTypeId);
            if ($status == 2)
                $notification = array('success' => $this->translator->translate('relationtypeDeleteSuccess'));
            if ($status == 1)
                $notification = array('success' => $this->translator->translate('relationtypeDeactivateSuccess'));

        } catch (Exception_Relation_DeleteFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to delete Relation Type', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationtypeDeleteFailed');
        } catch (Exception_Relation_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deleting Relation Type', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationtypeDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect(APPLICATION_URL . 'relationtype/index');
    }


    public function activateAction()
    {
        $relationTypeId = $this->_getParam('relationTypeId');

        $notification = array();
        try {
            $relationtypeServiceActivate = new Service_Relationtype_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $relationtypeServiceActivate->activateRelationType($relationTypeId);
            $notification = array('success' => $this->translator->translate('relationtypeActivationSuccess'));
        } catch (Exception_Relation_DeleteFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to activate Relation Type', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationtypeActivationFailed');
        } catch (Exception_Relation_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while activating Relation Type', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationtypeDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('relationtype/index/');
    }

}