<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class MenuController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/menu_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/menu_en.csv', 'en');
            parent::addUserTranslation('menu');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }


    public function indexAction()
    {
        $this->logger->log('Menu index action has been invoked', Zend_Log::DEBUG);

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
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'menu/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $menuService = new Service_Menu_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result      = $menuService->getMenuList($page, $orderBy, $direction, $filter);

        $this->view->searchForm = $menuService->getFilterForm($filter);
        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $result;
    }

    public function deactivateAction()
    {
        $menuId = $this->_getParam('menuId');

        if (is_null($menuId)) {
            throw new Exception_InvalidParameter();
        }

        $formData[Db_Menu::IS_ACTIVE] = '0';

        $menuServiceUpdate = new Service_Menu_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        try {
            $menuServiceUpdate->updateMenu($formData, $menuId);
            parent::clearNavigationCache();
            $notification['success'] = $this->translator->translate('menuDeactivateSuccess');
        } catch (Exception_AttributeGroup $e) {
            $notification['error'] = $this->translator->translate('menuDeactivateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect(APPLICATION_URL . 'menu/index');
    }

    public function activateAction()
    {
        $menuId = $this->_getParam('menuId');

        if (is_null($menuId)) {
            throw new Exception_InvalidParameter();
        }

        $formData[Db_Menu::IS_ACTIVE] = '1';

        $menuServiceUpdate = new Service_Menu_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        try {
            $menuServiceUpdate->updateMenu($formData, $menuId);
            parent::clearNavigationCache();
            $notification['success'] = $this->translator->translate('menuActivateSuccess');
        } catch (Exception_AttributeGroup $e) {
            $notification['error'] = $this->translator->translate('menuActivateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect(APPLICATION_URL . 'menu/index');
    }

    public function editAction()
    {
        $menuId          = $this->_getParam('menuId');
        $this->elementId = $menuId;

        if (is_null($menuId)) {
            throw new Exception_InvalidParameter();
        }

        // retrieve current attribute information
        $menuDaoImpl     = new Dao_Menu();
        $menuDto         = $menuDaoImpl->getMenu($menuId);
        $this->elementId = $menuDto[Db_Menu::NAME];

        $formData                = array();
        $formData['description'] = $menuDto[Db_Menu::DESCRIPTION];
        $formData['note']        = $menuDto[Db_Menu::NOTE];
        $formData['order']       = $menuDto[Db_Menu::ORDER_NUMBER];

        $menuServiceUpdate = new Service_Menu_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form              = $menuServiceUpdate->getUpdateMenuForm();

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
                    $menuServiceUpdate->updateMenu($newformData, $menuId);
                    parent::clearNavigationCache();
                    $notification['success'] = $this->translator->translate('menuUpdateSuccess');
                } catch (Exception_AttributeGroup $e) {
                    $notification['error'] = $this->translator->translate('menuUpdateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect(APPLICATION_URL . 'menu/index');
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
        $menuId = $this->_getParam('menuId');

        if (is_null($menuId)) {
            throw new Exception_InvalidParameter();
        }

        // retrieve current attribute information
        $menuDaoImpl     = new Dao_Menu();
        $menuDto         = $menuDaoImpl->getMenu($menuId);
        $this->elementId = $menuDto[Db_Menu::NAME];

        $this->view->menu   = $menuDto;
        $this->view->menuId = $menuId;
    }
}