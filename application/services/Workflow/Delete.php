<?php

/**
 *
 *
 *
 */
class Service_Workflow_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3604, $themeId);
    }

    /**
     * deletes a Workflow by the given Workflow Id
     *
     * @param $workflowId the Workflow ID to delete
     *
     * @throws Exception_Workflow_DeleteFailed
     */
    public function deleteWorkflow($workflowId)
    {
        try {
            $workflowDaoImpl = new Dao_Workflow();
            $workflow        = $workflowDaoImpl->getWorkflow($workflowId);
            $workflowType    = Util_Workflow_TypeFactory::create($workflow[Db_Workflow::SCRIPT_LANG], $workflow);
            $workflowType->delete();

            $rows       = $workflowDaoImpl->deleteWorkflow($workflowId);
            $statusCode = 1;
            if ($rows != 1) {
                throw new Exception("Error deleting Workflow with ID " . $workflowId . " in database");
                $statusCode = 0;
            }

        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Workflow_DeleteFailed($e);
        }
        return $statusCode;
    }

    /**
     * activate a Workflow by the given Workflow Id
     *
     * @param $workflowId the Workflow ID to activate
     *
     * @throws Exception_Workflow_ActivationFailed
     */
    public function activateWorkflow($workflowId)
    {
        try {
            $workflowDaoImpl = new Dao_Workflow();
            return $workflowDaoImpl->activateWorkflow($workflowId);
        } catch (Exception $e) {
            throw new Exception_Workflow_ActivationFailed($e);
        }
    }
}