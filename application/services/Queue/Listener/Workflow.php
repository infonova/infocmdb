<?php

class Service_Queue_Listener_Workflow implements Service_Queue_Listener
{

    public function listen()
    {
        $logger          = Zend_Registry::get('Log');
        $workflowDaoImpl = new Dao_Workflow();
        $list            = $workflowDaoImpl->getWorkflowForCronjob();

        foreach ($list as $workflow) {
            $cronjob = explode(' ', $workflow[Db_Workflow::EXECUTION_TIME]);

            $data                      = array();
            $data[Db_Cron::TYPE]       = 'workflow';
            $data[Db_Cron::MAPPING_ID] = $workflow[Db_Workflow::ID];
            $data[Db_Cron::VAR_DUMP]   = time();

            $contextArray = array('triggerType' => 'time');
            $workflowType = Util_Workflow_TypeFactory::create($workflow[Db_Workflow::SCRIPT_LANG], $workflow);
            $envVariables = $workflowType->getEnvironmentVariables();
            $contextArray = array_merge(array("Environment" => $envVariables), $contextArray);

            if (!$workflow['cronId']) {
                // insert instead of update
                $workflowDaoImpl->insertWorkflowImportsForCronjob($data);

                try {
                    $this->insertQueueMessage($workflow, $contextArray);
                } catch (Exception $e) {
                    $logger->log($e, Zend_Log::ERR);
                    // TODO
                }
            } else if (Service_Queue_Cron::checkExecutionTime($workflow[Db_Cron::LAST_EXECUTION], $cronjob)) {
                $data[Db_Cron::ID] = $workflow['cronId'];
                $workflowDaoImpl->updateWorkflowImportsForCronjob($data, $workflow['cronId']);

                try {
                    $this->insertQueueMessage($workflow, $contextArray);
                } catch (Exception $e) {
                    $logger->log($e, Zend_Log::ERR);
                    // TODO
                }
            }

        }
    }


    private function insertQueueMessage($workflow, $context = null)
    {
        $queueDaoImpl = new Dao_Queue();
        $messages     = $queueDaoImpl->searchActiveMessages('workflow', $workflow[Db_Workflow::NAME]);

        if ($messages['cnt'] > 0) {
            // do nothing. message already in queue
        } else {
            $args               = array();
            $args['type']       = Util_Workflow::ASYNC_TYPE_WORKFLOW;
            $args['workflowId'] = $workflow[Db_Workflow::ID];
            $args['tokenId']    = $workflow[Db_Workflow::NAME];; // to be found by search4name

            if ($context)
                $args['context'] = json_encode($context);

            $message = new Service_Queue_Message();
            $message->setQueueId(Service_Queue_Message::QUEUE_WORKFLOW);
            $message->setUserId(0); // system
            $message->setArgs($args);

            Service_Queue_Handler::add($message);
        }
    }

}
