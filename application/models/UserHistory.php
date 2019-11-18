<?php

class Dao_UserHistory extends Dao_Abstract
{

    public function createUserHistory($userHistory)
    {
        $table = new Db_UserHistory();
        return $table->insert($userHistory);
    }

    public function createUserHistoryAction($userHistory)
    {
        $table = new Db_UserHistoryAction();
        return $table->insert($userHistory);
    }

    public function getUserSession($sessionId)
    {
        $select = $this->db->select()
            ->from(Db_UserSession::TABLE_NAME, array(Db_UserSession::USER_ID, Db_UserSession::IP_ADDRESS))
            ->where(Db_UserSession::ID . ' =?', $sessionId);
        return $this->db->fetchRow($select);
    }

    public function updateUserSession($sessionId, $userId = null, $ipAddress = null)
    {
        if (!$ipAddress && !$userId)
            return;

        $data = array();
        if ($userId)
            $data[Db_UserSession::USER_ID] = $userId;
        if ($ipAddress)
            $data[Db_UserSession::IP_ADDRESS] = $ipAddress;

        $table = new Db_UserSession();
        $where = $this->db->quoteInto(Db_UserSession::ID . ' =?', $sessionId);
        $table->update($data, $where);
    }

    public function deleteSession($sessionId)
    {
        if (!$sessionId)
            return;

        try {
            $table = new Db_UserSession();
            $where = $this->db->quoteInto(Db_UserSession::ID . ' =?', $sessionId);
            $table->delete($where);
        } catch (Exception $e) {
            // ignore!
            return;
        }
    }
}