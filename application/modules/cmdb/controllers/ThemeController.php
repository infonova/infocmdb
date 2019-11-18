<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class ThemeController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/theme_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/theme_en.csv', 'en');
            parent::addUserTranslation('theme');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function itemsperpageAction()
    {
        $itemCountPerPageSession                            = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['theme'] = $this->_getParam('rowCount');
        $this->_redirect('theme/index');
        exit;
    }

    public function indexAction()
    {
        $this->logger->log('Theme index action has been invoked', Zend_Log::DEBUG);

        $this->setupItemsCountPerPage('theme');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->themePage)) {
            $page = $pageSession->themePage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                   = $this->_getParam('page');
                $pageSession->themePage = $page;
            }
        } else {
            $page                   = $this->_getParam('page');
            $pageSession->themePage = $page;
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
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'theme/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $themeServiceGet = new Service_Theme_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator       = $themeServiceGet->getThemeList($page, $orderBy, $direction, $filter);

        $this->view->searchForm = $themeServiceGet->getFilterForm($filter);
        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $paginator;
    }


    public function createAction()
    {
        $this->logger->log('create attributeGroup Action page invoked', Zend_Log::DEBUG);

        $themeServiceCreate = new Service_Theme_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $res                = $themeServiceCreate->getCreateThemeForm();
        $form               = $res['form'];
        $menuList           = $res['menuList'];
        $cloneFromId        = $this->_getParam('cloneFromId');


        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $theme                                = array();
                $theme[Db_Theme::NAME]                = $formData['name'];
                $theme[Db_Theme::DESCRIPTION]         = $formData['description'];
                $theme[Db_Theme::MENU_ID]             = $formData['startpage'];
                $theme[Db_Theme::NOTE]                = $formData['note'];
                $theme[Db_Theme::IS_ACTIVE]           = '1';
                $theme[Db_Theme::IS_WILDCARD_ENABLED] = '1';

                $createMenuList = array();
                foreach ($menuList as $menu) {
                    if ($formData[$menu[Db_Menu::ID]])
                        array_push($createMenuList, $menu[Db_Menu::ID]);
                }

                $notification = array();
                try {
                    $themeServiceCreate->createTheme($theme, $createMenuList);
                    parent::clearNavigationCache();
                    $notification['success'] = $this->translator->translate('themeInsertSuccess');
                } catch (Exception_Theme_InsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create Theme. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('themeInsertFailed');
                } catch (Exception_Theme_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new Theme', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('themeInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('theme/index');
                exit;
            } else {
                $form->populate($formData);
            }
        } else if ($cloneFromId != null) {
            $themeServiceGet = new Service_Theme_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $theme           = $themeServiceGet->getTheme($cloneFromId);

            $dbData['name']        = 'copy_of_' . $theme[Db_Theme::NAME];
            $dbData['description'] = $theme[Db_Theme::DESCRIPTION];
            $dbData['valid']       = $theme[Db_Theme::IS_ACTIVE];
            $dbData['note']        = $theme[Db_Theme::NOTE];
            $dbData['wildcard']    = '1';
            $dbData['startpage']   = $theme[Db_Theme::MENU_ID];

            $themeMenuList = $themeServiceGet->getThemeMenus($cloneFromId);
            foreach ($themeMenuList as $tmlist) {
                $dbData[$tmlist[Db_ThemeMenu::MENU_ID]] = 1;
            }

            $form->populate($dbData);
            $this->logger->log('Cloning ThemeId:' . $cloneFromId, Zend_Log::INFO);
        }

        $this->view->form     = $form;
        $this->view->menuList = $menuList;
    }

    public function detailAction()
    {
        $themeId = $this->_getParam('themeId');

        $themeServiceGet = new Service_Theme_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $theme           = $themeServiceGet->getExtendedTheme($themeId);
        $menus           = $themeServiceGet->getCurrentMenus($themeId);

        $this->elementId = $theme[Db_Theme::NAME];

        $this->view->theme = $theme;
        $this->view->menus = $menus;
    }


    public function editAction()
    {
        $themeId         = $this->_getParam('themeId');
        $this->elementId = $themeId;

        $themeServiceUpdate = new Service_Theme_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $res                = $themeServiceUpdate->getUpdateThemeForm($themeId);
        $form               = $res['form'];
        $menuList           = $res['menuList'];

        // the information stored in db
        $dbFormData      = array();
        $themeServiceGet = new Service_Theme_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $theme           = $themeServiceGet->getTheme($themeId);
        $this->elementId = $theme[Db_Theme::NAME];

        // get form data
        $dbFormData['name']        = $theme[Db_Theme::NAME];
        $dbFormData['description'] = $theme[Db_Theme::DESCRIPTION];
        $dbFormData['valid']       = $theme[Db_Theme::IS_ACTIVE];
        $dbFormData['note']        = $theme[Db_Theme::NOTE];
        $dbFormData['wildcard']    = '1';
        $dbFormData['startpage']   = $theme[Db_Theme::MENU_ID];

        $themeMenuList = $themeServiceGet->getThemeMenus($themeId);


        foreach ($themeMenuList as $tmlist) {
            $dbFormData[$tmlist[Db_ThemeMenu::MENU_ID]] = 1;
        }

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $theme                                = array();
                $theme[Db_Theme::NAME]                = $formData['name'];
                $theme[Db_Theme::DESCRIPTION]         = $formData['description'];
                $theme[Db_Theme::MENU_ID]             = $formData['startpage'];
                $theme[Db_Theme::NOTE]                = $formData['note'];
                $theme[Db_Theme::IS_ACTIVE]           = '1';
                $theme[Db_Theme::IS_WILDCARD_ENABLED] = '1';

                $createMenuList = array();
                foreach ($menuList as $menu) {
                    if ($formData[$menu[Db_Menu::ID]])
                        array_push($createMenuList, $menu[Db_Menu::ID]);
                }

                $notification = array();
                try {
                    $themeServiceUpdate->updateTheme($themeId, $theme, $createMenuList);
                    parent::clearNavigationCache();
                    $notification['success'] = $this->translator->translate('themeUpdateSuccess');
                } catch (Exception_Theme_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating Theme "' . $attributeGroupId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('themeInsertFailed');
                } catch (Exception_Theme_UpdateItemNotFound $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Theme "' . $attributeGroupId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('themeInsertFailed');
                } catch (Exception_Theme_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new Theme', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('themeInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('theme/index');
                exit;
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($dbFormData);
        }

        $this->view->form     = $form;
        $this->view->menuList = $menuList;
    }


    public function deleteAction()
    {
        $themeId = $this->_getParam('themeId');
        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" tries to delete Theme "' . $themeId . '" ', Zend_Log::NOTICE);

        $notification = array();
        try {
            $themeServiceDelete = new Service_Theme_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $statusCode         = $themeServiceDelete->deleteTheme($themeId);
            if ($statusCode) {
                switch ($statusCode) {
                    case 1:
                        $notification['success'] = $this->translator->translate('themeDeleteSuccess');
                        break;
                    case 2:
                        $notification['success'] = $this->translator->translate('themeDeactivationSuccess');
                        break;
                    default:
                        $notification['error'] = $this->translator->translate('themeDeleteFailed');
                        break;
                }
            } else {
                $notification['error'] = $this->translator->translate('themeDeleteFailed');
            }
        } catch (Exception_Theme_DeleteFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to delete Theme "' . $themeId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('themeDeleteFailed');
        } catch (Exception_Theme_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deleting a Theme "' . $themeId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('themeDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('theme');
    }

    public function activateAction()
    {
        $themeId = $this->_getParam('themeId');

        $notification = array();
        try {
            $themeServiceDelete = new Service_Theme_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $themeServiceDelete->activateTheme($themeId);
            $notification['success'] = $this->translator->translate('themeActivateSuccess');
        } catch (Exception_Theme_ActivateFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to activate Theme "' . $themeId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('themeDeleteFailed');
        } catch (Exception_Theme_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while activating a Theme "' . $themeId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('themeDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('theme/index/');
    }


}