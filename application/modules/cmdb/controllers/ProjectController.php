<?php
require_once 'AbstractAppAction.php';


/**
 *
 *
 *
 */
class ProjectController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/project_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/project_en.csv', 'en');
            parent::addUserTranslation('project');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function itemsperpageAction()
    {
        $itemCountPerPageSession                              = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['project'] = $this->_getParam('rowCount');
        $this->_redirect('project/index');
        exit;
    }

    public function indexAction()
    {
        $this->logger->log('Project index action has been invoked', Zend_Log::DEBUG);

        $this->setupItemsCountPerPage('project');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->projectPage)) {
            $page = $pageSession->projectPage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                     = $this->_getParam('page');
                $pageSession->projectPage = $page;
            }
        } else {
            $page                     = $this->_getParam('page');
            $pageSession->projectPage = $page;
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
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'project/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $projectServiceGet = new Service_Project_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $projectList       = $projectServiceGet->getProjectList($page, $orderBy, $direction, $filter);

        $this->view->searchForm = $projectServiceGet->getFilterForm($filter);
        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $projectList['paginator'];
    }


    public function createAction()
    {
        $this->logger->log('create project Action page invoked', Zend_Log::DEBUG);
        $projectServiceCreate = new Service_Project_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $users                = $projectServiceCreate->getUsers();
        $form                 = $projectServiceCreate->getCreateProjectForm($users);

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $projectServiceCreate->createProject($formData, parent::getUserInformation()->getId());
                    $notification['success'] = $this->translator->translate('projectInsertSuccess');
                    parent::clearNavigationCache();
                    parent::clearProjectCache();
                } catch (Exception_Project_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new Project', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('projectInsertFailed');
                } catch (Exception_Project_InsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create Project. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('projectInsertFailed');
                } catch (Exception_Project_UserInsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create User-Mapping. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('projectInsertFailed');
                }
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('project/index');
                exit;
            } else {
                $form->populate($formData);
            }
        }

        $this->view->users = $users;
        $this->view->form  = $form;
    }


    /**
     * edits the selected project
     *
     * @return unknown_type
     */
    public function editAction()
    {
        $projectId       = $this->_getParam('projectId');
        $this->elementId = $projectId;
        $this->logger->log('update project "' . $projectId . '" Action page invoked', Zend_Log::DEBUG);
        $projectServiceUpdate = new Service_Project_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $users                = $projectServiceUpdate->getUsers();
        $form                 = $projectServiceUpdate->getUpdateProjectForm($users);

        $projectServiceGet = new Service_Project_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $dbFormData        = $projectServiceGet->getProjectData($projectId);
        $this->elementId   = $dbFormData[Db_Project::NAME];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData, array('projectid' => $projectId))) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);
                $formValues = $form->getValues();

                $notification = array();
                try {
                    $check = $projectServiceUpdate->updateProject($projectId, $formValues, $dbFormData);
                    if ($check) $notification['success'] = $this->translator->translate('projectUpdateSuccess');
                    parent::clearNavigationCache();
                    parent::clearProjectCache();
                } catch (Exception_Project_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Project "' . $projectId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('projectUpdateFailed');
                } catch (Exception_Project_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating Project "' . $projectId . '" ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('projectUpdateFailed');
                } catch (Exception_Project_UpdateItemNotFound $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Project "' . $projectId . '". No items where updated!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('projectUpdateFailed');
                }
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('project/index');

                exit;
            } else {
                $form->populate($formData);
            }
        } else {

            $form->populate($dbFormData);
        }

        // set to view
        $this->view->users = $users;
        $this->view->form  = $form;
    }

    public function deleteAction()
    {
        $projectId = $this->_getParam('projectId');
        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" tries to delete Project "' . $projectId . '" ', Zend_Log::NOTICE);

        $notification = array();
        try {
            $projectServiceDelete = new Service_Project_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $statusCode           = $projectServiceDelete->deleteProject($projectId);
            if ($statusCode) {
                switch ($statusCode) {
                    case 1:
                        $notification['success'] = $this->translator->translate('projectDeleteSuccess');
                        break;
                    case 2:
                        $notification['success'] = $this->translator->translate('projectDeactivationSuccess');
                        break;
                    default:
                        $notification['error'] = $this->translator->translate('projectDeleteFailed');
                        break;
                }
            } else {
                $notification['error'] = $this->translator->translate('projectDeleteFailed');
            }
            parent::clearNavigationCache();
            parent::clearProjectCache();
        } catch (Exception_Project_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deleting a Project "' . $projectId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('projectDeleteFailed');
        } catch (Exception_Project_DeleteFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to delete Project "' . $projectId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('projectDeleteFailed');
        }


        $this->_helper->FlashMessenger($notification);
        $this->_redirect('project/index');
    }


    public function activateAction()
    {
        $projectId = $this->_getParam('projectId');

        $notification = array();
        try {
            $projectServiceDelete    = new Service_Project_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $statusCode              = $projectServiceDelete->activateProject($projectId);
            $notification['success'] = $this->translator->translate('projectActivateSuccess');
        } catch (Exception_Project_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deleting a Project "' . $projectId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('projectDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('project/index/');
    }


    /**
     * changes the selected project. If parameter is not set, is null or is 0, All projects are displayed
     *
     * @return unknown_type
     */
    public function changeAction()
    {
        $project = $this->_getParam('projectid');
        $ciType  = $this->_getParam('typeId');
        $ciId    = $this->_getParam('ciid');

        if (is_null($project) || $project <= 0) {
            $this->logger->log('project var was null. using default value 0 for project display', Zend_Log::DEBUG);
            $project = null;
        }

        parent::storeCurrentProjectId($project);
        parent::clearNavigationCache();

        if ($ciId) {
            $this->_redirect('ci/detail/ciid/' . $ciId);
        } else if ($ciType) {
            $this->_redirect('ci/index/typeid/' . $ciType);
        } else {
            $this->_redirect('index');
        }
    }
}