<?php


class Util_Workflow
{

    private $logger;

    const ASYNC_TYPE_WORKFLOW = 'workflow'; // ganzer WF async
    const ASYNC_TYPE_STEP     = 'step'; // einzelner Step async
    const STATUS_STARTED      = 'STARTED';
    const STATUS_FINISHED     = 'FINISHED';
    const STATUS_FAILED       = 'FAILED';

    public function __construct($loggers)
    {
        $this->logger = $loggers;
    }


    /**
     * Starts a workflow
     *
     * @param int      $workflowId    ID of the worklfow to start
     * @param string   $userId        ID of the user the workflow should be started with. Default '0'
     * @param array    $contextArray  array containing the context information passed to the workflow. Defaults to an empty array
     * @param bool     $returnBoolean if this function should only return a boolean or not. Default true
     * @param bool     $forceAsync    forces the workflow to be executed asynchronously, even if it may be set to synchronously
     * @param DateTime $startAt       the time the worklfow should be started. Must be a DateTime object.
     *                                If this parameter is set to anything but a DateTime object or null, it will crash.
     *                                Default is null
     *
     * @return array|bool returns an array containing workflow information or a boolean if $returnBoolean is set to true
     */
    public function startWorkflow($workflowId, $userId = '0', $contextArray = array(), $returnBoolean = true, $forceAsync = false, $startAt = null)
    {
        if (is_null($startAt)) {
            $startAt = new DateTime();
        }
        try {
            $daoWorkflow  = new Dao_Workflow();
            $workflow     = $daoWorkflow->getWorkflow($workflowId);
            $workflowType = Util_Workflow_TypeFactory::create($workflow[Db_Workflow::SCRIPT_LANG], $workflow);
            $envVariables = $workflowType->getEnvironmentVariables();
            if(is_array($contextArray)) {
                $contextArray = array_merge(array("Environment" => $envVariables), $contextArray);
            } else {
                $contextArray = array("Environment" => $envVariables);
            }

            $contextArray['user_id'] = $userId;

            // create context
            $context = null;

            if (count($contextArray) > 0)
                $context = json_encode($contextArray);

            // if async -> insert into queue
            if ($workflow[Db_Workflow::IS_ASYNC] || $forceAsync) {
                $args               = array();
                $args['type']       = self::ASYNC_TYPE_WORKFLOW;
                $args['workflowId'] = $workflowId;
                $args['tokenId']    = $workflowId;

                if ($context)
                    $args['context'] = $context;

                $message = new Service_Queue_Message();
                $message->setQueueId(Service_Queue_Message::QUEUE_WORKFLOW);
                $message->setUserId($userId);
                $message->setArgs($args);
                $message->setExecutionTime($startAt->format("Y-m-d H:i:s"));

                Service_Queue_Handler::add($message);
                $workflowReturnValues                = array();
                $workflowReturnValues['instance_id'] = null;
                $workflowReturnValues['status']      = 'EXECUTION SCHEDULED';
                $workflowReturnValues['log']         = 'Async Workflow started';
                $output                              = $workflowReturnValues;

            } else {
                // process me! NOW!
                $output = $this->handleWorkflow($workflow, $userId, $context);
            }

            if ($returnBoolean)
                return true;

            return $output;

        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            return false;
        }
    }


    public function asyncStartWorkflow($workflowId, $userId = '0', $context = null)
    {
        $daoWorkflow = new Dao_Workflow();
        $workflow    = $daoWorkflow->getWorkflow($workflowId);

        $this->handleWorkflow($workflow, $userId, $context);
    }


    public function asyncProcessWorkflowItem($itemId, $tokenId)
    {
        try {
            $daoWorkflow = new Dao_Workflow();
            $item        = $daoWorkflow->getWorkflowItem($itemId);

            // create wf logger
            $workflowLogger = new Util_Workflow_Log($item[Db_WorkflowItem::WORKFLOW_CASE_ID], $item[Db_WorkflowItem::ID]);
            $workflowLogger->log('asyncProcessWorkflowItem triggered');

            $workflowInstance = $daoWorkflow->getWorkflowInstance($item[Db_WorkflowItem::WORKFLOW_CASE_ID]);
            if ($workflowInstance[Db_WorkflowCase::STATUS] == Dao_Workflow::WORKFLOW_INS_STATUS_CANCELLED) {
                $workflowLogger->log('stopped asyncProcessWorkflowItem. Workflow cancelled');
                return;
            }

            // TODO: check for suspended workflows??

            $proceed = $this->processWorkflowItem($itemId, $tokenId, true);

            if ($proceed) {
                // TODO: how to synchronize with parallel tasks??
                //$task = $daoWorkflow->getWorkflowTaskByItemId($itemId);
                $workflow = $daoWorkflow->getWorkflowByItemId($itemId);

                $instanceId = $workflowInstance[Db_WorkflowCase::ID];
                $token      = $daoWorkflow->getToken($tokenId);

                // TODO: transition?? ok??
                $executedTransitions = array();
                array_push($executedTransitions, array('transition' => $item[Db_WorkflowItem::WORKFLOW_TRANSITION_ID], 'proceed' => true));

                $isRunning = $this->proceedWorkflowStatus($workflow[Db_Workflow::ID], $token, $workflowInstance, $executedTransitions);

                while ($isRunning) {
                    $isRunning = $this->checkWorkflowProgress($instanceId);
                }
            } else {

                // script failed?? exception handling??
            }

        } catch (Exception $e) {
            $this->logger->log($e);
        }
    }


    private function handleWorkflow($workflow, $userId, $context)
    {
        $daoWorkflow = new Dao_Workflow();

        // create workflow case
        $instanceId = $daoWorkflow->createWorkflowInstance($workflow[Db_Workflow::ID], $userId, $context);

        // get first step
        $step   = $daoWorkflow->getStartWorkflowStep($workflow[Db_Workflow::ID]);
        $stepId = $step[Db_WorkflowPlace::ID];
        // create token @startplace
        $tokenId = $daoWorkflow->createWorkflowToken($workflow[Db_Workflow::ID], $instanceId, $stepId, $context);

        // check for workflow trigger and start transition
        $isRunning = true;

        while ($isRunning) {
            $isRunning = $this->checkWorkflowProgress($instanceId);
        }

        $instance = $daoWorkflow->getWorkflowInstance($instanceId);

        return array(
            'instance_id'       => $instanceId,
            'status'            => $instance[Db_WorkflowCase::STATUS],
            'instance_created'  => $instance[Db_WorkflowCase::CREATED],
            'instance_finished' => $instance[Db_WorkflowCase::FINISHED],
            'log'               => $daoWorkflow->getWorkflowLogsForInstance($instanceId),
        );
    }


    public function checkWorkflowProgress($workflowInstanceId)
    {
        $daoWorkflow      = new Dao_Workflow();
        $workflowInstance = $daoWorkflow->getWorkflowInstance($workflowInstanceId);

        // check for suspended
        if ($workflowInstance[Db_WorkflowCase::STATUS] == Dao_Workflow::WORKFLOW_INS_STATUS_SUSPENDED) {
            // is suspended and NOT allowed to continue!
            return false;
        }

        // TODO: check for cancelled??


        // TODO: split handling!
        $token = $daoWorkflow->getFreeToken($workflowInstanceId);

        if (!$token || !$token[Db_WorkflowToken::ID]) {
            // nothing to do. already active.. or dead
            return false;
        }

        // lock token to prevent double processing
        $daoWorkflow->lockToken($token[Db_WorkflowToken::ID]);

        // mittels step dem arc zur nächsten transition folgen
        $currentStepId = $token[Db_WorkflowToken::WORKFLOW_PLACE_ID];
        $workflowId    = $workflowInstance[Db_WorkflowCase::WORKFLOW_ID];

        $arcList = $daoWorkflow->getNextArcs($workflowId, $currentStepId);

        if (!$arcList) {
            // TODO: throw exception
            return false;
        }


        // TODO: retrieve context from prev. item??
        $context = $workflowInstance[Db_WorkflowCase::CONTEXT];

        $workflowLogger = new Util_Workflow_Log($workflowInstanceId);
        $workflowLogger->log('retrieve wf-transitions for current WF-Step');

        $executedTransitions = array();
        foreach ($arcList as $arc) {
            // select transition
            $transition = $daoWorkflow->getTransition($arc[Db_WorkflowArc::WORKFLOW_TRANSITION_ID]);

            // check trigger
            switch ($transition[Db_WorkflowTransition::TRIGGER]) {
                case Dao_Workflow::WORKFLOW_TRANS_TRIGGER_AUTO:
                    $workflowLogger->log('[transition] type "AUTO"; id ' . $transition[Db_WorkflowTransition::ID]);
                    // create new workflow item
                    $itemId  = $daoWorkflow->createWorkflowItem($workflowId, $workflowInstanceId, $transition[Db_WorkflowTransition::ID], $context);
                    $proceed = $this->processWorkflowItem($itemId, $token[Db_WorkflowToken::ID]);
                    array_push($executedTransitions, array('transition' => $transition[Db_WorkflowTransition::ID], 'proceed' => $proceed));

                    break;
                case Dao_Workflow::WORKFLOW_TRANS_TRIGGER_TIME:
                    $workflowLogger->log('[transition] type "TIME"; id ' . $transition[Db_WorkflowTransition::ID]);

                    // TODO check time??
                    $timeToWait = $transition[Db_WorkflowTransition::TRIGGER_TIME];
                    $itemId     = $daoWorkflow->createWorkflowItem($workflowId, $workflowInstanceId, $transition[Db_WorkflowTransition::ID], $context);

                    // insert into message queue
                    $args            = array();
                    $args['type']    = self::ASYNC_TYPE_STEP;
                    $args['itemId']  = $itemId;
                    $args['tokenId'] = $token[Db_WorkflowToken::ID];

                    if ($context)
                        $args['context'] = $context;

                    $message = new Service_Queue_Message();
                    $message->setQueueId(Service_Queue_Message::QUEUE_WORKFLOW);
                    $message->setUserId('0');

                    $executionTime = time() + $timeToWait;
                    $executionTime = date("Y-m-d H:i:s", $executionTime);

                    $message->setExecutionTime($executionTime);
                    $message->setArgs($args);

                    Service_Queue_Handler::add($message);
                    array_push($executedTransitions, array('transition' => $transition[Db_WorkflowTransition::ID], 'proceed' => false));

                    break;
                case Dao_Workflow::WORKFLOW_TRANS_TRIGGER_USER:

                    // TODO: handle this
                case Dao_Workflow::WORKFLOW_TRANS_TRIGGER_MSG:
                    // TODO: handle this via user-continue-button just without button?
                default:
                    // DO NOTHING! not triggered by us!
                    array_push($executedTransitions, array('transition' => $transition[Db_WorkflowTransition::ID], 'proceed' => false));
                    break;
            }

            // TODO: handle only one arc??
            break;
        }


        if (!$executedTransitions || count($executedTransitions) <= 0) {
            // nothing executed = nothign to do??
            // FIXME: check error handling here
            return false;
        }

        foreach ($executedTransitions as $transToCheck) {
            // we processed those arcs!

            if (!$transToCheck['proceed'])
                return false;
        }


        // abschlusshandling
        // schließe aktuelles Token
        // wenn prozess am Endpunkt -> schließe workflow ab!
        // wenn noch mehrere Steps, dann öffne neues token

        return $this->proceedWorkflowStatus($workflowId, $token, $workflowInstance, $executedTransitions);
    }


    private function proceedWorkflowStatus($workflowId, $token, $workflowInstance, $executedTransitions)
    {
        $daoWorkflow = new Dao_Workflow();
        // move to new step.  (foreach)
        // if step = last step -> close workflow case with success
        $workflowInstanceId = $workflowInstance[Db_WorkflowCase::ID];

        // TODO: renew WfInstance information and check for cancelled state
        $newWorkflowInstance = $daoWorkflow->getWorkflowInstance($workflowInstanceId);

        if ($newWorkflowInstance[Db_WorkflowCase::STATUS] == Dao_Workflow::WORKFLOW_INS_STATUS_CANCELLED) {
            return false;
        }

        foreach ($executedTransitions as $transToCheck) {
            // assume to be only one!
            $transitionId = $transToCheck['transition'];

            // select new arc
            $arcList = $daoWorkflow->getTargetArcs($workflowId, $transitionId);

            // TODO:what if more than one arc??
            foreach ($arcList as $arc) {
                // FIXME: check for arc type. ONLY
                switch ($arc[Db_WorkflowArc::TYPE]) {
                    case 'SEQ':
                        // is only one! proceed to next level

                        // TODO: where do i get that toek from??

                        // remove old token
                        $daoWorkflow->consumeToken($token[Db_WorkflowToken::ID]);


                        $nextPlace = $arc[Db_WorkflowArc::WORKFLOW_PLACE_ID];
                        $place     = $daoWorkflow->getWorkflowPlace($nextPlace);

                        if ($place[Db_WorkflowPlace::TYPE] == '9') {
                            // is end Step
                            // finish workflow
                            $daoWorkflow->updateWorkflowCaseStatus($workflowInstanceId, Dao_Workflow::WORKFLOW_INS_STATUS_CLOSED, true);

                            return false;
                        } else {

                            // open new Token
                            $tokenId = $daoWorkflow->createWorkflowToken($workflowId, $workflowInstanceId, $place[Db_WorkflowPlace::ID], $context);
                        }

                        break;
                    default:
                        // TODO: IMPLEMENT NEXT STEPS!!!!!
                        // what to do??
                        break;
                }
            }
        }

        return true;
    }


    public function processWorkflowItem($itemId, $tokenId, $handleAsync = false)
    {
        $daoWorkflow = new Dao_Workflow();

        // get transition task
        $task           = $daoWorkflow->getWorkflowTaskByItemId($itemId);
        $workflow       = $daoWorkflow->getWorkflowByItemId($itemId);
        $item           = $daoWorkflow->getWorkflowItem($itemId);
        $responseFormat = $workflow[Db_Workflow::RESPONSE_FORMAT];

        $workflowLogger = new Util_Workflow_Log($item[Db_WorkflowItem::WORKFLOW_CASE_ID], $item[Db_WorkflowItem::ID]);
        $workflowLogger->log('process Workflow Item');

        $context = $item[Db_WorkflowItem::CONTEXT];

        if ($task[Db_WorkflowTask::IS_ASYNC] && !$handleAsync) {
            $workflowLogger->log('Workflow Item ist async. Add MessageQueueJob.');
            // TODO: insert into message queue

            $args            = array();
            $args['type']    = self::ASYNC_TYPE_STEP;
            $args['itemId']  = $itemId;
            $args['tokenId'] = $tokenId;

            if ($context)
                $args['context'] = $context;

            $message = new Service_Queue_Message();
            $message->setQueueId(Service_Queue_Message::QUEUE_WORKFLOW);
            $message->setUserId('0');
            $message->setArgs($args);

            Service_Queue_Handler::add($message);
            return false;
        } else {
            // set status in progress
            $daoWorkflow->updateWorkflowItemStatus($itemId, Dao_Workflow::WORKFLOW_ITEM_STATUS_IN_PROGRESS);
            // process now!

            // start script and log errors/status/whatever??
            try {
                $success = $this->executeScript($workflow, $item, $context, $responseFormat);
            } catch (Exception $e) {
                $workflowLogger->log('Script execution failed! Please check your Task Configuration. Error-Message: ' . $e->getMessage());
            }

            // db connection may be auto closed
            $daoWorkflow->reconnect();

            if (!$success) {
                $workflowLogger->log('Script executed with errors');
                // update status to failed!
                $daoWorkflow->updateWorkflowItemStatus($itemId, Dao_Workflow::WORKFLOW_ITEM_STATUS_FAILED, true);

                // update whole case to FAILED!
                // TODO: is that ok? what about xor transitions? allowed to ail??
                $daoWorkflow->updateWorkflowCaseStatus($item[Db_WorkflowItem::WORKFLOW_CASE_ID], Dao_Workflow::WORKFLOW_INS_STATUS_FAILED, true);

                return false; // TODO: ???? is that ok?
            } else {
                $workflowLogger->log('Script executed successfully');
                // update status to success
                $daoWorkflow->updateWorkflowItemStatus($itemId, Dao_Workflow::WORKFLOW_ITEM_STATUS_FINISHED, true);
            }

            // TODO: exception handling here!
            return true;
        }
    }


    private function executeScript($workflow, $item, $context = null, $responseFormat = null)
    {
        $workflowDaoImpl = new Dao_Workflow();
        $workflowType    = Util_Workflow_TypeFactory::create($workflow[Db_Workflow::SCRIPT_LANG], $workflow);
        $workflowLogger  = new Util_Workflow_Log($item[Db_WorkflowItem::WORKFLOW_CASE_ID], $item[Db_WorkflowItem::ID]);

        // prepare parameter
        $apikey = $this->getApikey($workflow[Db_Workflow::EXECUTE_USER_ID]);
        if (empty($apikey)) {
            throw new Exception("utils/Workflow.php: generated apikey for user_id '" . $workflow[Db_Workflow::USER_ID] . "' can't be empty!");
        }

        $api                = array('apikey' => $apikey);
        $workflowInstanceId = $workflowDaoImpl->getWorkflowInstanceIdByItemId($item[Db_WorkflowItem::ID]);

        $workflowInfo = array(
            'workflow_item_id'     => (int) $item[Db_WorkflowItem::ID],
            'workflow_instance_id' => (int) $workflowInstanceId,
        );

        // build context
        $context = json_decode($context, true);
        if (is_null($context)) {
            $context = array();
        }

        $contextEnvironment = array();
        if (isset($context["Environment"])) {
            $contextEnvironment = $context["Environment"];
            unset($context["Environment"]);
        }

        // set env variables for workflow (based on individualization.ini)
        $workflowType->setEnvironmentVariables($contextEnvironment);

        if(array_count_values($contextEnvironment) > 0) {
            // create string for printing environment variables to workflow log
            $envSettings = $workflowType->getEnvironmentVariablesAsExportString($contextEnvironment);
            $workflowLogger->log("Environment: " . $envSettings);
        }

        // remove bloated data from context
        $slimContext = $context;
        if (isset($slimContext['data'])) {
            unset($slimContext['data']);
        }

        // build command parameter string
        if ($responseFormat == 'json') {
            // prepare params
            $commandParams = array_merge($api, $slimContext, $workflowInfo);
        } else {
            // prepare params - do not add workflow info to plain format
            $commandParams = array_merge($api, $slimContext);
        }

        // additional arguments which can be received by webservice
        $updatedContext   = array_merge($api, $context, $workflowInfo);
        $workflowItemData = array(
            Db_WorkflowItem::CONTEXT              => $updatedContext,
            Db_WorkflowItem::WORKFLOW_ARG_CONTEXT => json_encode($commandParams),

        );
        $workflowDaoImpl->updateWorkflowItem($item[Db_WorkflowItem::ID], $workflowItemData);

        $ret = $workflowType->execute($commandParams, $workflowLogger);

        if (!empty($ret['stderr'])) {
            $workflowLogger->log('[FAILED] executing script');
            return false;
        } else {
            $workflowLogger->log('[OK] executing script');
        }

        return true;
    }


    public function suspendWorkflow($instanceId, $userId = '0')
    {
        // TODO: implement me!

        if ($userId != '0') {
            // TODO: check Berechtigung??
            // eventuell permission denied exception werfen
            // return false, ??
        }

        try {
            $daoWorkflow = new Dao_Workflow();
            $daoWorkflow->updateWorkflowCaseStatus($instanceId, Dao_Workflow::WORKFLOW_INS_STATUS_SUSPENDED, true);

            $workflowLogger = new Util_Workflow_Log($instanceId);
            $workflowLogger->log('suspended Workflow by user ' . $userId);
        } catch (Exception $e) {
            $this->logger->log($e);
            return false;
        }
        return true;
    }


    public function cancelWorkflow($instanceId, $userId = '0')
    {
        // TODO: implement me!
        // cancel current WF!
        // nur im async modus
        // machbar, aber der aktuelle step WIRD fertiggestellt, da wir nicht zum prozess kommen.

        // TODO: einen cancelled - status beim case abfragen bevor wir den nächsten step starten??

        if ($userId != '0') {
            // TODO: check Berechtigung??
            // eventuell permission denied exception werfen
            // return false, ??
        }

        try {
            $daoWorkflow = new Dao_Workflow();
            $daoWorkflow->updateWorkflowCaseStatus($instanceId, Dao_Workflow::WORKFLOW_INS_STATUS_CANCELLED, true);

            $workflowLogger = new Util_Workflow_Log($instanceId);
            $workflowLogger->log('cancelled Workflow by user ' . $userId);
        } catch (Exception $e) {
            $this->logger->log($e);
            return false;
        }
        return true;
    }


    /**
     *
     * continue a wf-instance
     * can be triggered by user/system
     *
     * @param int $instanceId
     * @param int $userId
     */
    public function continueWorkflow($instanceId, $transitionId, $userId = '0')
    {
        $daoWorkflow = new Dao_Workflow();
        $instance    = $daoWorkflow->getWorkflowInstance($instanceId);
        $workflowId  = $instance[Db_WorkflowCase::WORKFLOW_ID];
        $context     = $instance[Db_WorkflowCase::CONTEXT];

        // get role_id from transition and check with user_id role?
        $transition = $daoWorkflow->getTransition($transitionId);
        $roleId     = $transition[Db_WorkflowTransition::ROLE_ID];

        // check permission
        if ($roleId && $userId && $userId != '0') {
            // is limited and not system triggert -> check or userId role
            $check = $daoWorkflow->checkUserRolePrivileges($userId, $roleId);
            if (!$check || count($check) <= 0) {
                // error -> invalid privs!
                throw new Exception_AccessDenied();
            }
        }

        // TODO: get place... or the right token.. somehow.. This is a temporary SOLUTION!!
        // TODO: select token.. omfg..
        $token = $daoWorkflow->getLockedToken($workflowId, $instanceId);

        if (!$token) {
            // TODO: error -> skip??
            // no token found. WF step seems to be already finished! -> skip
            return false;
        }

        // create workflow item
        $itemId = $daoWorkflow->createWorkflowItem($workflowId, $instanceId, $transitionId, $context);


        // create workflow logger
        $workflowLogger = new Util_Workflow_Log($instanceId, $itemId);
        $workflowLogger->log('[transition] continued by user "' . $userId . '" with transition ID id ' . $transitionId);

        // insert into message queue and pray!
        $args            = array();
        $args['type']    = self::ASYNC_TYPE_STEP;
        $args['itemId']  = $itemId;
        $args['tokenId'] = $token[Db_WorkflowToken::ID];

        if ($context)
            $args['context'] = $context;

        $message = new Service_Queue_Message();
        $message->setQueueId(Service_Queue_Message::QUEUE_WORKFLOW);
        $message->setUserId($userId);
        $message->setArgs($args);

        Service_Queue_Handler::add($message);
        return true;
    }


    public function wakeupWorkflow($instanceId, $userId = '0')
    {
        if ($userId != '0') {
            // TODO: check Berechtigung??
            // eventuell permission denied exception werfen
            // return false, ??
        }

        try {


            $daoWorkflow = new Dao_Workflow();
            $instance    = $daoWorkflow->getWorkflowInstance($instanceId);

            if ($instance[Db_WorkflowCase::STATUS] != Dao_Workflow::WORKFLOW_INS_STATUS_SUSPENDED) {
                // IS NOT A WAKEUP ITEM!!!
                // TODO: throw exception??
                return false;
            }

            $daoWorkflow->updateWorkflowCaseStatus($instanceId, Dao_Workflow::WORKFLOW_INS_STATUS_OPEN, true);

            $workflowLogger = new Util_Workflow_Log($instanceId);
            $workflowLogger->log('woke up Workflow by user ' . $userId);

            // now, continue wf handling?
            $isRunning = true;

            while ($isRunning) {
                $isRunning = $this->checkWorkflowProgress($instanceId);
            }
        } catch (Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    public function getApikey($userId)
    {
        try {
            $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            $options   = $bootstrap->getOptions();
            $timeout   = $options['auth']['login']['timeout'];

            $authInterface = new Dao_Authentication();
            $apikey        = $authInterface->setApiSession($userId, $timeout);
            return $apikey;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
        }
    }

    /**
     * @return string path to workflow dir
     */
    public static function getWorkflowPath()
    {
        $config = new Util_Config('fileupload.ini', APPLICATION_ENV);

        $useDefaultPath = $config->getValue('file.upload.path.default', true, Util_Config::BOOL);

        if ($useDefaultPath) {
            $defaultFolder = $config->getValue('file.upload.path.folder', '_uploads/', Util_Config::STRING);
            $path          = APPLICATION_PUBLIC . $defaultFolder;
        } else {
            $path = $config->getValue('file.upload.path.custom', APPLICATION_PUBLIC . '_uploads/', Util_Config::STRING);
        }

        $path = $path . $config->getValue('file.upload.workflow.folder', 'workflow', Util_Config::STRING) . '/';

        return $path;
    }

    /**
     * @return string path to executable dir
     */
    public static function getExecutablePath()
    {
        $config = new Util_Config('fileupload.ini', APPLICATION_ENV);

        $useDefaultPath = $config->getValue('file.upload.path.default', true, Util_Config::BOOL);

        if ($useDefaultPath) {
            $defaultFolder = $config->getValue('file.upload.path.folder', '_uploads/', Util_Config::STRING);
            $path          = APPLICATION_PUBLIC . $defaultFolder;
        } else {
            $path = $config->getValue('file.upload.path.custom', APPLICATION_PUBLIC . '_uploads/', Util_Config::STRING);
        }

        $path = $path . $config->getValue('file.upload.executeable.folder', 'executeable', Util_Config::STRING) . '/';

        return $path;
    }

    /**
     * @param array $docHeadParts list of doc headers (key = label, value = description)
     *
     * @return string comment header block in Perl syntax
     */
    public static function getDocHead($docHeadParts = array(), $commentInitiator = '#')
    {
        $docHeader = '';
        if (!empty($docHeadParts)) {
            $maxDocLabel = 0;
            foreach ($docHeadParts as $docLabel => $docDescription) {
                $docLabelLength = strlen($docLabel);
                if ($docLabelLength > $maxDocLabel) {
                    $maxDocLabel = $docLabelLength;
                }
            }

            $docRow    = str_repeat($commentInitiator, 15);
            $docHeader .= $docRow . " DOCHEAD " . $docRow . "\n";
            $docHeader .= $commentInitiator . " DO NOT EDIT - This part will be set automatically\n";
            $docHeader .= $commentInitiator . "\n";

            foreach ($docHeadParts as $docLabel => $docDescription) {
                $label     = " " . str_pad($docLabel . ':', $maxDocLabel + 4);
                $docHeader .= $commentInitiator . $label . $docDescription . "\n";
            }

            $docHeader .= $commentInitiator . "\n";
            $docHeader .= $docRow . " /DOCHEAD " . $docRow . "\n";
        }

        return $docHeader;
    }

    /**
     * @param array $workflow a workflow row
     *
     * @return array trigger type with array keys 'type' and 'activityTriggerTypesUsed'
     */
    public static function getWorkflowTriggerType($workflow)
    {
        $activityTriggerTypes = array(
            'attribute',
            'project',
            'ci',
            'ci_type_change',
            'relation',
            'fileimport',
        );

        $activityTriggerTypesUsed = array();
        foreach ($activityTriggerTypes as $triggerName) {
            $triggerColumn = 'trigger_' . $triggerName;
            if ($workflow[$triggerColumn] == 1) {
                $activityTriggerTypesUsed[] = $triggerName;
            }
        }

        $trigger = 'manual';
        if ($workflow[Db_Workflow::TRIGGER_TIME] == 1) {
            $trigger = 'time';
        } elseif (count($activityTriggerTypesUsed) > 0) {
            $trigger = 'activity';
        }

        return array(
            'type'                     => $trigger,
            'activityTriggerTypesUsed' => $activityTriggerTypesUsed,
        );
    }

    /**
     * @param array  $workflow workflow row
     * @param object $user     user which should be represented as author
     *
     * @return array list of workflow script headers
     */
    public static function getDocHeadPartsForWorkflow($workflow, $user)
    {

        $syncOrAsync = 'sync';
        if ($workflow[Db_Workflow::IS_ASYNC] == 1) {
            $syncOrAsync = 'async';
        }

        $trigger = Util_Workflow::getWorkflowTriggerType($workflow);

        $scriptHead = array(
            'Workflow-ID'   => $workflow[Db_Workflow::ID],
            'Workflow-Name' => $workflow[Db_Workflow::NAME],
            'Trigger-Type'  => $trigger['type'],
        );

        if ($trigger == 'time') {
            $scriptHead['Time-Trigger-Cron'] = $workflow[Db_Workflow::EXECUTION_TIME];
        } elseif ($trigger == 'activity') {
            $scriptHead['Activity-Trigger-Type'] = implode(', ', $trigger['activityTriggerTypesUsed']);
        }

        $scriptHead['Response-Format'] = $workflow[Db_Workflow::RESPONSE_FORMAT];
        $scriptHead['Sync/Async']      = $syncOrAsync;
        $scriptHead['Last Updated At'] = date('Y-m-d H:i:s');
        $scriptHead['Last Author']     = $user->getFirstname() . ' ' . $user->getLastname() . ' (' . $user->getUsername() . ')';
        $scriptHead['Description']     = $workflow[Db_Workflow::DESCRIPTION];

        return $scriptHead;
    }

    /**
     * @param array $attribute attribute row
     *
     * @return array list of executable script headers
     */
    public static function getDocHeadPartsForExecutable($attribute)
    {

        $syncOrAsync = 'sync';
        if ($attribute[Db_Attribute::IS_AUTOCOMPLETE] == 1) {
            $syncOrAsync = 'async';
        }

        $docHead = array(
            'Attribute-Name'  => $attribute[Db_Attribute::NAME],
            'Sync/Async'      => $syncOrAsync,
            'Last Updated At' => date('Y-m-d H:i:s'),
            'Description'     => $attribute[Db_Attribute::DESCRIPTION],
        );

        return $docHead;
    }

    /**
     * @param string $filename     name of file (without path)
     * @param string $script       content of file
     * @param array  $docHeadParts list of doc headers
     * @param string $scriptType   workflow|executable
     *
     * @return array|bool returns false if wile can not be written, otherwise array with file information
     */
    public static function saveScriptToFile($filename, $script, $docHeadParts = array(), $scriptType = 'workflow')
    {
        $script = trim($script);

        if (!empty($script)) {
            // replacing windows line endings with unix line endings
            $script = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $script);

            // update comment section with workflow-information
            $docHeader = self::getDocHead($docHeadParts);
            $script    = preg_replace('/############ DOCHEAD ############.+############ \/DOCHEAD ############/s', $docHeader, $script);

            if ($scriptType === 'workflow') {
                $basePath = self::getWorkflowPath();
            } elseif ($scriptType === 'executable') {
                $basePath = self::getExecutablePath();
            } else {
                return false;
            }

            $filePath     = $basePath . $filename;
            $file         = fopen($filePath, 'w');
            $bytesWritten = fwrite($file, $script);


            if ($bytesWritten === false) {
                return false;
            }

            fclose($file);
            chmod($filePath, 0774);

            return array(
                'script'     => $filename,
                'scriptname' => $filename,
            );
        }
    }

    /**
     * @param string       $filename   name of file (without path)
     * @param integer|null $subFolder  optional parameter: ID of the workflow/attribute for moving file in a separate folder
     * @param string       $scriptType workflow|executable
     *
     * @return bool returns true if moving was successful otherwise false
     */
    public static function archiveScript($filename, $subFolder = null, $scriptType = 'workflow')
    {

        if ($scriptType === 'workflow') {
            $basePath = self::getWorkflowPath();
        } elseif ($scriptType === 'executable') {
            $basePath = self::getExecutablePath();
        } else {
            return false;
        }

        $baseArchivePath = $basePath . 'archive/';

        if (!empty($subFolder)) {
            $baseArchivePath .= $subFolder . '/';
        }

        if (file_exists($basePath . $filename)) {
            if (!is_dir($baseArchivePath)) {
                mkdir($baseArchivePath, 0775, true);
            }
            $return = rename($basePath . $filename, $baseArchivePath . date('Y-m-d_Hms') . '__' . $filename);
            return $return;
        }

        return false;
    }
}