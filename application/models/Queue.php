<?php

class Dao_Queue extends Dao_Abstract
{

    const MESSAGE_IDLE        = 'idle';
    const MESSAGE_IN_PROGRESS = 'in_progress';
    const MESSAGE_COMPLETED   = 'completed';
    const MESSAGE_FAILED      = 'failed';

    public function getIdleMessage($queueName)
    {
        $select = $this->db->select()
            ->forUpdate()
            ->from(Db_QueueMessage::TABLE_NAME)
            ->join(Db_Queue::TABLE_NAME, Db_Queue::TABLE_NAME . '.' . Db_Queue::ID . ' = ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::QUEUE_ID, array())
            ->where(Db_Queue::TABLE_NAME . '.' . Db_Queue::NAME . ' =?', $queueName)
            ->where(Db_Queue::TABLE_NAME . '.' . Db_Queue::IS_ACTIVE . ' =?', '1')
            ->where(Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::STATUS . ' =?', self::MESSAGE_IDLE)
            ->where(Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::EXECUTION_TIME . ' <= NOW() OR ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::EXECUTION_TIME . ' IS NULL')
            // TODO: reactivate as soon as retries of failed messages are allowed!
//							->where('NOT '.Db_QueueMessage::TABLE_NAME.'.'.Db_QueueMessage::STATUS.' =?', self::MESSAGE_COMPLETED)
//							->where('NOT '.Db_QueueMessage::TABLE_NAME.'.'.Db_QueueMessage::STATUS.' =?', self::MESSAGE_IN_PROGRESS)
//							->where('NOT '.Db_QueueMessage::TABLE_NAME.'.'.Db_QueueMessage::STATUS.' =? OR ('.Db_QueueMessage::TABLE_NAME.'.'.Db_QueueMessage::TIMEOUT.' <= "'.time().'" AND '.Db_QueueMessage::TABLE_NAME.'.'.Db_QueueMessage::STATUS.' = "'.self::MESSAGE_FAILED.'")', self::MESSAGE_FAILED)
            ->limit(1)
            ->order(Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::PRIORITY . ' ASC');
        return $this->db->fetchRow($select);
    }


    public function searchActiveMessages($queueName, $searchString)
    {
        $select = $this->db->select()
            ->from(Db_QueueMessage::TABLE_NAME, array('cnt' => 'COUNT(' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::ID . ')'))
            ->join(Db_Queue::TABLE_NAME, Db_Queue::TABLE_NAME . '.' . Db_Queue::ID . ' = ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::QUEUE_ID, array())
            ->where(Db_Queue::TABLE_NAME . '.' . Db_Queue::NAME . ' =?', $queueName)
            ->where(Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::ARGS . ' LIKE "%' . $searchString . '%"')
            ->where('NOT ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::STATUS . ' =?', self::MESSAGE_COMPLETED)
            ->where('NOT ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::STATUS . ' =? OR (' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::TIMEOUT . ' <= "' . time() . '" AND ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::STATUS . ' = "' . self::MESSAGE_FAILED . '")', self::MESSAGE_FAILED)
            ->group(Db_Queue::TABLE_NAME . '.' . Db_Queue::NAME);
        return $this->db->fetchRow($select);
    }

    public function searchActiveMessagesForReporting($queueName, $searchString)
    {
        $select = $this->db->select()
            ->from(Db_QueueMessage::TABLE_NAME, array('cnt' => 'COUNT(' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::ID . ')'))
            ->join(Db_Queue::TABLE_NAME, Db_Queue::TABLE_NAME . '.' . Db_Queue::ID . ' = ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::QUEUE_ID, array())
            ->where(Db_Queue::TABLE_NAME . '.' . Db_Queue::NAME . ' =?', $queueName)
            ->where(Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::ARGS . ' LIKE "%' . $searchString . '%"')
            ->where('NOT ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::STATUS . ' =?', self::MESSAGE_COMPLETED)
            ->where('NOT ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::STATUS . ' =?', self::MESSAGE_FAILED)
            ->where('NOT (' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::TIMEOUT . ' <= "' . time() . '" AND ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::STATUS . ' = ?)', self::MESSAGE_IN_PROGRESS)
            ->group(Db_Queue::TABLE_NAME . '.' . Db_Queue::NAME);
        return $this->db->fetchRow($select);
    }

    public function searchActiveMessagesForFileimport($queueName, $file, $method, $historyId = null)
    {
        $select = $this->db->select()
            ->from(Db_QueueMessage::TABLE_NAME, array('cnt' => 'COUNT(' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::ID . ')'))
            ->join(Db_Queue::TABLE_NAME, Db_Queue::TABLE_NAME . '.' . Db_Queue::ID . ' = ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::QUEUE_ID, array())
            ->where(Db_Queue::TABLE_NAME . '.' . Db_Queue::NAME . ' =?', $queueName)
            ->where(Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::ARGS . ' LIKE "%' . $file . '%"')
            ->where(Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::ARGS . ' LIKE "%' . $method . '%"');

        if ($historyId)
            $select->where(Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::ARGS . ' LIKE "%' . $historyId . '%"');

        $select->where('NOT ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::STATUS . ' =?', self::MESSAGE_COMPLETED)
            ->where('NOT ' . Db_QueueMessage::TABLE_NAME . '.' . Db_QueueMessage::STATUS . ' =?', self::MESSAGE_FAILED)
            ->group(Db_Queue::TABLE_NAME . '.' . Db_Queue::NAME);
        return $this->db->fetchRow($select);
    }

    public function setMessageStatus($id, $status, $timeout = null, $execute_time = null)
    {
        // db connection may be auto closed
        $this->reconnect();

        if (!$timeout)
            $timeout = time() + 200;

        if (!$execute_time)
            $execute_time = 'NOW()';

        $query = "UPDATE " . Db_QueueMessage::TABLE_NAME . " 
		SET " . Db_QueueMessage::STATUS . " = '" . $status . "', 
			" . Db_QueueMessage::TIMEOUT . " = '" . $timeout . "',
			" . Db_QueueMessage::EXECUTION_TIME . " = " . $execute_time . "
		WHERE " . Db_QueueMessage::ID . " = '" . $id . "' ";
        return $this->db->query($query);
    }

    public function insertMessage($message)
    {
        $table = new Db_QueueMessage();
        return $table->insert($message->toArray());
    }

    public function deleteOldMessages()
    {
        $table = new Db_QueueMessage();

        return $table->delete('(' . Db_QueueMessage::STATUS . ' = "' . self::MESSAGE_COMPLETED . '" OR ' . Db_QueueMessage::STATUS . ' = "' . self::MESSAGE_FAILED . '") AND ' . Db_QueueMessage::EXECUTION_TIME . ' <= DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }


    public function deleteOldSearchResults()
    {
        $time = time();

        $sql = "DELETE FROM " . Db_SearchResult::TABLE_NAME . " 
				WHERE " . Db_SearchResult::SESSION . " IN (
														SELECT " . Db_SearchSession::ID . " 
														FROM " . Db_SearchSession::TABLE_NAME . " 
														WHERE " . Db_SearchSession::TIMEOUT . " < '" . $time . "'
													)";
        $this->db->query($sql);

        $sql = "DELETE FROM " . Db_SearchSession::TABLE_NAME . " 
				WHERE " . Db_SearchSession::TIMEOUT . " < '" . $time . "'";
        $this->db->query($sql);
    }

    public function deleteOldSessions()
    {
        $t     = time() - (6 * 60 * 60);
        $table = new Db_UserSession();
        $where = $table->getAdapter()->quoteInto(Db_UserSession::MODIFIED . ' <= ?', $t);
        return $table->delete($where);
    }


    public function deleteOldApiSessions()
    {

        $sql = "DELETE FROM " . Db_ApiSession::TABLE_NAME . "
		 		WHERE (FROM_UNIXTIME(" . Db_ApiSession::TABLE_NAME . "." . Db_ApiSession::VALID_TO . ") < NOW());";
        $this->db->query($sql);

    }

    public function deleteOldPasswordResetRequests()
    {
        $sql = "DELETE FROM " . Db_PasswordReset::TABLE_NAME . "
                    WHERE " . Db_PasswordReset::VALID_TO . " < now();";
        $this->db->query($sql);
    }

    public function deleteOldImportFileValidations($maxAge)
    {

        $sql = "DELETE FROM " . Db_ImportFileValidation::TABLE_NAME . "
		 		WHERE DATE_ADD(" . Db_ImportFileValidation::FINALIZED . ", INTERVAL " . $maxAge . " HOUR) < now()";
        $this->db->query($sql);

    }

    public function getQueue($id)
    {
        $sql = $this->db->select()
            ->from(Db_Queue::TABLE_NAME)
            ->where(Db_Queue::TABLE_NAME . '.' . Db_Queue::ID . ' =?', $id);

        return $this->db->fetchRow($sql);
    }

    /**
     * FROM Db_ImportFileHistory
     * get all entries where filename matches regex where (Db_ImportFileHistory::CREATED + $maxAge)
     * is smaller than current time --> entries are too old
     *
     * @param string $regex  without slashes!
     * @param int    $maxAge in hours
     *
     * @return array Database Rows
     */
    public function getImportTooOld($regex, $maxAge)
    {
        // necessary variable is not set, return!
        if (!isset($maxAge)) {
            return false;
        }
        $sql = "SELECT " . Db_ImportFileHistory::ID . " FROM " . Db_ImportFileHistory::TABLE_NAME . " WHERE ";

        // regex matching the filename
        if ($regex) {
            $sql .= " " . Db_ImportFileHistory::FILENAME . " REGEXP '" . $regex . "' AND ";
        }

        // adding max age to CREATED, if the resulting timestamp is less than the current time -> entry is too old
        $sql .= " DATE_ADD(" . Db_ImportFileHistory::CREATED . ", INTERVAL " . $maxAge . " HOUR) < now();";

        return $this->db->fetchAll($sql);
    }

    /**
     * FROM Db_ImportFileHistory
     * get all entries (where filename matches regex) LIMIT $maxCount, 2^64-1
     * --> ordering entries by ID descending, offset is maxCount, 2^64-1 to get all other rows
     *
     * @see http://stackoverflow.com/a/271650
     *
     * @param string $regex without slashes!
     * @param int    $maxCount
     *
     * @return array Database Rows
     */
    public function getImportTooMany($regex, $maxCount)
    {
        // necessary variable is not set, return!
        if (!isset($maxCount)) {
            return false;
        }
        $sql = "SELECT " . Db_ImportFileHistory::ID . " FROM " . Db_ImportFileHistory::TABLE_NAME . " ";

        // regex matching the filename
        if ($regex) {
            $sql .= " WHERE " . Db_ImportFileHistory::FILENAME . " REGEXP '" . $regex . "' ";
        }

        // ordering by ID desc, offset is $maxCount (keeping the newest $maxCount entries), limit to biggest possible value (2^64-1) to get all other entries
        $sql .= " ORDER BY " . Db_ImportFileHistory::ID . " DESC LIMIT " . $maxCount . ", 18446744073709551615 ;";

        return $this->db->fetchAll($sql);
    }

    /**
     * deleting all entries from Db_ImportFileHistory && Db_ImportFileHistoryDetail with id / IMPORT_FILE_HISTORY_ID = $id
     *
     * @param int $id
     *
     * @return query result
     */
    public function deleteImportFileHistory($id)
    {
        $sql = "DELETE FROM " . Db_ImportFileHistory::TABLE_NAME . " WHERE " . Db_ImportFileHistory::ID . " = " . $id . "; ";
        $sql .= "DELETE FROM " . Db_ImportFileHistoryDetail::TABLE_NAME . " WHERE " . Db_ImportFileHistoryDetail::IMPORT_FILE_HISTORY_ID . " = " . $id . "; ";

        return $this->db->query($sql);
    }


    public function getTooOldWorkflowCases($maxAge, $regex = null)
    {

        $sql = "SELECT " . Db_WorkflowCase::TABLE_NAME . "." . Db_WorkflowCase::ID . " AS workflow_case_id, " . Db_Workflow::TABLE_NAME . "." . Db_Workflow::ID . " AS workflow_id FROM " . Db_Workflow::TABLE_NAME .
            " LEFT JOIN " . Db_WorkflowCase::TABLE_NAME . " ON " . Db_Workflow::TABLE_NAME . "." . Db_Workflow::ID . " = " . Db_WorkflowCase::TABLE_NAME . "." . Db_WorkflowCase::WORKFLOW_ID .
            " WHERE ";

        // regex matching the workflow name
        if ($regex) {
            $sql .= " " . Db_WorkflowCase::NAME . " REGEXP '" . $regex . "' AND ";
        }

        // adding max age to CREATED, if the resulting timestamp is less than the current time -> entry is too old
        $sql .= " DATE_ADD(" . Db_WorkflowCase::CREATED . ", INTERVAL " . $maxAge . " HOUR) < now()";

        $sql .= " LIMIT 1000";

        return $this->db->fetchAll($sql);
    }
}