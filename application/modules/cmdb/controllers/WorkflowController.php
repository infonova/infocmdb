<?php
require_once 'AbstractAppAction.php';

/**
 * CONFIG ONLY!!
 *
 *
 */
class WorkflowController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/workflow_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/workflow_en.csv', 'en');
            parent::addUserTranslation('workflow');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function detailAction()
    {
        $workflowId = $this->_getParam('workflowId');
        $page       = $this->_getParam('page');
        $orderBy    = $this->_getParam('orderBy');
        $direction  = $this->_getParam('direction');
        $filter     = $this->_getParam('filter');

        if (!$workflowId) {
            throw new Exception_InvalidParameter();
        }

        $this->elementId = $workflowId;

        $workflowServiceGet = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result             = $workflowServiceGet->getWorkflowDetail($workflowId, $page, $orderBy, $direction, $filter);

        $workflow  = $result['workflow'];
        $paginator = $result['paginator'];

        $this->elementId = $workflow[Db_Workflow::NAME];

        $this->view->workflow  = $workflow;
        $this->view->paginator = $paginator;
    }

    public function itemsperpageAction()
    {
        $itemCountPerPageSession                               = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['workflow'] = $this->_getParam('rowCount');
        $this->_redirect('workflow/index');
        exit;
    }

    public function indexAction()
    {

        $this->setupItemsCountPerPage('workflow');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->workflowPage)) {
            $page = $pageSession->workflowPage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                      = $this->_getParam('page');
                $pageSession->workflowPage = $page;
            }
        } else {
            $page                      = $this->_getParam('page');
            $pageSession->workflowPage = $page;
        }

        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $workflowSearchColumns = array(
            Db_Workflow::NAME,
            Db_Workflow::DESCRIPTION,
            'trigger_type',
            Db_Workflow::EXECUTION_TIME,
            Db_Workflow::NOTE,
            Db_Workflow::IS_ASYNC,
            Db_Workflow::IS_ACTIVE,
        );

        $filterSession = new Zend_Session_Namespace('WorkflowFilter');

        $displayAttributeFilter = false;
        if ($this->_request->isPost()) {
            $formData              = $this->_request->getPost();
            $filterSession->search = $formData['search'];
            if ($formData['search'] == 'Filter' || $formData['search'] == '') {
                $attributeFilter = array();
                foreach ($workflowSearchColumns as $column) {
                    if ($formData[$column] != "") {
                        $attributeFilter[$column] = $formData[$column];
                        $displayAttributeFilter   = true;
                    }
                }
                $filterSession->attributeFilter = $attributeFilter;
            }
        } else {
            if ($filterSession->search == 'Filter' || $filterSession->search == '') {
                if (!empty($filterSession->attributeFilter)) {
                    $attributeFilter        = $filterSession->attributeFilter;
                    $displayAttributeFilter = true;
                }
            }
        }


        if ($displayAttributeFilter == false) {
            $filter = null;
            if ($this->_hasParam('search')) {
                if (!$this->_getParam('search')) {
                    $filterString = '';
                } else {
                    $filterString = '/filter/' . $this->_getParam('search') . '/';
                }
                $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'workflow/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
            } elseif ($this->_getParam('filter')) {
                $filter = str_replace('*', '%', $this->_getParam('filter'));

                if (!$filter || $filter == 'Filter' || $filter == '%') {
                    $filter = null;
                }
            }


            $filter = str_replace('"', '', $filter);
            $filter = str_replace("'", '', $filter);
        }

        $workflowServiceGet = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result             = $workflowServiceGet->getWorkflowList($page, $orderBy, $direction, $filter, $attributeFilter);

        $triggerTypes = array();
        foreach ($result as $item) {
            if ($item[Db_Workflow::TRIGGER_TIME]) {
                $trigger = 'time';
            } else if ($item[Db_Workflow::TRIGGER_CI] || $item[Db_Workflow::TRIGGER_CI_TYPE_CHANGE] || $item[Db_Workflow::TRIGGER_ATTRIBUTE] || $item[Db_Workflow::TRIGGER_RELATION] || $item[Db_Workflow::TRIGGER_PROJECT] || $item[Db_Workflow::TRIGGER_FILEIMPORT]) {
                $trigger = 'activity';
            } else {
                $trigger = 'manual';
            }
            $triggerTypes[$item[Db_Workflow::ID]] = $trigger;
        }


        $options = array(
            'columnOptions' => array(
                'trigger_type' => array(
                    'element_type'   => 'select',
                    'select_options' => array(
                        'activity' => 'activity', 'time' => 'time', 'manual' => 'manual',
                    ),
                ),
                'is_async'     => array(
                    'element_type'   => 'select',
                    'select_options' => array(
                        '1' => 'yes', '0' => 'no',
                    ),
                ),
                'is_active'    => array(
                    'element_type'   => 'select',
                    'select_options' => array(
                        '1' => 'active', '0' => 'inactive',
                    ),
                ),
            ),
        );


        $searchForm = $workflowServiceGet->getFilterForm($workflowSearchColumns, $filter, $options);

        if ($displayAttributeFilter == true) {
            $searchForm->populate($attributeFilter);
        }


        $this->view->columns                = $workflowSearchColumns;
        $this->view->triggerTypes           = $triggerTypes;
        $this->view->searchForm             = $searchForm;
        $this->view->displayAttributeFilter = $displayAttributeFilter;
        $this->view->page                   = $page;
        $this->view->filter                 = $filter;
        $this->view->orderBy                = $orderBy;
        $this->view->direction              = $direction;
        $this->view->paginator              = $result;

    }

    public function cronformAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $workflowId = $this->_getParam('workflowId');

        if ($workflowId) {
            $workflowDao = new Dao_Workflow();
            $workflow    = $workflowDao->getWorkflow($workflowId);
        }
        $form = new Form_Workflow_Cron($this->translator, $workflow[Db_Workflow::EXECUTION_TIME]);

        $this->view->form = $form;
    }

    public function mappingformAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $workflowId = $this->_getParam('workflowId');

        $form = new Form_Workflow_MappingSelection($this->translator);

        if ($workflowId) {
            $workflowServiceGet = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $workflow           = $workflowServiceGet->getWorkflowDetail($workflowId);
            $form->populate($workflow['workflow']);
        }

        $this->view->form = $form;
    }

    public function createAction()
    {

        $this->logger->log('create workflow Action page invoked', Zend_Log::DEBUG);
        $workflowServiceCreate = new Service_Workflow_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form                  = $workflowServiceCreate->getCreateWorkflowForm();

        $cronService = new Service_Cron_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $workflowId              = $workflowServiceCreate->createWorkflow($formData, parent::getUserInformation());
                    $notification['success'] = $this->translator->translate('workflowInsertSuccess');
                    parent::clearNavigationCache();
                } catch (Exception_Workflow_InsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create Workflow. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('workflowInsertFailed');
                } catch (Exception_Workflow_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new Workflow', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('workflowInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('workflow/detail/workflowId/' . $workflowId);
            } else {
                $form->populate($formData);
            }
        }

        $this->view->form = $form;
    }

    public function editAction()
    {
        $this->logger->log('edit workflow Action page invoked', Zend_Log::DEBUG);
        $workflowId      = $this->_getParam('workflowId');
        $this->elementId = $workflowId;

        $workflowServiceUpdate = new Service_Workflow_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form                  = $workflowServiceUpdate->getUpdateWorkflowForm();

        $workflowServiceGet  = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $workflow            = $workflowServiceGet->getWorkflowDetail($workflowId);
        $workflowStored      = $workflow['workflow'];
        $workflowType        = Util_Workflow_TypeFactory::create($workflowStored[Db_Workflow::SCRIPT_LANG], $workflowStored);
        $workflowTasksStored = $workflowServiceGet->getWorkflowTasksByWorkflowId($workflowId);
        $this->elementId     = $workflowStored[Db_Workflow::NAME];

        # var_dump($workflowStored, $workflowId, $workflowTasksStored);exit;#XXX


        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $workflowServiceUpdate->updateWorkflow($formData, $workflowId, $workflowTasksStored[0][Db_WorkflowTask::ID], parent::getUserInformation());
                    $notification['success'] = $this->translator->translate('workflowUpdateSuccess');
                    parent::clearNavigationCache();
                } catch (Exception_Workflow_InsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create Workflow. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('workflowInsertFailed');
                } catch (Exception_Workflow_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new Workflow', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('workflowInsertFailed');
                }
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('workflow/detail/workflowId/' . $workflowId);
                exit;
            } else {
                if ($workflowStored[Db_Workflow::TRIGGER_TIME]) {
                    $formData['trigger'] = 'time';
                } else {
                    $formData['trigger']                           = 'activity';
                    $formData[Db_Workflow::TRIGGER_CI]             = $workflowStored[Db_Workflow::TRIGGER_CI];
                    $formData[Db_Workflow::TRIGGER_CI_TYPE_CHANGE] = $workflowStored[Db_Workflow::TRIGGER_CI_TYPE_CHANGE];
                    $formData[Db_Workflow::TRIGGER_ATTRIBUTE]      = $workflowStored[Db_Workflow::TRIGGER_ATTRIBUTE];
                    $formData[Db_Workflow::TRIGGER_RELATION]       = $workflowStored[Db_Workflow::TRIGGER_RELATION];
                    $formData[Db_Workflow::TRIGGER_PROJECT]        = $workflowStored[Db_Workflow::TRIGGER_PROJECT];
                }

                $form->populate($formData);
            }
        } else {
            $formData                   = $workflowStored;
            $formData['user']           = $workflowStored[Db_Workflow::EXECUTE_USER_ID];
            $formData['asynch']         = $workflowStored[Db_Workflow::IS_ASYNC];
            $formData['active']         = $workflowStored[Db_Workflow::IS_ACTIVE];
            $formData['scriptname']     = $workflowTasksStored[0][Db_WorkflowTask::SCRIPTNAME];
            $formData['script']         = $workflowType->getScriptContent();
            $formData['script_test']    = $workflowType->getTestContent();
            $formData['responseFormat'] = $workflowStored[Db_Workflow::RESPONSE_FORMAT];

            //var_dump($formData,$workflowTasksStored); exit; #XXX

            if ($workflowStored[Db_Workflow::TRIGGER_TIME]) {
                $formData['trigger'] = 'time';
            } else if (
                $workflowStored[Db_Workflow::TRIGGER_CI] ||
                $workflowStored[Db_Workflow::TRIGGER_CI_TYPE_CHANGE] ||
                $workflowStored[Db_Workflow::TRIGGER_ATTRIBUTE] ||
                $workflowStored[Db_Workflow::TRIGGER_RELATION] ||
                $workflowStored[Db_Workflow::TRIGGER_PROJECT] ||
                $workflowStored[Db_Workflow::TRIGGER_FILEIMPORT]
            ) {
                $formData['trigger'] = 'activity';
            } else {
                $formData['trigger'] = 'manual';
            }

            $this->view->triggerCi           = $workflowStored[Db_Workflow::TRIGGER_CI];
            $this->view->triggerCiTypeChange = $workflowStored[Db_Workflow::TRIGGER_CI_TYPE_CHANGE];
            $this->view->triggerAttribute    = $workflowStored[Db_Workflow::TRIGGER_ATTRIBUTE];
            $this->view->triggerRelation     = $workflowStored[Db_Workflow::TRIGGER_RELATION];
            $this->view->triggerProject      = $workflowStored[Db_Workflow::TRIGGER_PROJECT];
            $this->view->triggerFileimport   = $workflowStored[Db_Workflow::TRIGGER_FILEIMPORT];

            $form->populate($formData);
        }

        $this->view->trigger    = $formData['trigger'];
        $this->view->workflowId = $workflowId;
        $this->view->form       = $form;
    }

    public function deleteAction()
    {
        $workflowId = $page = $this->_getParam('workflowId');

        if (!$workflowId) {
            throw new Exception_InvalidParameter();
        }

        $workflowServiceDelete = new Service_Workflow_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $statusCode            = $workflowServiceDelete->deleteWorkflow($workflowId);
        $notification          = array();
        if ($statusCode) {
            switch ($statusCode) {
                case 1:
                    $notification['success'] = $this->translator->translate('workflowDeleteSuccess');
                    break;
                case 2:
                    $notification['success'] = $this->translator->translate('workflowDeactivationSuccess');
                    break;
                default:
                    $notification['error'] = $this->translator->translate('workflowDeleteFailed');
                    break;
            }
        } else {
            $notification['error'] = $this->translator->translate('workflowDeleteFailed');
        }
        $this->_helper->FlashMessenger($notification);
        $this->_redirect('workflow/index');
    }

    public function activateAction()
    {
        $workflowId = $this->_getParam('workflowId');

        $notification = array();
        try {
            $workflowActivation      = new Service_Workflow_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $activate                = $workflowActivation->activateWorkflow($workflowId);
            $notification['success'] = $this->translator->translate('workflowActivateSuccess');
        } catch (Exception_Workflow_InsertFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to activate Workflow.', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('workflowInsertFailed');
        } catch (Exception_Workflow_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while activating Workflow', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('workflowInsertFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('workflow/index/');
    }

    public function executeAction()
    {
        $workflowId   = $this->_getParam('workflowId');
        $notification = array();

        if (is_null($workflowId)) {
            throw new Exception_InvalidParameter();
        }

        $util   = new Util_Workflow($this->logger);
        $status = $util->startWorkflow($workflowId, parent::getUserInformation()->getId(), array('triggerType' => 'manual'));

        $notification = array();
        if ($status) {
            $notification['success'] = $this->translator->translate('workflowStartSuccess');
        } else {
            $notification['error'] = $this->translator->translate('workflowStartFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('workflow/detail/workflowId/' . $workflowId);

    }

    public function retryAction()
    {

        $instanceId = $this->_getParam('instanceId');

        if (is_null($instanceId)) {
            throw new Exception_InvalidParameter();
        }

        $workflowServiceGet = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result             = $workflowServiceGet->getWorkflowInstanceDetail($instanceId);

        $instance      = $result['instance'];
        $workflowId    = $instance[Db_WorkflowCase::WORKFLOW_ID];
        $contextAsJSON = $instance[Db_WorkflowCase::CONTEXT];
        $decodeContext = json_decode($contextAsJSON, true);

        $util           = new Util_Workflow($this->logger);
        $statusWorkflow = $util->startWorkflow($workflowId, parent::getUserInformation()->getId(), $decodeContext, true);

        $notification = array();
        if ($statusWorkflow) {
            $notification['success'] = $this->translator->translate('workflowStartSuccess');
        } else {
            $notification['error'] = $this->translator->translate('workflowStartFailed');
        }
        $this->_helper->FlashMessenger($notification);
        $this->_redirect('workflow/detail/workflowId/' . $workflowId);

    }

    public function instanceAction()
    {
        $instanceId = $this->_getParam('instanceId');

        if (is_null($instanceId)) {
            throw new Exception_InvalidParameter();
        }

        $this->elementId = $instanceId;

        $workflowServiceGet = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result             = $workflowServiceGet->getWorkflowInstanceDetail($instanceId);

        $instance     = $result['instance'];
        $instanceLogs = $result['instanceLogs'];

        $this->view->instanceId   = $instanceId;
        $this->view->instance     = $instance;
        $this->view->instanceLogs = $instanceLogs;
        $this->view->workflowId   = $instance[Db_WorkflowCase::WORKFLOW_ID];

        $this->view->currentPositionType = $result['positionType'];
        $this->view->currentPosition     = $result['position'];
        $this->view->currentPositionId   = $result['positionId'];

        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/jsonviewer/jquery.json-viewer.min.js');
        $this->view->headLink()->appendStylesheet(APPLICATION_URL . 'js/jsonviewer/jquery.json-viewer.css');

        $workflow_item = $workflowServiceGet->getWorkflowItemByInstanceId($instance['id']);


        $this->view->arg_context = $workflow_item[Db_WorkflowItem::WORKFLOW_ARG_CONTEXT];
        // TODO: implement me!
        // zeigt eine spezifische WF instanz.
        // log-messages für die instanz anzeigen
        // aktuellen status / atktuellen Step anzeigen
        // abbrechen-button!!
    }


    public function transitiondetailAction()
    {
        $transitionId = $this->_getParam('transitionId');

        if (is_null($transitionId)) {
            throw new Exception_InvalidParameter();
        }

        $workflowServiceGet = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result             = $workflowServiceGet->getWorkflowTransitionDetail($transitionId);

        $this->view->task       = $result['task'];
        $this->view->transition = $result['transition'];
        $this->workflowId       = $result['workflowId'];
    }


    public function placedetailAction()
    {
        $placeId = $this->_getParam('placeId');

        if (is_null($placeId)) {
            throw new Exception_InvalidParameter();
        }

        $workflowServiceGet = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result             = $workflowServiceGet->getWorkflowPlaceDetail($placeId);

        $this->view->place = $result['place'];
        $this->workflowId  = $result['workflowId'];
    }


    // TODO: implement me / refactor me
    public function visworkflowAction()
    {
        $workflowId = $this->_getParam('workflowId');


        $workflowDaoImpl = new Dao_Workflow();
        $workflow        = $workflowDaoImpl->getWorkflow($workflowId);

        $start = $workflowDaoImpl->getStartWorkflowStep($workflowId);


        $result = array();
        array_push($result, array('type' => 'place', 'id' => $start[Db_WorkflowPlace::ID], 'value' => $start[Db_WorkflowPlace::NAME]));


        // handle remaining steps
        $hasMore      = true;
        $currentPlace = $start[Db_WorkflowPlace::ID];
        while ($hasMore) {

            $arcs = $workflowDaoImpl->getNextArcs($workflowId, $currentPlace);

            if (!$arcs) {
                $hasMore = false;
                break;
            }

            $transitionId = null;
            foreach ($arcs as $arc) {
                $transitionId = $arc[Db_WorkflowArc::WORKFLOW_TRANSITION_ID];
                array_push($result, array('type' => 'arc', 'value' => "\/"));
            }

            $transition = $workflowDaoImpl->getTransition($transitionId);
            array_push($result, array('type' => 'transition', 'id' => $transition[Db_WorkflowTransition::ID], 'value' => $transition[Db_WorkflowTransition::NAME]));

            $arcs = $workflowDaoImpl->getTargetArcs($workflowId, $transitionId);
            foreach ($arcs as $arc) {
                $placeId = $arc[Db_WorkflowArc::WORKFLOW_PLACE_ID];
                array_push($result, array('type' => 'arc', 'value' => "\/"));
            }

            $place = $workflowDaoImpl->getWorkflowPlace($placeId);
            array_push($result, array('type' => 'place', 'id' => $place[Db_WorkflowPlace::ID], 'value' => $place[Db_WorkflowPlace::NAME]));
            $currentPlace = $placeId;
//			getWorkflowPlace($placeId)
//			getTargetArcs($workflowId, $transitionId)
        }


//		$end = $workflowDaoImpl->getEndWorkflowStep($workflowId);
//		array_push($result, $end[Db_WorkflowPlace::NAME]);

        $this->view->workflowId = $workflowId;
        $this->view->result     = $result;
    }


    public function createvisimageAction()
    {
        header("Content-type: image/png");
        $id   = $this->_getParam('id');
        $type = $this->_getParam('type'); // instance/workflow

        $workflowServiceGet = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $image              = $workflowServiceGet->getWorkflowImage($id, $type);
        $image->render();
        exit;
    }

    public function suspendAction()
    {
        // pause workflow instance..?
        $workflowInstanceId = $this->_getParam('instanceId');

        if (is_null($workflowInstanceId)) {
            throw new Exception_InvalidParameter();
        }

        $notification = array();
        try {
            $util   = new Util_Workflow($this->logger);
            $status = $util->suspendWorkflow($workflowInstanceId, parent::getUserInformation()->getId());

            if ($status) {
                $notification['success'] = $this->translator->translate('suspendWorkflowSuccess');
            } else {
                $notification['error'] = $this->translator->translate('suspendWorkflowFailed');
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            $notification['error'] = $this->translator->translate('suspendWorkflowFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('workflow/instance/instanceId/' . $workflowInstanceId);
    }


    public function cancelAction()
    {
        $workflowInstanceId = $this->_getParam('instanceId');

        if (is_null($workflowInstanceId)) {
            throw new Exception_InvalidParameter();
        }

        $notification = array();
        try {
            $util   = new Util_Workflow($this->logger);
            $status = $util->cancelWorkflow($workflowInstanceId, parent::getUserInformation()->getId());

            if ($status) {
                $notification['success'] = $this->translator->translate('continueWorkflowSuccess');
            } else {
                $notification['error'] = $this->translator->translate('continueWorkflowFailed');
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            $notification['error'] = $this->translator->translate('continueWorkflowFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('workflow/instance/instanceId/' . $workflowInstanceId);
    }


    /**
     *
     * to continue a USER_Type transition
     */
    public function continueAction()
    {
        $workflowInstanceId   = $this->_getParam('instanceId');
        $workflowTransitionId = $this->_getParam('transitionId');

        if (is_null($workflowInstanceId)) {
            throw new Exception_InvalidParameter();
        }

        $notification = array();
        try {
            $util   = new Util_Workflow($this->logger);
            $status = $util->continueWorkflow($workflowInstanceId, $workflowTransitionId, parent::getUserInformation()->getId());

            if ($status) {
                $notification['success'] = $this->translator->translate('continueWorkflowSuccess');
            } else {
                $notification['error'] = $this->translator->translate('continueWorkflowFailed');
            }
        } catch (Exception_AccessDenied $e) {
            $notification['error'] = $this->translator->translate('continueWorkflowUserPermissionDenied');
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            $notification['error'] = $this->translator->translate('continueWorkflowFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('workflow/instance/instanceId/' . $workflowInstanceId);
    }

    /**
     *
     * continue suspended workflow
     */
    public function wakeupAction()
    {
        $workflowInstanceId = $this->_getParam('instanceId');

        if (is_null($workflowInstanceId)) {
            throw new Exception_InvalidParameter();
        }

        $notification = array();
        try {
            $util   = new Util_Workflow($this->logger);
            $status = $util->wakeupWorkflow($workflowInstanceId, parent::getUserInformation()->getId());

            if ($status) {
                $notification['success'] = $this->translator->translate('wakeupWorkflowSuccess');
            } else {
                $notification['error'] = $this->translator->translate('wakeupWorkflowFailed');
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            $notification['error'] = $this->translator->translate('wakeupWorkflowFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('workflow/instance/instanceId/' . $workflowInstanceId);
    }

    public function mappingAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $workflowId = $this->_getParam('workflowId');
        $type       = $this->_getParam('type');

        if (is_null($type)) {
            throw new Exception_InvalidParameter();
        }
        $workflowServiceGet = new Service_Workflow_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $mapping            = $workflowServiceGet->getPossibleMappings($type);

        $form = new Form_Workflow_Mapping($this->translator, $mapping, $type, $config);

        if ($workflowId) {
            $storedMapping = $workflowServiceGet->getTriggerMappings($workflowId, $type);
            $formValues    = array();
            foreach ($storedMapping as $create) {
                if ($create[Db_WorkflowTrigger::METHOD] == 'create')
                    $formValues[$type . '__' . $create[Db_WorkflowTrigger::MAPPING_ID]][Db_WorkflowTrigger::METHOD_KEY_CREATE] = $create[Db_WorkflowTrigger::METHOD];
                if ($create[Db_WorkflowTrigger::METHOD] == 'update')
                    $formValues[$type . '__' . $create[Db_WorkflowTrigger::MAPPING_ID]][Db_WorkflowTrigger::METHOD_KEY_UPDATE] = $create[Db_WorkflowTrigger::METHOD];
                if ($create[Db_WorkflowTrigger::METHOD] == 'delete')
                    $formValues[$type . '__' . $create[Db_WorkflowTrigger::MAPPING_ID]][Db_WorkflowTrigger::METHOD_KEY_DELETE] = $create[Db_WorkflowTrigger::METHOD];
            }
            $form->populate($formValues);
        }

        $this->view->type             = $type;
        $this->view->form             = $form;
        $this->view->possibleMappings = $mapping;
    }

    public function fileimporttriggermappingAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $workflow_id = $this->_getParam('workflowId');

        $workflow_dao    = new Dao_Workflow();
        $file_import_dao = new Dao_Import();
        $file_imports    = $file_import_dao->getFileImportNamesForRegExCheck();

        if (isset($workflow_id)) {
            $file_import_trigger = $workflow_dao->getWorkflowFileimportTriggerInfoByWorkflowId($workflow_id);
        }

        $this->view->file_imports = json_encode($file_imports);
        if (isset($file_import_trigger) && !is_null($file_import_trigger)) {
            $this->view->current_method = $file_import_trigger[Db_WorkflowTrigger::METHOD];
            $this->view->current_regex  = $file_import_trigger[Db_WorkflowTrigger::FILEIMPORT_REGEX];
        }
        if (!isset($this->view->current_regex)) {
            $this->view->current_regex = "[]";
        }
    }

    public function scripttemplateAction()
    {
        $lang         = $this->_getParam('lang');
        $type         = $this->_getParam('type');
        $workflowType = Util_Workflow_TypeFactory::create($lang);
        if ($type === 'script') {
            $template = $workflowType->getTemplate();
        } else {
            $template = $workflowType->getTestTemplate();
        }

        echo $template;
        exit;
    }

    public function validatescriptAction()
    {
        $lang       = $this->_getParam('lang');
        $script     = $this->_getParam('script');
        $testScript = $this->_getParam('script_test');

        $worklowType = Util_Workflow_TypeFactory::create($lang);

        $output  = array();
        $success = $worklowType->validate($script, $testScript, $output);

        $result = array(
            'success' => $success,
            'output'  => $output
        );

        echo json_encode($result);
        exit;
    }

    public function rebuildAction()
    {
        $allWorkflows = $this->_getParam('all', 0);
        $workflowId   = $this->_getParam('id', array());

        $daoWorkflow = new Dao_Workflow();
        $workflows   = array();

        if ($allWorkflows == 1) {
            $workflows = $daoWorkflow->getActiveWorkflows();
        } elseif (!empty($workflowId)) {
            $workflows[] = $daoWorkflow->getWorkflow($workflowId);
        }

        foreach ($workflows as $index => $workflow) {
            $workflowType = Util_Workflow_TypeFactory::create($workflow[Db_Workflow::SCRIPT_LANG], $workflow);
            if (method_exists($workflowType, 'build')) {
                $result = $workflowType->build();
                if ($result === true) {
                    $workflows[$index]['build_status'] = 'OK';
                } else {
                    $workflows[$index]['build_status'] = 'FAILED';
                }
            } else {
                $workflows[$index]['build_status'] = 'IGNORED';
            }
        }

        if ($allWorkflows == 0 && isset($workflows[0])) {
            $workflow     = $workflows[0];
            $notification = array();

            if ($workflow['build_status'] !== 'FAILED') {
                $notification['success'] = $this->translator->translate('workflowBuildSuccess');
            } else {
                $notification['error'] = $this->translator->translate('workflowBuildFailed');
            }

            $this->_helper->FlashMessenger($notification);
            $this->_redirect('workflow/index');
        }

        $this->view->workflows = $workflows;
    }

    public function solvecaseAction()
    {
        $id      = $this->_getParam('id');
        $referer = $this->getRequest()->getServer('HTTP_REFERER');

        if (!$id) {
            throw new Exception_InvalidParameter();
        }

        $workflowDao = new Dao_Workflow();
        $notification = array();
        $updateData = array(
            Db_WorkflowCase::SOLVE_STATUS => 2,
        );

        try {
            $workflowDao->updateWorkflowCase($updateData, $id);
            $notification['success'] = $this->translator->translate('workflowUpdateSuccess');
        } catch (Exception $e) {
            $this->logger->log($e, ZEND_LOG::WARN);
            $notification['error'] = $this->translator->translate('workflowUpdateFailed');
        }

        $this->_helper->FlashMessenger($notification);

        $this->_redirect($referer);
    }

    public function solveAction()
    {
        $id      = $this->_getParam('workflowId');
        $referer = $this->getRequest()->getServer('HTTP_REFERER');

        if (!$id) {
            throw new Exception_InvalidParameter();
        }

        $workflowDao = new Dao_Workflow();
        $notification = array();

        try {
            $workflowDao->updateWorkflowSolveStatus($id, 2);
            $notification['success'] = $this->translator->translate('workflowUpdateSuccess');
        } catch(Exception $e) {
            $this->logger->log($e, ZEND_LOG::WARN);
            $notification['error'] = $this->translator->translate('workflowUpdateFailed');
        }

        $this->_helper->FlashMessenger($notification);

        $this->_redirect($referer);
    }
}