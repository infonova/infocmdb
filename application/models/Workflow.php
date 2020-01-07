<?php

class Dao_Workflow extends Dao_Abstract
{

    const WORKFLOW_INS_STATUS_OPEN      = 'OPEN';
    const WORKFLOW_INS_STATUS_CLOSED    = 'CLOSED';
    const WORKFLOW_INS_STATUS_SUSPENDED = 'SUSPENDED';
    const WORKFLOW_INS_STATUS_FAILED    = 'FAILED';
    const WORKFLOW_INS_STATUS_CANCELLED = 'CANCELLED';

    const WORKFLOW_TOKEN_STATUS_FREE      = 'FREE';
    const WORKFLOW_TOKEN_STATUS_LOCKED    = 'LOCKED';
    const WORKFLOW_TOKEN_STATUS_CONSUMED  = 'CONSUMED';
    const WORKFLOW_TOKEN_STATUS_CANCELLED = 'CANCELLED';

    const WORKFLOW_ARC_DIRECTION_IN  = 'IN';
    const WORKFLOW_ARC_DIRECTION_OUT = 'OUT';

    const WORKFLOW_TRANS_TRIGGER_AUTO = 'AUTO';
    const WORKFLOW_TRANS_TRIGGER_USER = 'USER';
    const WORKFLOW_TRANS_TRIGGER_MSG  = 'MSG';
    const WORKFLOW_TRANS_TRIGGER_TIME = 'TIME';

    const WORKFLOW_ITEM_STATUS_ENABLED     = 'ENABLED';
    const WORKFLOW_ITEM_STATUS_IN_PROGRESS = 'IN_PROGRESS';
    const WORKFLOW_ITEM_STATUS_CANCELLED   = 'CANCELLED';
    const WORKFLOW_ITEM_STATUS_FAILED      = 'FAILED';
    const WORKFLOW_ITEM_STATUS_FINISHED    = 'FINISHED';

    public function getWorkflow($id)
    {
        $select = $this->db->select()
            ->from(Db_Workflow::TABLE_NAME)
            ->where(Db_Workflow::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function getWorkflowByName($name)
    {
        $select = $this->db->select()
            ->from(Db_Workflow::TABLE_NAME)
            ->where(Db_Workflow::NAME . ' =?', $name);
        return $this->db->fetchRow($select);
    }

    public function getWorkflowTasksByWorkflowId($workflowId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowTask::TABLE_NAME)
            ->join(Db_WorkflowTransition::TABLE_NAME, Db_WorkflowTask::TABLE_NAME . '.' . Db_WorkflowTask::ID . ' = ' . Db_WorkflowTransition::TABLE_NAME . '.' . Db_WorkflowTransition::WORKFLOW_TASK_ID, array('workflow_transition_id' => Db_WorkflowTransition::ID))
            ->where(Db_WorkflowTransition::TABLE_NAME . '.' . Db_WorkflowTransition::WORKFLOW_ID . ' =?', $workflowId)
            ->order(Db_WorkflowTask::TABLE_NAME . '.' . Db_WorkflowTask::ID . ' ASC');

        return $this->db->fetchAll($select);
    }

    public function getWorkflowTask($id)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowTask::TABLE_NAME)
            ->where(Db_WorkflowTask::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function getWorkflowTriggerByWorkflowId($workflowId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowTrigger::TABLE_NAME)
            ->where(Db_WorkflowTrigger::WORKFLOW_ID . ' =?', $workflowId);
        return $this->db->fetchAll($select);
    }

    public function getWorkflowInstance($id)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowCase::TABLE_NAME)
            ->where(Db_WorkflowCase::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function getActiveWorkflows()
    {
        $table  = new Db_Workflow();
        $select = $table->select();
        $select->where(Db_Workflow::IS_ACTIVE . ' = ?', '1');

        return $this->db->fetchAll($select);
    }

    public function getWorkflowForPagination($orderBy = null, $direction = null, $filter = null, $attributeFilter = null)
    {
        $table  = new Db_Workflow();
        $select = $table->select();

        $select = $select->from(Db_Workflow::TABLE_NAME, array(
            Db_Workflow::ID,
            Db_Workflow::NAME,
            Db_Workflow::DESCRIPTION,
            Db_Workflow::NOTE,
            Db_Workflow::EXECUTE_USER_ID,
            Db_Workflow::IS_ASYNC,
            Db_Workflow::TRIGGER_CI,
            Db_Workflow::TRIGGER_CI_TYPE_CHANGE,
            Db_Workflow::TRIGGER_ATTRIBUTE,
            Db_Workflow::TRIGGER_PROJECT,
            Db_Workflow::TRIGGER_RELATION,
            Db_Workflow::TRIGGER_TIME,
            Db_Workflow::TRIGGER_FILEIMPORT,
            Db_Workflow::EXECUTION_TIME,
            Db_Workflow::STATUS,
            Db_Workflow::STATUS_MESSAGE,
            Db_Workflow::USER_ID,
            Db_Workflow::IS_ACTIVE,
            Db_Workflow::VALID_FROM,
            'trigger_type' => new Zend_Db_Expr('CASE WHEN trigger_ci = "1" THEN "activity"
						WHEN trigger_ci_type_change = "1" THEN "activity"
						WHEN trigger_attribute = "1" THEN "activity"
						WHEN trigger_project = "1" THEN "activity"
						WHEN trigger_relation = "1" THEN "activity"
						WHEN trigger_fileimport = "1" THEN "activity"
						WHEN trigger_time = "1" THEN "time"
						ELSE "manual" END'
            ),
            Db_Workflow::SCRIPT_LANG,
        ));

        if ($filter) {
            $select = $select
                ->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::NAME . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::DESCRIPTION . ' LIKE "%' . $filter . '%"');
        } else if ($attributeFilter) {

            $subselect = $this->db->select(false);
            $subselect->from(array('workflow' => new Zend_Db_Expr('(' . $select . ')'), array(new Zend_Db_Expr('*'))));
            $subselect->where('trigger_type LIKE "%' . $attributeFilter['trigger_type'] . '%"');

            $select = $subselect;
            $select = $select
                ->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::NAME . ' LIKE "%' . $attributeFilter['name'] . '%"')
                ->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::DESCRIPTION . ' LIKE "%' . $attributeFilter['description'] . '%"')
                ->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::NOTE . ' LIKE "%' . $attributeFilter['note'] . '%"')
                ->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::IS_ASYNC . ' LIKE "%' . $attributeFilter['is_async'] . '%"')
                ->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::IS_ACTIVE . ' LIKE "%' . $attributeFilter['is_active'] . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::NAME . ' ASC');
        }


        return $select;
    }

    public function getWorkflowInstanceForPagination($workflowId, $orderBy = null, $direction = null, $filter = null)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowCase::TABLE_NAME)
            ->where(Db_WorkflowCase::TABLE_NAME . '.' . Db_WorkflowCase::WORKFLOW_ID . ' =?', $workflowId)
            ->joinLeft(Db_User::TABLE_NAME, Db_WorkflowCase::TABLE_NAME . '.' . Db_WorkflowCase::USER_ID . ' = ' . Db_User::TABLE_NAME . '.' . Db_User::ID, array(Db_User::USERNAME));;
        if ($orderBy) {
            if (!$direction)
                $direction = 'DESC';

            $select->order($orderBy . ' ' . $direction);
        } else {
            $select->order(Db_WorkflowCase::TABLE_NAME . '.' . Db_WorkflowCase::CREATED . ' DESC');
        }

        if ($filter) {
            // TODO: implement me!
        }
        return $select;
    }


    public function getTriggerMappings($workflowId, $type)
    {
        $select = $this->db->select()->from(Db_WorkflowTrigger::TABLE_NAME);

        switch ($type) {
            case 'ci':
                $select->join(Db_CiType::TABLE_NAME, Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array(Db_CiType::NAME, Db_CiType::DESCRIPTION));
                break;
            case 'ci_type':
                $select->join(Db_CiType::TABLE_NAME, Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array(Db_CiType::NAME, Db_CiType::DESCRIPTION));
                break;
            case 'relation':
                $select->join(Db_CiRelationType::TABLE_NAME, Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID . ' = ' . Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID, array(Db_CiRelationType::NAME, Db_CiRelationType::DESCRIPTION));
                break;
            case 'attribute':
                $select->join(Db_Attribute::TABLE_NAME, Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID, array(Db_Attribute::NAME, Db_Attribute::DESCRIPTION));
                break;
            case 'project':
                $select->join(Db_Project::TABLE_NAME, Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID . ' = ' . Db_Project::TABLE_NAME . '.' . Db_Project::ID, array(Db_Project::NAME, Db_Project::DESCRIPTION));
                break;
        }

        $select->where(Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::WORKFLOW_ID . ' =?', $workflowId)
            ->where(Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::TYPE . ' =?', $type);
        return $this->db->fetchAll($select);
    }

    public function getPossibleMappings($type)
    {
        $select = $this->db->select();

        switch ($type) {
            case 'ci':
                $select->from(Db_CiType::TABLE_NAME, array('id' => Db_CiType::ID, 'description' => Db_CiType::DESCRIPTION, 'name' => Db_CiType::NAME));
                break;
            case 'ci_type_change':
                $select->from(Db_CiType::TABLE_NAME, array('id' => Db_CiType::ID, 'description' => Db_CiType::DESCRIPTION, 'name' => Db_CiType::NAME));
                break;
            case 'relation':
                $select->from(Db_CiRelationType::TABLE_NAME, array('id' => Db_CiRelationType::ID, 'description' => Db_CiRelationType::DESCRIPTION, 'name' => Db_CiRelationType::NAME));
                $select->where(Db_CiRelationType::IS_ACTIVE . ' =?', '1');
                break;
            case 'attribute':
                $select->from(Db_Attribute::TABLE_NAME, array('id' => Db_Attribute::ID, 'description' => Db_Attribute::DESCRIPTION, 'name' => Db_Attribute::NAME));
                $select->where(Db_Project::IS_ACTIVE . ' =?', '1');
                break;
            case 'project':
                $select->from(Db_Project::TABLE_NAME, array('id' => Db_Project::ID, 'description' => Db_Project::DESCRIPTION, 'name' => Db_Project::NAME));
                $select->where(Db_Project::IS_ACTIVE . ' =?', '1');
                break;
        }
        return $this->db->fetchAll($select);
    }

    public function getStartWorkflowStep($workflowId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowPlace::TABLE_NAME)
            ->where(Db_WorkflowPlace::WORKFLOW_ID . ' =?', $workflowId)
            ->where(Db_WorkflowPlace::TYPE . ' =?', '1');
        return $this->db->fetchRow($select);
    }

    public function getEndWorkflowStep($workflowId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowPlace::TABLE_NAME)
            ->where(Db_WorkflowPlace::WORKFLOW_ID . ' =?', $workflowId)
            ->where(Db_WorkflowPlace::TYPE . ' =?', '9');
        return $this->db->fetchRow($select);
    }

    public function getFreeToken($instanceId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowToken::TABLE_NAME)
            ->where(Db_WorkflowToken::WORKFLOW_CASE_ID . ' =?', $instanceId)
            ->where(Db_WorkflowToken::STATUS . ' =?', self::WORKFLOW_TOKEN_STATUS_FREE);
        return $this->db->fetchRow($select);
    }

    public function getLockedToken($workflowId, $instanceId, $placeId = null)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowToken::TABLE_NAME)
            ->where(Db_WorkflowToken::WORKFLOW_ID . ' =?', $workflowId)
            ->where(Db_WorkflowToken::WORKFLOW_CASE_ID . ' =?', $instanceId)
            ->where(Db_WorkflowToken::STATUS . ' =?', self::WORKFLOW_TOKEN_STATUS_LOCKED);

        if ($placeId)
            $select->where(Db_WorkflowToken::WORKFLOW_PLACE_ID . ' =?', $placeId);
        return $this->db->fetchRow($select);
    }

    public function countWorkflowMapping($workflowId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowTrigger::TABLE_NAME, 'count(*) as count')
            ->where(Db_WorkflowTrigger::WORKFLOW_ID . ' = ?', $workflowId);

        return $this->db->fetchRow($select);
    }

    public function getToken($tokenId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowToken::TABLE_NAME)
            ->where(Db_WorkflowToken::ID . ' =?', $tokenId);
        return $this->db->fetchRow($select);
    }

    public function getWorkflowPlace($placeId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowPlace::TABLE_NAME)
            ->where(Db_WorkflowPlace::ID . ' =?', $placeId);
        return $this->db->fetchRow($select);
    }

    public function lockToken($tokenId)
    {
        return $this->handleToken($tokenId, self::WORKFLOW_TOKEN_STATUS_LOCKED);
    }

    public function consumeToken($tokenId)
    {
        return $this->handleToken($tokenId, self::WORKFLOW_TOKEN_STATUS_CONSUMED);
    }

    public function cancelToken($tokenId)
    {
        return $this->handleToken($tokenId, self::WORKFLOW_TOKEN_STATUS_CANCELLED);
    }

    private function handleToken($tokenId, $status)
    {
        $table = new Db_WorkflowToken();

        $data                           = array();
        $data[Db_WorkflowToken::STATUS] = $status;

        $where = $this->db->quoteInto(Db_WorkflowToken::ID . ' =?', $tokenId);
        return $table->update($data, $where);
    }

    public function getNextArcs($workflowId, $stepId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowArc::TABLE_NAME)
            ->where(Db_WorkflowArc::WORKFLOW_ID . ' =?', $workflowId)
            ->where(Db_WorkflowArc::WORKFLOW_PLACE_ID . ' =?', $stepId)
            ->where(Db_WorkflowArc::DIRECTION . ' =?', self::WORKFLOW_ARC_DIRECTION_IN);
        return $this->db->fetchAll($select);
    }

    public function getTargetArcs($workflowId, $transitionId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowArc::TABLE_NAME)
            ->where(Db_WorkflowArc::WORKFLOW_ID . ' =?', $workflowId)
            ->where(Db_WorkflowArc::WORKFLOW_TRANSITION_ID . ' =?', $transitionId)
            ->where(Db_WorkflowArc::DIRECTION . ' =?', self::WORKFLOW_ARC_DIRECTION_OUT);
        return $this->db->fetchAll($select);
    }

    public function getTransition($id)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowTransition::TABLE_NAME)
            ->where(Db_WorkflowTransition::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function createWorkflowItem($workflowId, $caseId, $transitionId, $context = null)
    {
        $table = new Db_WorkflowItem();

        $data                                          = array();
        $data[Db_WorkflowItem::WORKFLOW_ID]            = $workflowId;
        $data[Db_WorkflowItem::WORKFLOW_CASE_ID]       = $caseId;
        $data[Db_WorkflowItem::WORKFLOW_TRANSITION_ID] = $transitionId;
        $data[Db_WorkflowItem::STATUS]                 = self::WORKFLOW_ITEM_STATUS_ENABLED;

        if ($context)
            $data[Db_WorkflowItem::CONTEXT] = $context;


        return $table->insert($data);
    }

    public function createWorkflowInstance($workflowId, $userId = '0', $context = null)
    {
        $table = new Db_WorkflowCase();

        $data                               = array();
        $data[Db_WorkflowCase::WORKFLOW_ID] = $workflowId;
        $data[Db_WorkflowCase::STATUS]      = self::WORKFLOW_INS_STATUS_OPEN;
        $data[Db_WorkflowCase::USER_ID]     = $userId;

        if ($context)
            $data[Db_WorkflowCase::CONTEXT] = $context;

        return $table->insert($data);
    }


    public function createWorkflowToken($workflowId, $instanceId, $stepId, $context = null)
    {
        $table = new Db_WorkflowToken();

        $data                                      = array();
        $data[Db_WorkflowToken::WORKFLOW_ID]       = $workflowId;
        $data[Db_WorkflowToken::WORKFLOW_CASE_ID]  = $instanceId;
        $data[Db_WorkflowToken::WORKFLOW_PLACE_ID] = $stepId;
        $data[Db_WorkflowToken::STATUS]            = self::WORKFLOW_TOKEN_STATUS_FREE;

        if ($context) {
            $data[Db_WorkflowToken::CONTEXT] = $context;
        }

        return $table->insert($data);
    }


    public function getWorkflowTaskByItemId($itemId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowTask::TABLE_NAME)
            ->join(Db_WorkflowTransition::TABLE_NAME, Db_WorkflowTransition::TABLE_NAME . '.' . Db_WorkflowTransition::WORKFLOW_TASK_ID . ' = ' . Db_WorkflowTask::TABLE_NAME . '.' . Db_WorkflowTask::ID, array())
            ->join(Db_WorkflowItem::TABLE_NAME, Db_WorkflowItem::TABLE_NAME . '.' . Db_WorkflowItem::WORKFLOW_TRANSITION_ID . ' = ' . Db_WorkflowTransition::TABLE_NAME . '.' . Db_WorkflowTransition::ID, array())
            ->where(Db_WorkflowItem::TABLE_NAME . '.' . Db_WorkflowItem::ID . ' =?', $itemId);
        return $this->db->fetchRow($select);
    }


    public function getWorkflowByItemId($itemId)
    {
        $select = $this->db->select()
            ->from(Db_Workflow::TABLE_NAME)
            ->join(Db_WorkflowItem::TABLE_NAME, Db_WorkflowItem::TABLE_NAME . '.' . Db_WorkflowItem::WORKFLOW_ID . ' = ' . Db_Workflow::TABLE_NAME . '.' . Db_Workflow::ID, array())
            ->where(Db_WorkflowItem::TABLE_NAME . '.' . Db_WorkflowItem::ID . ' =?', $itemId);
        return $this->db->fetchRow($select);
    }

    public function getWorkflowByCaseId($caseId)
    {
        $select = $this->db->select()
            ->from(Db_Workflow::TABLE_NAME)
            ->join(Db_WorkflowCase::TABLE_NAME, Db_WorkflowCase::TABLE_NAME . '.' . Db_WorkflowCase::WORKFLOW_ID . ' = ' . Db_Workflow::TABLE_NAME . '.' . Db_Workflow::ID, array())
            ->where(Db_WorkflowCase::TABLE_NAME . '.' . Db_WorkflowCase::ID . ' =?', $caseId);

        return $this->db->fetchRow($select);
    }

    public function getWorkflowItem($itemId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowItem::TABLE_NAME)
            ->where(Db_WorkflowItem::ID . ' =?', $itemId);
        return $this->db->fetchRow($select);
    }

    public function insertWorkflow($data)
    {
        $table = new Db_Workflow();
        return $table->insert($data);
    }

    public function insertWorkflowTask($data)
    {
        $table = new Db_WorkflowTask();
        return $table->insert($data);
    }

    public function insertWorkflowTransition($data)
    {
        $table = new Db_WorkflowTransition();
        return $table->insert($data);
    }

    public function insertWorkflowPlace($data)
    {
        $table = new Db_WorkflowPlace();
        return $table->insert($data);
    }

    public function insertWorkflowArc($data)
    {
        $table = new Db_WorkflowArc();
        return $table->insert($data);
    }

    public function insertWorkflowTrigger($data)
    {
        $table = new Db_WorkflowTrigger();
        return $table->insert($data);
    }

    public function insertWorkflowLog($caseId, $itemId, $message)
    {
        $data                                   = array();
        $data[Db_WorkflowLog::WORKFLOW_CASE_ID] = $caseId;
        $data[Db_WorkflowLog::WORKFLOW_ITEM_ID] = $itemId;
        $data[Db_WorkflowLog::MESSAGE]          = $message;

        $table = new Db_WorkflowLog();
        $table->insert($data);
    }

    public function updateWorkflowItemStatus($itemId, $status, $isFinished = false)
    {
        $data                          = array();
        $data[Db_WorkflowItem::STATUS] = $status;

        if ($isFinished)
            $data[Db_WorkflowItem::FINISHED] = new Zend_Db_Expr('NOW()');

        $where = $this->db->quoteInto(Db_WorkflowItem::ID . ' =?', $itemId);

        $table = new Db_WorkflowItem();
        $table->update($data, $where);
    }


    public function updateWorkflowCaseStatus($caseId, $status, $isFinished = false)
    {
        $data                          = array();
        $data[Db_WorkflowCase::STATUS] = $status;

        if ($isFinished)
            $data[Db_WorkflowCase::FINISHED] = new Zend_Db_Expr('NOW()');

        if ($status === Dao_Workflow::WORKFLOW_INS_STATUS_FAILED) {
            $data[Db_WorkflowCase::SOLVE_STATUS] = 1;
        }

        $where = $this->db->quoteInto(Db_WorkflowCase::ID . ' =?', $caseId);

        $table = new Db_WorkflowCase();
        $table->update($data, $where);

        $workflow = $this->getWorkflowByCaseId($caseId);
        if ($workflow === false) {
            throw new Exception('No workflow was found for workflow case id ' . $caseId);
        }
        $workflowId   = $workflow['id'];
        $dataWorkflow = array(
            'status'         => 1,
            'status_message' => null,
        );
        if ($status === Dao_Workflow::WORKFLOW_INS_STATUS_FAILED) {
            $instanceLogs = $this->getWorkflowLogsForInstance($caseId);
            $logString    = '';
            foreach ($instanceLogs as $instanceLog) {
                $logString .= $instanceLog['message'] . "\n";
            }

            $dataWorkflow['status']         = 0;
            $dataWorkflow['status_message'] = $logString;

        }

        $where = $this->db->quoteInto(Db_Workflow::ID . ' = ?', $workflowId);
        $table = new Db_Workflow();

        $affectedRows = $table->update($dataWorkflow, $where);

        if ($affectedRows > 1) {
            throw new Exception('More than one row affected by workflow status update - Condition was: ' . $where);
        }

        return $affectedRows;
    }

    public function updateWorkflowSolveStatus($workflowId, $status)
    {
        $where = $this->db->quoteInto(Db_WorkflowCase::WORKFLOW_ID . ' = ?', $workflowId);

        $data = array(
            Db_WorkflowCase::SOLVE_STATUS => $status,
        );

        $table = new Db_WorkflowCase();
        return $table->update($data, $where);
    }

    public function updateWorkflowCase($data, $workflowCaseId)
    {
        $where = $this->db->quoteInto(Db_WorkflowCase::ID . ' = ?', $workflowCaseId);

        $table = new Db_WorkflowCase();
        return $table->update($data, $where);
    }

    public function activateWorkflow($workflowId)
    {
        $data[Db_Workflow::IS_ACTIVE] = '1';
        $where                        = $this->db->quoteInto(Db_Workflow::ID . ' = ?', $workflowId);

        $table = new Db_Workflow();
        return $table->update($data, $where);
    }

    public function deactivateWorkflow($workflowId)
    {
        $data[Db_Workflow::IS_ACTIVE] = '0';
        $where                        = $this->db->quoteInto(Db_Workflow::ID . ' = ?', $workflowId);

        $table = new Db_Workflow();
        return $table->update($data, $where);
    }

    public function deleteWorkflow($workflowId)
    {
        $this->deleteWorkflowArcs($workflowId);
        $this->deleteWorkflowItem($workflowId);
        $this->deleteWorkflowToken($workflowId);
        $this->deleteWorkflowCase($workflowId);
        $this->deleteWorkflowPlace($workflowId);
        $this->deleteWorkflowTransition($workflowId);
        $this->deleteWorkflowMappingByWorkflowId($workflowId);

        $where = $this->db->quoteInto(Db_Workflow::ID . ' = ?', $workflowId);

        $table = new Db_Workflow();
        return $table->delete($where);
    }

    public function deleteWorkflowInstance($workflowId, $instanceId)
    {
        $this->deleteWorkflowItem($workflowId, $instanceId);
        $this->deleteWorkflowToken($workflowId, $instanceId);
        $this->deleteWorkflowCase($workflowId, $instanceId);
    }


    private function deleteWorkflowToken($workflowId, $instanceId = null)
    {
        $where   = array();
        $where[] = $this->db->quoteInto(Db_WorkflowToken::WORKFLOW_ID . ' = ?', $workflowId);

        if ($instanceId !== null) {
            $where[] = $this->db->quoteInto(Db_WorkflowToken::WORKFLOW_CASE_ID . ' = ?', $instanceId);
        }

        $table = new Db_WorkflowToken();
        return $table->delete($where);
    }

    private function deleteWorkflowItem($workflowId, $instanceId = null)
    {
        $where   = array();
        $where[] = $this->db->quoteInto(Db_WorkflowItem::WORKFLOW_ID . ' = ?', $workflowId);

        if ($instanceId !== null) {
            $where[] = $this->db->quoteInto(Db_WorkflowItem::WORKFLOW_CASE_ID . ' = ?', $instanceId);
        }

        $table = new Db_WorkflowItem();
        return $table->delete($where);
    }


    private function deleteWorkflowCase($workflowId, $instanceId = null)
    {
        $where   = array();
        $where[] = $this->db->quoteInto(Db_WorkflowCase::WORKFLOW_ID . ' = ?', $workflowId);

        if ($instanceId !== null) {
            $where[] = $this->db->quoteInto(Db_WorkflowCase::ID . ' = ?', $instanceId);
        }

        $table = new Db_WorkflowCase();
        return $table->delete($where);
    }


    private function deleteWorkflowArcs($workflowId)
    {
        $where = $this->db->quoteInto(Db_WorkflowArc::WORKFLOW_ID . ' = ?', $workflowId);

        $table = new Db_WorkflowArc();
        return $table->delete($where);
    }


    private function deleteWorkflowTransition($workflowId)
    {
        $where = $this->db->quoteInto(Db_WorkflowTransition::WORKFLOW_ID . ' = ?', $workflowId);

        $table = new Db_WorkflowTransition();
        return $table->delete($where);
    }

    private function deleteWorkflowPlace($workflowId)
    {
        $where = $this->db->quoteInto(Db_WorkflowPlace::WORKFLOW_ID . ' = ?', $workflowId);

        $table = new Db_WorkflowPlace();
        return $table->delete($where);
    }


    public function deleteWorkflowMappingByWorkflowId($workflowId)
    {
        $table = new Db_WorkflowTrigger();
        $where = array(Db_WorkflowTrigger::WORKFLOW_ID . ' = ?' => $workflowId);
        $table->delete($where);
    }


    public function deleteWorkflowMappingByVars($workflowId, $mappingId, $type, $method)
    {
        $table = new Db_WorkflowTrigger();
        $where = array(Db_WorkflowTrigger::WORKFLOW_ID . ' = ?' => $workflowId, Db_WorkflowTrigger::MAPPING_ID . ' = ?' => $mappingId, Db_WorkflowTrigger::TYPE . ' = ?' => $type, Db_WorkflowTrigger::METHOD . ' = ?' => $method);
        $table->delete($where);
    }

    public function deleteWorkflowMappingById($workflowId, $mappingId, $type)
    {
        $table = new Db_WorkflowTrigger();
        $where = array(Db_WorkflowTrigger::WORKFLOW_ID . ' = ?' => $workflowId, Db_WorkflowTrigger::MAPPING_ID . ' = ?' => $mappingId, Db_WorkflowTrigger::TYPE . ' = ?' => $type);
        $table->delete($where);
    }

    public function updateWorkflow($data, $workflowId)
    {
        $where = $this->db->quoteInto(Db_Workflow::ID . ' = ?', $workflowId);

        $table = new Db_Workflow();
        return $table->update($data, $where);
    }

    public function updateWorkflowTask($data, $workflowTaskId)
    {
        $where = $this->db->quoteInto(Db_WorkflowTask::ID . ' = ?', $workflowTaskId);

        $table = new Db_WorkflowTask();
        return $table->update($data, $where);
    }

    public function updateWorkflowTransitionByWorkflowId($data, $workflowId)
    {
        $where = $this->db->quoteInto(Db_WorkflowTransition::WORKFLOW_ID . ' = ?', $workflowId);

        $table = new Db_WorkflowTransition();
        return $table->update($data, $where);
    }


    public function checkUserRolePrivileges($userId, $roleId)
    {
        $select = $this->db->select()
            ->from(Db_UserRole::TABLE_NAME)
            ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . ' =?', $userId)
            ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' =?', $roleId);
        return $this->db->fetchAll($select);
    }


    public function getWorkflowLogsForInstance($instanceId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowLog::TABLE_NAME)
            ->where(Db_WorkflowLog::TABLE_NAME . '.' . Db_WorkflowLog::WORKFLOW_CASE_ID . ' =?', $instanceId);
        return $this->db->fetchAll($select);
    }


    // GRAFIK - ZENTRUM

    public function countMaxArcs($workflowId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowArc::TABLE_NAME, array('cnt' => 'COUNT(id)'))
            ->where(Db_WorkflowArc::TABLE_NAME . '.' . Db_WorkflowArc::WORKFLOW_ID . ' =?', $workflowId)
            ->group(Db_WorkflowArc::TABLE_NAME . '.' . Db_WorkflowArc::WORKFLOW_PLACE_ID)
            ->order('cnt DESC')
            ->limit(1);
        return $this->db->fetchRow($select);
    }


    public function countMaxTransitions($workflowId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowTransition::TABLE_NAME, array('cnt' => 'COUNT(id)'))
            ->where(Db_WorkflowTransition::TABLE_NAME . '.' . Db_WorkflowTransition::WORKFLOW_ID . ' =?', $workflowId)
            ->group(Db_WorkflowTransition::TABLE_NAME . '.' . Db_WorkflowTransition::ID);
        return $this->db->fetchRow($select);
    }

    public function getActiveTransitionsViaItem($instanceId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowItem::TABLE_NAME, array())
            ->join(Db_WorkflowTransition::TABLE_NAME, Db_WorkflowItem::TABLE_NAME . '.' . Db_WorkflowItem::WORKFLOW_TRANSITION_ID . ' = ' . Db_WorkflowTransition::TABLE_NAME . '.' . Db_WorkflowTransition::ID)
            ->where(Db_WorkflowItem::TABLE_NAME . '.' . Db_WorkflowItem::WORKFLOW_CASE_ID . ' =?', $instanceId)
            ->where(Db_WorkflowItem::TABLE_NAME . '.' . Db_WorkflowItem::STATUS . ' =?', Dao_Workflow::WORKFLOW_ITEM_STATUS_IN_PROGRESS);
        return $this->db->fetchAll($select);
    }

    public function getActivePlacesViaToken($instanceId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowToken::TABLE_NAME, array())
            ->join(Db_WorkflowPlace::TABLE_NAME, Db_WorkflowPlace::TABLE_NAME . '.' . Db_WorkflowPlace::ID . ' = ' . Db_WorkflowToken::TABLE_NAME . '.' . Db_WorkflowToken::WORKFLOW_PLACE_ID)
            ->where(Db_WorkflowToken::TABLE_NAME . '.' . Db_WorkflowToken::WORKFLOW_CASE_ID . ' =?', $instanceId)
            ->where(Db_WorkflowToken::TABLE_NAME . '.' . Db_WorkflowToken::STATUS . ' =?', Dao_Workflow::WORKFLOW_TOKEN_STATUS_FREE)
            ->orWhere(Db_WorkflowToken::TABLE_NAME . '.' . Db_WorkflowToken::STATUS . ' =?', Dao_Workflow::WORKFLOW_TOKEN_STATUS_LOCKED);
        return $this->db->fetchAll($select);
    }


    public function getWorkflowForCronjob()
    {
        $select = $this->db->select();
        $select->from(Db_Workflow::TABLE_NAME);
        $select->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::IS_ACTIVE . ' =?', '1');
        $select->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::TRIGGER_TIME . ' =?', '1');
        $select->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::EXECUTION_TIME . ' IS NOT NULL');
        $select->joinLeft(Db_Cron::TABLE_NAME, Db_Cron::TABLE_NAME . '.' . Db_Cron::MAPPING_ID . ' = ' . Db_Workflow::TABLE_NAME . '.' . Db_Workflow::ID . ' AND ' . Db_Cron::TYPE . ' = "workflow"', array('cronId' => Db_Cron::ID, Db_Cron::LAST_EXECUTION));
        return $this->db->fetchAll($select);
    }

    public function insertWorkflowImportsForCronjob($data)
    {
        $cron = new Db_Cron();
        $cron->insert($data);
    }

    public function updateWorkflowImportsForCronjob($data, $cronId)
    {
        $cron  = new Db_Cron();
        $where = array(Db_Cron::ID . ' = ?' => $cronId);
        $cron->update($data, $where);
    }

    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_Workflow::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_Workflow::NAME . ' LIKE ?', $value);

        if($id > 0) {
            $select->where(Db_Workflow::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }

    // handling for customization modul
    public function getConfiguredWorkflowMappings($type, $method, $mappingId)
    {
        $select = $this->db->select();
        $select->from(Db_Workflow::TABLE_NAME);
        $select->join(
            Db_WorkflowTrigger::TABLE_NAME,
            Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::WORKFLOW_ID . ' = ' .
            Db_Workflow::TABLE_NAME . '.' . Db_Workflow::ID,
            array(Db_WorkflowTrigger::FILEIMPORT_REGEX => Db_WorkflowTrigger::FILEIMPORT_REGEX)
        );

        $methods = array($method);

        switch ($type) {
            case 'ci':
                $select->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' = ' . Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID, array('mappingDescription' => Db_CiType::DESCRIPTION, 'mappingName' => Db_CiType::NAME));
                $select->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::TRIGGER_CI . ' =?', '1');
                break;
            case 'ci_type_change':
                $select->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' = ' . Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID, array('mappingDescription' => Db_CiType::DESCRIPTION, 'mappingName' => Db_CiType::NAME));
                $select->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::TRIGGER_CI_TYPE_CHANGE . ' =?', '1');
                break;
            case 'relation':
                $select->join(Db_CiRelationType::TABLE_NAME, Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ' . Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID, array('mappingDescription' => Db_CiRelationType::DESCRIPTION, 'mappingName' => Db_CiRelationType::NAME));
                $select->where(Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::IS_ACTIVE . ' =?', '1');
                $select->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::TRIGGER_RELATION . ' =?', '1');
                break;
            case 'attribute':
                $select->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID, array('mappingDescription' => Db_Attribute::DESCRIPTION, 'mappingName' => Db_Attribute::NAME));
                $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_ACTIVE . ' =?', '1');
                $select->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::TRIGGER_ATTRIBUTE . ' =?', '1');
                break;
            case 'project':
                $select->join(Db_Project::TABLE_NAME, Db_Project::TABLE_NAME . '.' . Db_Project::ID . ' = ' . Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID, array('mappingDescription' => Db_Project::DESCRIPTION, 'mappingName' => Db_Project::NAME));
                $select->where(Db_Project::TABLE_NAME . '.' . Db_Project::IS_ACTIVE . ' =?', '1');
                $select->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::TRIGGER_PROJECT . ' =?', '1');
                break;
            case Db_WorkflowTrigger::TYPE_FILEIMPORT:
                array_push($methods, Db_WorkflowTrigger::METHOD_BEFORE_AND_AFTER_IMPORT);
                break;
        }

        $select->where(Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::TYPE . ' =?', $type);
        $select->where(Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::METHOD . ' IN (?)', $methods);
        if (isset($mappingId)) {
            $select->where(Db_WorkflowTrigger::TABLE_NAME . '.' . Db_WorkflowTrigger::MAPPING_ID . ' =?', $mappingId);
        }
        $select->where(Db_Workflow::TABLE_NAME . '.' . Db_Workflow::IS_ACTIVE . ' =?', '1');

        return $this->db->fetchAll($select);
    }

    /**
     * Gets the method and regex the specified workflow.
     *
     * Used to restore the file import data when updating file import trigger
     *
     * @param $workflow_id
     *
     * @return mixed
     */
    public function getWorkflowFileimportTriggerInfoByWorkflowId($workflow_id)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowTrigger::TABLE_NAME, array(Db_WorkflowTrigger::METHOD, Db_WorkflowTrigger::FILEIMPORT_REGEX))
            ->where(Db_WorkflowTrigger::WORKFLOW_ID . ' =?', $workflow_id)
            ->where(Db_WorkflowTrigger::MAPPING_ID . " IS NULL")
            ->where(Db_WorkflowTrigger::TYPE . ' =?', Db_WorkflowTrigger::TYPE_FILEIMPORT);
        return $this->db->fetchRow($select);
    }

    public function updateWorkflowItem($itemId, $data)
    {
        if (isset($data[Db_WorkflowItem::CONTEXT]) && is_array($data[Db_WorkflowItem::CONTEXT])) {
            $data[Db_WorkflowItem::CONTEXT] = json_encode($data[Db_WorkflowItem::CONTEXT]);
        }

        if (isset($data[Db_WorkflowItem::WORKFLOW_ARG_CONTEXT]) && is_array($data[Db_WorkflowItem::WORKFLOW_ARG_CONTEXT])) {
            $data[Db_WorkflowItem::WORKFLOW_ARG_CONTEXT] = json_encode($data[Db_WorkflowItem::WORKFLOW_ARG_CONTEXT]);
        }

        $where = $this->db->quoteInto(Db_WorkflowItem::ID . ' =?', $itemId);

        $table = new Db_WorkflowItem();
        $table->update($data, $where);
    }

    public function getWorkflowItemByInstanceId($instanceId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowItem::TABLE_NAME)
            ->where(Db_WorkflowItem::WORKFLOW_CASE_ID . ' =?', $instanceId);
        return $this->db->fetchRow($select);
    }


    /**
     * @param $itemId int id of the workflow item
     *
     * @return array contains only the workflow case id, no other information
     */
    public function getWorkflowInstanceIdByItemId($itemId)
    {
        $select = $this->db->select()
            ->from(Db_WorkflowItem::TABLE_NAME, Db_WorkflowCase::TABLE_NAME . "." . Db_WorkflowCase::ID)
            ->join(Db_WorkflowCase::TABLE_NAME, Db_WorkflowCase::TABLE_NAME . '.' . Db_WorkflowCase::ID . ' = ' . Db_WorkflowItem::TABLE_NAME . '.' . Db_WorkflowItem::WORKFLOW_CASE_ID, array())
            ->where(Db_WorkflowItem::TABLE_NAME . '.' . Db_WorkflowItem::ID . ' =?', $itemId);
        return $this->db->fetchRow($select)[Db_WorkflowCase::ID];
    }
}
