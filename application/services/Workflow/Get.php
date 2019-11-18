<?php

/**
 *
 *
 *
 */
class Service_Workflow_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3601, $themeId);
    }

    public function getWorkflowDetail($id, $page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $workflowDaoImpl = new Dao_Workflow();
        $workflow        = $workflowDaoImpl->getWorkflow($id);

        // get List of instances
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/workflow.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $select = $workflowDaoImpl->getWorkflowInstanceForPagination($id, $orderBy, $direction, $filter);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return array(
            'workflow'  => $workflow,
            'paginator' => $paginator,
        );
    }


    public function getWorkflowInstanceDetail($instanceId)
    {
        $workflowDaoImpl = new Dao_Workflow();

        $instance     = $workflowDaoImpl->getWorkflowInstance($instanceId);
        $instanceLogs = $workflowDaoImpl->getWorkflowLogsForInstance($instanceId);
        $workflowId   = $instance[Db_WorkflowCase::WORKFLOW_ID];

        $positionId   = null;
        $positionType = null;
        $position     = null;

        // if CLOSED -> end place return
        if ($instance[Db_WorkflowCase::STATUS] == Dao_Workflow::WORKFLOW_INS_STATUS_CLOSED) {
            $endPlace     = $workflowDaoImpl->getEndWorkflowStep($workflowId);
            $position     = $endPlace[Db_WorkflowPlace::NAME];
            $positionType = 'place';
            $positionId   = $endPlace[Db_WorkflowPlace::ID];
        } else {
            // get items
            // wenn offene Items -> Transition -> return
            $openItems = $workflowDaoImpl->getActiveTransitionsViaItem($instanceId);

            $position = null;
            if ($openItems && count($openItems) > 0) {
                foreach ($openItems as $item) {
                    $positionType = 'transition';
                    $position     = $item[Db_WorkflowTransition::NAME];
                    $positionId   = $item[Db_WorkflowTransition::ID];
                }
            } else {
                // no items-> no transition 
                // get token
                $openTokens = $workflowDaoImpl->getActivePlacesViaToken($instanceId);
                $position   = null;
                if ($openTokens && count($openTokens) > 0) {
                    foreach ($openTokens as $token) {
                        $positionType = 'place';
                        $position     = $token[Db_WorkflowPlace::NAME];
                        $positionId   = $token[Db_WorkflowPlace::ID];
                    }
                } else {
                    // wenn keine token -> START PLACE
                    $startPlace   = $workflowDaoImpl->getStartWorkflowStep($workflowId);
                    $position     = $startPlace[Db_WorkflowPlace::NAME];
                    $positionId   = $startPlace[Db_WorkflowPlace::ID];
                    $positionType = 'place';
                }
            }
        }

        return array(
            'instance'     => $instance,
            'instanceLogs' => $instanceLogs,
            'positionType' => $positionType,
            'position'     => $position,
            'positionId'   => $positionId,
        );
    }

    public function getWorkflowTasksByWorkflowId($workflowId)
    {
        $workflowDaoImpl = new Dao_Workflow();
        return $workflowDaoImpl->getWorkflowTasksByWorkflowId($workflowId);
    }


    public function getWorkflowTransitionDetail($transitionId)
    {
        $workflowDaoImpl = new Dao_Workflow();

        $transition = $workflowDaoImpl->getTransition($transitionId);
        $task       = $workflowDaoImpl->getWorkflowTask($transition[Db_WorkflowTransition::WORKFLOW_TASK_ID]);

        return array(
            'transition' => $transition,
            'task'       => $task,
            'workflowId' => $transition[Db_WorkflowTransition::WORKFLOW_ID],
        );
    }


    public function getWorkflowPlaceDetail($placeId)
    {
        $workflowDaoImpl = new Dao_Workflow();
        $place           = $workflowDaoImpl->getWorkflowPlace($placeId);

        return array(
            'place'      => $place,
            'workflowId' => $place[Db_WorkflowPlace::WORKFLOW_ID],
        );
    }

    public function getWorkflowTask($id)
    {
        $workflowDaoImpl = new Dao_Workflow();
        return $workflowDaoImpl->getWorkflowTask($id);
    }

    public function getPossibleMappings($type)
    {
        $workflowDaoImpl = new Dao_Workflow();
        return $workflowDaoImpl->getPossibleMappings($type);
    }

    public function getTriggerMappings($workflowId, $type)
    {
        $workflowDaoImpl = new Dao_Workflow();
        return $workflowDaoImpl->getTriggerMappings($workflowId, $type);
    }

    /**
     * retrieves a list of workflows by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getWorkflowList($page = null, $orderBy = null, $direction = null, $filter = null, $attributeFilter = null)
    {
        $this->logger->log("Service_Workflow: getWorkflowList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/workflow.ini', APPLICATION_ENV);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['workflow'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $workflowDaoImpl = new Dao_Workflow();
        $select          = $workflowDaoImpl->getWorkflowForPagination($orderBy, $direction, $filter, $attributeFilter);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return $paginator;
    }


    public function getWorkflowImage($id, $type)
    {
        $workflowId = null;

        if ($type == 'instance') {

        } else {
            $workflowId = $id;
        }


        // count arcs?
        $workflowDaoImpl = new Dao_Workflow();
        $countHeight     = $workflowDaoImpl->countMaxArcs($workflowId);
        $countHeight     = $countHeight['cnt'];

        if (!$countHeight)
            $countHeight = 2;

        $countHeight--;
        $height = 300 * $countHeight;


        // count transitions
        $countWidth = $workflowDaoImpl->countMaxTransitions($workflowId);
        $countWidth = $countWidth['cnt'];

        if (!$countWidth)
            $countWidth = 1;

        $width = 300 * $countWidth;
        $width += 500; // basis-Breite

        $framework = new Util_Workflow_Graph_Framework($width, $height);
        $framework->setColor(0, 0, 0, 127);
        $framework->setTitel('Michis WERK', 233, 14, 91);
        // create image


        // TODO: create START Node
        $placeCount = 0;
        $startNode  = new Util_Workflow_Graph_Place($placeCount, 0);
        $startNode->setTitel('START Node');
        $framework->addPlace($startNode);


        $placeCount++;;

        $hasMore = true;
//    	while($hasMore) {
//    		
//
//    		
//    		exit;
//    	}


        // TODO: create END Node
        $endNode = new Util_Workflow_Graph_Place($placeCount, 0);
        $startNode->setTitel('END Node');
        $framework->addPlace($endNode);

        // TODO: abschlussstrich?


        // TODO implement me!
        return $framework;
    }

    public function getFilterForm($attributes = null, $filter = null, $options = null)
    {
        $form = new Form_Filter($this->translator, $attributes, $options);
        if ($filter) {
            $form->populate(array('search' => $filter));
        }
        return $form;
    }

    public function getWorkflowItemByInstanceId($instanceId)
    {
        $workflowDaoImpl = new Dao_Workflow();
        return $workflowDaoImpl->getWorkflowItemByInstanceId($instanceId);
    }

}