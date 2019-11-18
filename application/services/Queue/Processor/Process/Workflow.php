<?php

class Process_Workflow extends Service_Queue_Processor
{

    public function __construct()
    {
        parent::__construct('workflow');
    }

    public function process()
    {
        $this->logger->log('process Workflow!', Zend_Log::DEBUG);

        $properties = $this->message->getArgs();
        list($type, $typeId, $tokenId, $context) = $properties;

        try {
            $workflowUtil = new Util_Workflow($this->logger);

            switch ($type) {
                case Util_Workflow::ASYNC_TYPE_WORKFLOW:
                    // process whole WF!
                    $workflowUtil->asyncStartWorkflow($typeId, $this->message->getUserId(), $context);
                    break;
                case Util_Workflow::ASYNC_TYPE_STEP:
                    // process single step
                    $workflowUtil->asyncProcessWorkflowItem($typeId, $tokenId);
                    break;
                default:
                    // exception
                    $this->logger->log('Process Workflow failed! WF-Type was not set properly', Zend_Log::CRIT);
                    Service_Queue_Handler::failed($this->message->getId());
                    return;
                    break;

            }
            Service_Queue_Handler::finalize($this->message->getId());
        } catch (Exception $e) {
            $this->logger->log('Process Workflow failed!', Zend_Log::CRIT);
            $this->logger->log($e, Zend_Log::ERR);
            Service_Queue_Handler::failed($this->message->getId());
        }
    }
}