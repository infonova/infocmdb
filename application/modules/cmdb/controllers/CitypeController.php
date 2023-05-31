<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class CitypeController extends AbstractAppAction
{

    public function init()
    {
        parent::init();
        try {
            $this->translator->addTranslation($this->languagePath . '/de/citype_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/citype_en.csv', 'en');
            parent::addUserTranslation('citype');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    /**
     *  The index action is used to display all configured user entries
     *  by using the pagination feature
     *
     */
    public function itemsperpageAction()
    {
        $itemCountPerPageSession                             = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['citype'] = $this->_getParam('rowCount');
        $this->_redirect('citype/index');
        exit;
    }

    public function indexAction()
    {
        $this->logger->log('CiType index action has been invoked', Zend_Log::DEBUG);

        $this->setupItemsCountPerPage('citype');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->citypePage)) {
            $page = $pageSession->citypePage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                    = $this->_getParam('page');
                $pageSession->citypePage = $page;
            }
        } else {
            $page                    = $this->_getParam('page');
            $pageSession->citypePage = $page;
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
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'citype/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '', $this->_getParam('filter'));
            $filter = str_replace('%', '', $filter);

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $citypeServiceGet = new Service_Citype_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $citypeResult     = $citypeServiceGet->getCitypeList($page, $orderBy, $direction, $filter);

        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $citypeResult['paginator'];
        $this->view->searchForm = $citypeServiceGet->getFilterForm($filter)->setAction($this->view->url(array('filter' => $filter, 'page' => null)));
    }


    /**
     * this function is used to display a single ci type
     */
    public function detailAction()
    {
        $typeId = $this->_getParam('citypeId');

        $citypeServiceGet = new Service_Citype_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $currentCiType    = $citypeServiceGet->getCiType($typeId);

        if ($currentCiType[Db_CiType::PARENT_CI_TYPE_ID])
            $parent = $citypeServiceGet->getCiType($currentCiType[Db_CiType::PARENT_CI_TYPE_ID]);
        if ($currentCiType[Db_CiType::DEFAULT_PROJECT_ID])
            $defaultProject = $citypeServiceGet->getProject($currentCiType[Db_CiType::DEFAULT_PROJECT_ID]);
        if ($currentCiType[Db_CiType::DEFAULT_ATTRIBUTE_ID])
            $defaultAttribute = $citypeServiceGet->getAttribute($currentCiType[Db_CiType::DEFAULT_ATTRIBUTE_ID]);
        if ($currentCiType[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID])
            $defaultSortAttribute = $citypeServiceGet->getAttribute($currentCiType[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID]);

        $attributes = $citypeServiceGet->getAttributes($typeId);
        $relations  = $citypeServiceGet->getRelations($typeId);

        $this->view->attributes           = $attributes;
        $this->view->relations            = $relations;
        $this->view->parent               = $parent;
        $this->view->defaultProject       = $defaultProject;
        $this->view->defaultAttribute     = $defaultAttribute;
        $this->view->defaultSortAttribute = $defaultSortAttribute;
        //$this->view->iconPath = APPLICATION_PUBLIC.$path;
        $this->view->icon   = $currentCiType[Db_CiType::ICON];
        $this->view->ciType = $currentCiType;
    }


    /**
     * this function is used to display a single ci type
     * the parent ci type can be changed by adding an addidtional parameter
     */
    public function editAction()
    {
        $this->logger->log('editAction page invoked', Zend_Log::DEBUG);

        $config      = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination.ini', APPLICATION_ENV);
        $maxElements = $config->list->attribute->size;

        $typeId              = $this->_getParam('citypeId');
        $this->elementId     = $typeId;
        $citypeServiceUpdate = new Service_Citype_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $attributes          = $citypeServiceUpdate->getAttributes();
        $relations           = $citypeServiceUpdate->getRelations();

        $citypeServiceGet = new Service_Citype_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $this->logger->log('getCiTypeData:' . $typeId, Zend_Log::DEBUG);
        $dbData          = $citypeServiceGet->getCiTypeData($typeId);
        $this->elementId = $dbData[Db_CiType::NAME];


        $form = $citypeServiceUpdate->getUpdateCiTypeForm($typeId, $attributes, $relations, $dbData['defaultProject'], $maxElements);

        if ($this->_request->isPost()) {
            $newFormData         = $this->_request->getPost();
            $newFormData['icon'] = $form->getValue('icon');
            if ($form->isValid($newFormData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $check = $citypeServiceUpdate->updateCiType($typeId, $newFormData, $dbData);
                    parent::clearNavigationCache();
                    if ($check) $notification['success'] = $this->translator->translate('ciTypeUpdateSuccess');
                } catch (Exception_Citype_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating CiType "' . $attributeGroupId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('ciTypeUpdateFailed');
                } catch (Exception_Citype_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update CiType "' . $attributeGroupId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('ciTypeUpdateFailed');
                } catch (Exception_Citype_WrongIconType $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update CiType "' . $attributeGroupId . '", icon file type wrong!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('citypeWrongIconType');
                } catch (Exception_Citype_UpdateItemNotFound $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update CiType "' . $attributeGroupId . '". No items where updated!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('ciTypeUpdateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('citype/index');
            } else {
                $form->populate($newFormData);
            }
        } else {

            $form->populate($dbData);
        }

        $c = 0;

        $this->view->parent = $dbData['parentCiType'];

        if (isset($dbData['addAttribute_20']))
            $c = 1;
        if (isset($dbData['addAttribute_40']))
            $c = 2;

        $this->view->storedIcon  = $dbData['icon'];
        $this->view->maxElements = $maxElements;
        $this->view->c           = $c;
        $this->view->attributes  = $attributes;
        $this->view->relations   = $relations;
        $this->view->form        = $form;
    }


    public function deleteAction()
    {
        $typeId              = $page = $this->_getParam('citypeId');
        $citypeServiceDelete = new Service_Citype_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $status              = $citypeServiceDelete->deleteCiType($typeId);

        $notification = array();
        switch ($status) {
            case 1:
                $notification['success'] = $this->translator->translate('ciTypeDeleteSuccess');
                break;
            case 2:
                $notification['success'] = $this->translator->translate('ciTypeDactivateSuccess');
                break;
            default:
                $notification['error'] = $this->translator->translate('ciTypeDeleteFailed');
                break;
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('citype/index');
    }


    public function activateAction()
    {
        $typeId = $page = $this->_getParam('citypeId');

        $notification = array();
        try {
            $citypeServiceDelete = new Service_Citype_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $citypeServiceDelete->activateCiType($typeId);
            $notification['success'] = $this->translator->translate('citypeActivateSuccess');
        } catch (Exception_Citype_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while activating and Citype "' . $typeId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('citypeActivateFailed');
        } catch (Exception_Citype_ActivateFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to activate Citype "' . $typeId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('citypeActivateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('citype/index/');
    }


    public function removeimageAction()
    {
        $typeId = $page = $this->_getParam('citypeId');

        $notification = array();
        try {
            $citypeServiceUpdate = new Service_Citype_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $citypeServiceUpdate->removeImage($typeId);
            $notification['success'] = $this->translator->translate('citypeDeleteImageSuccess');
        } catch (Exception_Citype_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deletign Image from Citype "' . $typeId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('citypeDeleteImageFailed');
        } catch (Exception_Citype_DeleteImageFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to delete Image from Citype "' . $typeId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('citypeDeleteImageFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('citype/detail/citypeId/' . $typeId . '/');
    }


    public function chooseiconAction()
    {
        $this->_helper->layout->disableLayout();
        $citypeServiceGet   = new Service_Citype_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $images             = $citypeServiceGet->getStoredIcons();
        $this->view->images = $images['images'];
        $this->view->path   = $images['path'];
        $this->view->form   = $typeId = $this->_getParam('Form');
    }


    public function createAction()
    {
        $this->logger->log('create citypeAction page invoked', Zend_Log::DEBUG);

        $config      = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination.ini', APPLICATION_ENV);
        $maxElements = $config->list->attribute->size;

        $citypeServiceCreate = new Service_Citype_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $attributes          = $citypeServiceCreate->getAttributes();
        $relations           = $citypeServiceCreate->getRelations();
        $form                = $citypeServiceCreate->getCreateCiTypeForm($attributes, $relations, $maxElements);
        $cloneFromId         = $this->_getParam('cloneFromId');

        if ($this->_request->isPost()) {
            $formData         = $this->_request->getPost();
            $formData['icon'] = $form->getValue('icon');
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $citypeId                = $citypeServiceCreate->createCitype($formData);
                    $notification['success'] = $this->translator->translate('citypeCreateSuccess');

                } catch (Exception_Citype_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while adding Citype to session ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('citypeCreateFailed');
                } catch (Exception_Citype_WrongIconType $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to add CI type to session, icon file type wrong!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('citypeWrongIconType');
                } catch (Exception_Citype_InsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to add CI type to session!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('citypeCreateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect(APPLICATION_URL . 'citype/index');
            } else {

                $form->populate($formData);
            }
        } else if ($cloneFromId != null) {
            $citypeServiceGet = new Service_Citype_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $dbData           = $citypeServiceGet->getCiTypeData($cloneFromId);
            $dbData['name']   = 'copy_of_' . $dbData['name'];
            $form->populate($dbData);
            $this->logger->log('Cloning CiTypeId:' . $cloneFromId, Zend_Log::INFO);
        }

        $this->view->maxElements = $maxElements;
        $this->view->attributes  = $attributes;
        $this->view->relations   = $relations;
        $this->view->form        = $form;
    }

    public function updateformforparentAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $parentCiTypeId = $this->_getParam('parent');
        $citypeId       = $this->_getParam('citypeId');
        $count          = $this->_getParam('count');
        $value          = $this->_getParam('value');

        $citypeServiceCreate = new Service_Citype_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $this->view->field   = $citypeServiceCreate->getChildElementFormField($citypeId, $parentCiTypeId, $count, $value);
    }

    public function updatepersistentattributesAction()
    {
        $typeId       = $this->_getParam('citypeId');
        $ciDao        = new Dao_Ci();
        $attributeDao = new Dao_Attribute();
        $historyDao   = new Dao_History();

        $ciList = $ciDao->getCiListByCiTypeId($typeId);

        foreach ($ciList as $ci) {
            $historyId        = $historyDao->createHistory(0, Enum_History::CI_UPDATE);
            $querypAttributes = $attributeDao->getAttributesByAttributeTypeCiID($ci['id'], Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID);
            Util_AttributeType_Type_QueryPersist::execute_query($ci['id'], $querypAttributes, $historyId);
        }

        print "successfully updated query persistent attributes";
        exit(0);
    }

}