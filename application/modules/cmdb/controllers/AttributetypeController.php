<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class AttributetypeController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/attributetype_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/attributetype_en.csv', 'en');
            parent::addUserTranslation('attributetype');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }


    public function indexAction()
    {
        $this->logger->log('Attributetype index action has been invoked', Zend_Log::DEBUG);

        $page      = $this->_getParam('page');
        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $filter = null;
        if ($this->_hasParam('search')) {
            if (!$this->_getParam('search')) {
                $filterString = '';
            } else {
                $filterString = '/filter/' . $this->_getParam('search') . '/';
            }
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'attributetype/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '', $this->_getParam('filter'));
            $filter = str_replace('%', '', $filter);

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $attributetypeService = new Service_Attributetype_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result               = $attributetypeService->getAttributetypeList($page, $orderBy, $direction, $filter);

        $this->view->searchForm = $attributetypeService->getFilterForm($filter);
        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $result;
    }


    public function deactivateAction()
    {
        $menuId = $this->_getParam('attributetypeId');

        if (is_null($menuId)) {
            throw new Exception_InvalidParameter();
        }

        $formData[Db_Menu::IS_ACTIVE] = '0';

        $attributetypeServiceUpdate = new Service_Attributetype_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        try {
            $attributetypeServiceUpdate->updateAttributetype($formData, $menuId);
            parent::clearNavigationCache();
            $notification['success'] = $this->translator->translate('attributetypeDeactivateSuccess');
        } catch (Exception_AttributeGroup $e) {
            $notification['error'] = $this->translator->translate('attributetypeDeactivateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect(APPLICATION_URL . 'attributetype/index');
    }


    public function activateAction()
    {
        $menuId = $this->_getParam('attributetypeId');

        if (is_null($menuId)) {
            throw new Exception_InvalidParameter();
        }

        $formData[Db_AttributeType::IS_ACTIVE] = '1';

        $attributetypeServiceUpdate = new Service_Attributetype_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        try {
            $attributetypeServiceUpdate->updateAttributetype($formData, $menuId);
            parent::clearNavigationCache();
            $notification['success'] = $this->translator->translate('attributetypeActivateSuccess');
        } catch (Exception_AttributeGroup $e) {
            $notification['error'] = $this->translator->translate('attributetypeActivateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect(APPLICATION_URL . 'attributetype/index');
    }


    public function editAction()
    {
        $menuId = $this->_getParam('attributetypeId');

        if (is_null($menuId)) {
            throw new Exception_InvalidParameter();
        }

        $this->elementId = $menuId;

        // retrieve current attribute information
        $attributetypeDaoImpl = new Dao_AttributeType();
        $menuDto              = $attributetypeDaoImpl->getAttributetype($menuId);
        $this->elementId      = $menuDto[Db_AttributeType::NAME];

        $formData          = array();
        $formData['order'] = $menuDto[Db_Menu::ORDER_NUMBER];

        $attributetypeServiceUpdate = new Service_Attributetype_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form                       = $attributetypeServiceUpdate->getUpdateAttributetypeForm();

        // this part is for validating the form
        if ($this->_request->isPost()) {
            $newformData = $this->_request->getPost();
            foreach ($newformData as $key => $val) {
                if ($val == '' || $val == ' ' || is_null($val))
                    unset($newformData[$key]);
            }
            if ($form->isValid($newformData)) {
                $newformData = $form->getValues();
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                try {
                    $attributetypeServiceUpdate->updateAttributetype($newformData, $menuId);
                    parent::clearNavigationCache();
                    $notification['success'] = $this->translator->translate('attributetypeUpdateSuccess');
                } catch (Exception_AttributeGroup $e) {
                    $notification['error'] = $this->translator->translate('attributetypeUpdateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect(APPLICATION_URL . 'attributetype/index');
            } else {
                $form->populate($newformData);
            }
        } else {
            $form->populate($formData);
        }

        $this->view->menu   = $menuDto;
        $this->view->menuId = $menuId;
        $this->view->form   = $form;
    }

    public function detailAction()
    {
        $menuId = $this->_getParam('attributetypeId');

        if (is_null($menuId)) {
            throw new Exception_InvalidParameter();
        }

        // retrieve current attribute information
        $menuDaoImpl = new Dao_AttributeType();
        $menuDto     = $menuDaoImpl->getAttributetype($menuId);

        $this->elementId = $menuDto[Db_AttributeType::NAME];

        $this->view->menu   = $menuDto;
        $this->view->menuId = $menuId;
    }
}