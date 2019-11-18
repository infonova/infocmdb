<?php

class Util_Workflow_Log
{

    private $caseId = null;
    private $itemId = null;

    public function __construct($caseId, $itemId = null)
    {
        $this->caseId = $caseId;
        $this->itemId = $itemId;
    }

    public function log($message = null)
    {

        if (!$message || !$this->caseId || !$this->itemId)
            return;


        try {
            $daoWorkflow = new Dao_Workflow();
            $daoWorkflow->insertWorkflowLog($this->caseId, $this->itemId, $message);
        } catch (Exception $e) {
            // do nothing and ignore!
        }
    }


    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    }

    public function setCaseId($caseId)
    {
        $this->caseId = $caseId;
    }
}