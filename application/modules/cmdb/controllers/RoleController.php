<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class RoleController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/role_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/role_en.csv', 'en');
            parent::addUserTranslation('role');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function itemsperpageAction()
    {
        $itemCountPerPageSession                           = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['role'] = $this->_getParam('rowCount');
        $this->_redirect('role/index');
        exit;
    }

    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);

        $this->setupItemsCountPerPage('role');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->rolePage)) {
            $page = $pageSession->rolePage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                  = $this->_getParam('page');
                $pageSession->rolePage = $page;
            }
        } else {
            $page                  = $this->_getParam('page');
            $pageSession->rolePage = $page;
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
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'role/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }


        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $roleServiceGet = new Service_Role_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $roleList       = $roleServiceGet->getRoleList($page, $orderBy, $direction, $filter);

        $this->view->searchForm = $roleServiceGet->getFilterForm($filter);
        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $roleList['paginator'];
    }


    public function deleteAction()
    {
        $roleId = $this->_getParam('roleId');
        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" tries to delete Role "' . $roleId . '" ', Zend_Log::NOTICE);

        $notification = array();
        try {
            $roleServiceDelete = new Service_Role_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $statusCode        = $roleServiceDelete->deleteRole($roleId);
            if ($statusCode) {
                switch ($statusCode) {
                    case 1:
                        $notification['success'] = $this->translator->translate('roleDeleteSuccess');
                        break;
                    case 2:
                        $notification['success'] = $this->translator->translate('roleDeactivationSuccess');
                        break;
                    default:
                        $notification['error'] = $this->translator->translate('roleDeleteFailed');
                        break;
                }
            } else {
                $notification['error'] = $this->translator->translate('roleDeleteFailed');
            }

        } catch (Exception_Role_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deleting a Role "' . $roleId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('roleDeleteFailed');
        } catch (Exception_Role_DeleteFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to delete Role "' . $roleId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('roleDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('role/index');
    }

    public function activateAction()
    {
        $roleId = $this->_getParam('roleId');

        $notification = array();
        try {
            $roleServiceDelete       = new Service_Role_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $statusCode              = $roleServiceDelete->activateRole($roleId);
            $notification['success'] = $this->translator->translate('roleActivateSuccess');
        } catch (Exception_Role_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while activating Role ID "' . $roleId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('roleActivateFailed');
        } catch (Exception_Role_ActivateFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to activate Role ID "' . $roleId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('roleActivateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('role/index/');
    }


    public function createAction()
    {
        $this->logger->log('create Action page invoked', Zend_Log::DEBUG);
        $roleServiceCreate = new Service_Role_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $users             = $roleServiceCreate->getUsers();
        $attributes        = $roleServiceCreate->getAttributes();
        $form              = $roleServiceCreate->getCreateRoleForm($users, $attributes);
        $cloneFromId       = $this->_getParam('cloneFromId');

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $roleId                  = $roleServiceCreate->createRole($formData, parent::getUserInformation()->getId());
                    $notification['success'] = $this->translator->translate('roleInsertSuccess');
                } catch (Exception_Role_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new Role', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('roleInsertFailed');
                } catch (Exception_Role_InsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create Role. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('roleInsertFailed');
                }
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('role/index');
                exit;
            } else {
                $form->populate($formData);
            }
        } else if ($cloneFromId != null) {
            $roleServiceGet = new Service_Role_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $dbData         = $roleServiceGet->getRoleData($cloneFromId);
            $dbData['name'] = 'copy_of_' . $dbData['name'];
            $form->populate($dbData);
            $this->logger->log('Cloning RoleId:' . $cloneFromId, Zend_Log::INFO);
        }


        $this->view->attributes              = $attributes;
        $this->view->users                   = $users;
        $this->view->form                    = $form;
        $this->view->filterOptionsPermission = array(
            5 => $this->translator->translate('noFilter'),
            1 => $this->translator->translate('setFilterRandW'),
            4 => $this->translator->translate('setFilterRorW'),
            2 => $this->translator->translate('setFilterR'),
            3 => $this->translator->translate('setFilterX'));
        $this->view->defaultOptionPermission = 5;
        $this->view->filterOptionsUser       = array(
            0 => $this->translator->translate('setFilterHasRoleNot'),
            1 => $this->translator->translate('setFilterHasRole'),
            2 => $this->translator->translate('setFilterAllRoles'));
        $this->view->defaultOptionUser       = 2;
    }


    public function editAction()
    {
        $roleId          = $this->_getParam('roleId');
        $this->elementId = $roleId;
        $this->logger->log('update role "' . $roleId . '" Action page invoked', Zend_Log::DEBUG);
        $roleServiceUpdate = new Service_Role_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $users             = $roleServiceUpdate->getUsers();
        $attributes        = $roleServiceUpdate->getAttributes();
        $form              = $roleServiceUpdate->getUpdateRoleForm($users, $attributes);

        $roleServiceGet  = new Service_Role_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $dbData          = $roleServiceGet->getRoleData($roleId);
        $this->elementId = $dbData[Db_Role::NAME];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $roleServiceUpdate->updateRole($roleId, $formData, $dbData, parent::getUserInformation()->getId());
                    $notification['success'] = $this->translator->translate('roleUpdateSuccess');
                } catch (Exception_Role_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating Role "' . $roleId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('roleUpdateFailed');
                } catch (Exception_Role_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Role "' . $roleId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('roleUpdateFailed');
                } catch (Exception_Role_UpdateItemNotFound $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Role "' . $roleId . '". No items where updated!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('roleUpdateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('role/index');
                exit;
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($dbData);
        }

        // set to view
        $this->view->attributes              = $attributes;
        $this->view->users                   = $users;
        $this->view->form                    = $form;
        $this->view->filterOptionsPermission = array(
            5 => $this->translator->translate('noFilter'),
            1 => $this->translator->translate('setFilterRandW'),
            4 => $this->translator->translate('setFilterRorW'),
            2 => $this->translator->translate('setFilterR'),
            3 => $this->translator->translate('setFilterX'));
        $this->view->defaultOptionPermission = 4;
        $this->view->filterOptionsUser       = array(
            0 => $this->translator->translate('setFilterHasRoleNot'),
            1 => $this->translator->translate('setFilterHasRole'),
            2 => $this->translator->translate('setFilterAllRoles'));
        $this->view->defaultOptionUser       = 1;
    }

    public function detailAction()
    {
        $roleId = $this->_getParam('roleId');

        $roleServiceGet = new Service_Role_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $role           = $roleServiceGet->getRole($roleId);
        $userList       = $roleServiceGet->getUsers($roleId);
        $permissionList = $roleServiceGet->getPermissions($roleId);

        $this->view->role        = $role;
        $this->view->users       = $userList;
        $this->view->permissions = $permissionList;
    }


}