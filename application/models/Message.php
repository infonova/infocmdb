<?php

class Dao_Message extends Dao_Abstract
{


    public function getCountNewMessages($userId)
    {
        $select = $this->db->select()
            ->from(Db_PrivateMessage::TABLE_NAME, array('cnt' => 'COUNT(*)'))
            ->where(Db_PrivateMessage::TO_USER_ID . ' =?', $userId)
            ->where(Db_PrivateMessage::TABLE_NAME . '.' . Db_PrivateMessage::READ . ' IS NULL')
            ->where(Db_PrivateMessage::TABLE_NAME . '.' . Db_PrivateMessage::IS_DELETED . ' =?', '0');

        return $this->db->fetchRow($select);
    }

    public function getMessagesForPagination($userId)
    {
        $select = $this->db->select()
            ->from(Db_PrivateMessage::TABLE_NAME)
            ->joinLeft(Db_User::TABLE_NAME, Db_User::TABLE_NAME . '.' . Db_User::ID . ' = ' . Db_PrivateMessage::TABLE_NAME . '.' . Db_PrivateMessage::FROM_USER_ID, array(Db_User::USERNAME))
            ->where(Db_PrivateMessage::TO_USER_ID . ' =?', $userId)
            ->where(Db_PrivateMessage::IS_DELETED . ' =?', '0')
            ->order(Db_PrivateMessage::SENT . ' DESC');

        return $select;
    }


    public function getOutMessagesForPagination($userId)
    {
        $select = $this->db->select()
            ->from(Db_PrivateMessage::TABLE_NAME)
            ->joinLeft(Db_User::TABLE_NAME, Db_User::TABLE_NAME . '.' . Db_User::ID . ' = ' . Db_PrivateMessage::TABLE_NAME . '.' . Db_PrivateMessage::TO_USER_ID, array(Db_User::USERNAME))
            ->where(Db_PrivateMessage::FROM_USER_ID . ' =?', $userId);

        return $select;
    }

    public function getMessage($messageId)
    {
        $select = $this->db->select()
            ->from(Db_PrivateMessage::TABLE_NAME)
            ->joinLeft(Db_User::TABLE_NAME, Db_User::TABLE_NAME . '.' . Db_User::ID . ' = ' . Db_PrivateMessage::TABLE_NAME . '.' . Db_PrivateMessage::FROM_USER_ID, array(Db_User::USERNAME))
            ->where(Db_PrivateMessage::TABLE_NAME . '.' . Db_PrivateMessage::ID . ' =?', $messageId);

        return $this->db->fetchRow($select);
    }

    public function insertMessage($data)
    {
        $table = new Db_PrivateMessage();
        return $table->insert($data);
    }

    public function updateMessage($messageId, $data)
    {
        $table = new Db_PrivateMessage();
        $where = $table->getAdapter()->quoteInto(Db_PrivateMessage::TABLE_NAME . '.' . Db_PrivateMessage::ID . ' = ?', $messageId);
        return $table->update($data, $where);
    }

    public function deleteMessage($messageId)
    {
        $table                               = new Db_PrivateMessage();
        $data                                = array();
        $data[Db_PrivateMessage::IS_DELETED] = '1';
        $where                               = $this->db->quoteInto(Db_PrivateMessage::ID . ' =?', $messageId);
        return $table->update($data, $where);
    }

    public function getThemeDescriptionById($themeId)
    {

        $select = $this->db->select()
            ->from(Db_Theme::TABLE_NAME, array(Db_Theme::DESCRIPTION))
            ->where(Db_Theme::ID . ' =?', $themeId);
        return $this->db->fetchRow($select);
    }
}