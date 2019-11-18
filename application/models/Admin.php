<?php

class Dao_Admin extends Dao_Abstract
{

    public function getSessionForPagination($orderBy = null, $direction = null, $filter = null)
    {
        $select = $this->db->select()
            ->from(Db_UserSession::TABLE_NAME)
            ->joinLeft(Db_User::TABLE_NAME, Db_User::TABLE_NAME . '.' . Db_User::ID . ' = ' . Db_UserSession::TABLE_NAME . '.' . Db_UserSession::USER_ID, array(Db_User::USERNAME));

        if ($filter) {
            $select = $select
                ->where(Db_UserSession::IP_ADDRESS . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_UserSession::MODIFIED . ' ASC');
        }

        return $select;
    }


    public function deleteSession($id)
    {
        $table = new Db_UserSession();
        $where = $this->db->quoteInto(Db_UserSession::ID . ' =?', $id);
        $table->delete($where);
    }

    public function deleteSessionsOfUser(int $userId)
    {
        $table = new Db_UserSession();
        $where = $this->db->quoteInto(Db_UserSession::USER_ID . ' =?', $userId);
        $table->delete($where);
    }
}