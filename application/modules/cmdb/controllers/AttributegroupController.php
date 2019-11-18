<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class AttributegroupController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/attributegroup_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/attributegroup_en.csv', 'en');
            parent::addUserTranslation('attributegroup');
            parent::setTranslatorLocal();
            //$this->attributegroupService = new Service_Attributegroup($this->translator, $this->logger);
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function itemsperpageAction()
    {
        $itemCountPerPageSession                                     = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['attributegroup'] = $this->_getParam('rowCount');
        $this->_redirect('attributegroup/index');
        exit;
    }

    public function indexAction()
    {
        $this->logger->log('AttributeGroup index action has been invoked', Zend_Log::DEBUG);

        $this->setupItemsCountPerPage('attributegroup');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->attributegroupPage)) {
            $page = $pageSession->attributegroupPage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                            = $this->_getParam('page');
                $pageSession->attributegroupPage = $page;
            }
        } else {
            $page                            = $this->_getParam('page');
            $pageSession->attributegroupPage = $page;
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
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'attributegroup/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '', $this->_getParam('filter'));
            $filter = str_replace('%', '', $filter);

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $attributeGroupServiceGet = new Service_Attributegroup_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $attributeGroupList       = $attributeGroupServiceGet->getAttributeGroupList($page, $orderBy, $direction, $filter);

        $this->view->searchForm = $attributeGroupServiceGet->getFilterForm($filter);
        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->paginator  = $attributeGroupList['paginator'];
    }


    public function createAction()
    {
        $this->logger->log('create attributeGroup Action page invoked', Zend_Log::DEBUG);

        $attributeGroupServiceCreate = new Service_Attributegroup_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form                        = $attributeGroupServiceCreate->getCreateAttributeGroupForm();


        // this part is for validating the form
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $attributeGroup[Db_AttributeGroup::NAME]                      = trim($formData['name']);
                $attributeGroup[Db_AttributeGroup::DESCRIPTION]               = trim($formData['description']);
                $attributeGroup[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID] = $formData['parentAttributeGroup'];
                $attributeGroup[Db_AttributeGroup::NOTE]                      = trim($formData['note']);
                $attributeGroup[Db_AttributeGroup::ORDER_NUMBER]              = $formData['sorting'];
                $attributeGroup[Db_AttributeGroup::IS_ACTIVE]                 = '1';
                $attributeGroup[Db_AttributeGroup::IS_DUPLICATE_ALLOW]        = $formData['duplicate_allow'];

                $notification = array();
                try {
                    $statusCode              = $form = $attributeGroupServiceCreate->createAttributeGroup($attributeGroup);
                    $notification['success'] = $this->translator->translate('attributegroupInsertSuccess');
                } catch (Exception_AttributeGroup_InsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create AttributeGroup. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('attributegroupInsertFailed');
                } catch (Exception_AttributeGroup_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while creating new AttributeGroup', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('attributegroupInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('attributegroup/index');
                exit;
            } else {
                $form->populate($formData);
            }
        }


        $this->view->form = $form;
    }

    public function detailAction()
    {
        $this->logger->log('detail page invoked', Zend_Log::DEBUG);
        $attributeGroupId = $this->_getParam('attributeGroupId');
        $this->elementId  = $attributeGroupId;

        $attributeGroupServiceGet = new Service_Attributegroup_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $attributeGroup           = $attributeGroupServiceGet->getAttributeGroup($attributeGroupId);
        $this->elementId          = $attributeGroup[Db_AttributeGroup::NAME];

        $parent = null;
        if ($attributeGroup[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID])
            $parent = $attributeGroupServiceGet->getParentIfExists($attributeGroupId);

        $this->view->parent         = $parent;
        $this->view->attributeGroup = $attributeGroup;
    }


    public function editAction()
    {
        $this->logger->log('edit page invoked', Zend_Log::DEBUG);
        $attributeGroupId = $this->_getParam('attributeGroupId');
        $this->elementId  = $attributeGroupId;

        $attributeGroupServiceGet = new Service_Attributegroup_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        $attributeServiceGet = new Service_Attribute_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $attributeOrder      = $attributeServiceGet->getAttributesToOrder($attributeGroupId);

        $attributeServiceUpdate = new Service_Attribute_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());


        $attributeGroup  = $attributeGroupServiceGet->getAttributeGroup($attributeGroupId);
        $this->elementId = $attributeGroup[Db_AttributeGroup::NAME];

        $attributeGroupServiceUpdate = new Service_Attributegroup_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form                        = $attributeGroupServiceUpdate->getUpdateAttributeGroupForm($attributeGroupId);


        $dbFormData                         = array();
        $dbFormData['name']                 = trim($attributeGroup[Db_AttributeGroup::NAME]);
        $dbFormData['description']          = trim($attributeGroup[Db_AttributeGroup::DESCRIPTION]);
        $dbFormData['parentAttributeGroup'] = $attributeGroup[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID];
        $dbFormData['note']                 = trim($attributeGroup[Db_AttributeGroup::NOTE]);
        $dbFormData['sorting']              = $attributeGroup[Db_AttributeGroup::ORDER_NUMBER];
        $dbFormData['valid']                = $attributeGroup[Db_AttributeGroup::IS_ACTIVE];
        $dbFormData['duplicate_allow']      = $attributeGroup[Db_AttributeGroup::IS_DUPLICATE_ALLOW];


        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $values = $form->getValues();

                $attributeGroup                                               = array();
                $attributeGroup[Db_AttributeGroup::ID]                        = $attributeGroupId;
                $attributeGroup[Db_AttributeGroup::NAME]                      = trim($values['name']);
                $attributeGroup[Db_AttributeGroup::DESCRIPTION]               = trim($values['description']);
                $attributeGroup[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID] = $values['parentAttributeGroup'];
                $attributeGroup[Db_AttributeGroup::NOTE]                      = trim($values['note']);
                $attributeGroup[Db_AttributeGroup::ORDER_NUMBER]              = $values['sorting'];
                $attributeGroup[Db_AttributeGroup::IS_ACTIVE]                 = $values['valid'];
                $attributeGroup[Db_AttributeGroup::IS_DUPLICATE_ALLOW]        = $values['duplicate_allow'];


                $notification = array();
                try {
                    $attributeGroupServiceUpdate->updateAttributeGroup($attributeGroupId, $attributeGroup);

                    foreach ($formData['sort_attribute'] as $attributeId => $order_number) {
                        $orderUpdate = array('sorting' => $order_number);
                        $attributeServiceUpdate->updateAttribute($attributeId, $orderUpdate, array());

                    }

                    $notification['success'] = $this->translator->translate('attributegroupUpdateSuccess');
                } catch (Exception_AttributeGroup_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating AttributeGroup "' . $attributeGroupId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('attributegroupUpdateFailed');
                } catch (Exception_AttributeGroup_UpdateItemNotFound $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update AttributeGroup "' . $attributeGroupId . '". No items where updated!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('attributegroupUpdateFailed');
                } catch (Exception_AttributeGroup_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update AttributeGroup "' . $attributeGroupId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('attributegroupUpdateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('attributegroup/index');
                exit;
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($dbFormData);
        }

        $this->view->form       = $form;
        $this->view->attributes = $attributeOrder;
        $this->view->adminView  = Zend_Registry::get('adminMode');
    }


    public function deleteAction()
    {
        $attributeGroupId = $this->_getParam('attributeGroupId');
        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" tries to delete AttributeGroup "' . $attributeGroupId . '" ', Zend_Log::NOTICE);

        $notification = array();
        try {
            $attributeGroupServiceDelete = new Service_Attributegroup_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $statusCode                  = $attributeGroupServiceDelete->deleteAttributeGroup($attributeGroupId);
            if ($statusCode == 2)
                $notification['success'] = $this->translator->translate('attributegroupDeleteSuccess');
            if ($statusCode == 1)
                $notification['success'] = $this->translator->translate('attributegroupDeactivateSuccess');
        } catch (Exception_AttributeGroup_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deleting a AttributeGroup "' . $attributeGroupId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('attributegroupDeleteFailed');
        } catch (Exception_AttributeGroup_DeleteFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to delete AttributeGroup "' . $attributeGroupId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('attributegroupDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('attributegroup');
    }

    public function activateAction()
    {
        $attributeGroupId = $this->_getParam('attributeGroupId');

        $notification = array();
        try {
            $attributeGroupServiceDelete = new Service_Attributegroup_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $statusCode                  = $attributeGroupServiceDelete->activateAttributeGroup($attributeGroupId);
            $notification['success']     = $this->translator->translate('attributegroupActivateSuccess');
        } catch (Exception_AttributeGroup_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while activating View Type "' . $attributeGroupId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('attributegroupActivateFailed');
        } catch (Exception_AttributeGroup_ActivateFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to activate View Type "' . $attributeGroupId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('attributegroupActivateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('attributegroup/index/');

    }
}