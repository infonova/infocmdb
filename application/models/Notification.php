<?php

class Dao_Notification extends Dao_Abstract
{

    public function deleteNotificationsByNotificationId($id, $type)
    {
        $table = new Db_Notification();
        $where = $this->db->quoteInto(Db_Notification::NOTIFICATION_ID . ' = ?',
                $id) . ' AND ' . $this->db->quoteInto(
                Db_Notification::NOTIFICATION_TYPE . ' = ?', $type);
        return $table->delete($where);
    }

    public function getNotificationRecipients($mailId)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_Notification::TABLE_NAME,
                array(
                    Db_Notification::ADDRESS => new Zend_Db_Expr("if(isnull(" . Db_Notification::TABLE_NAME .
                        "." . Db_Notification::ADDRESS . "), " .
                        Db_User::TABLE_NAME . "." . Db_User::EMAIL . "," .
                        Db_Notification::TABLE_NAME . "." .
                        Db_Notification::ADDRESS . ")"),
                ))
            ->joinLeft(Db_User::TABLE_NAME,
                Db_Notification::TABLE_NAME . "." . Db_Notification::USER_ID .
                "=" . Db_User::TABLE_NAME . "." . Db_User::ID,
                array(
                    Db_User::ID,
                ))
            ->where(Db_Notification::NOTIFICATION_TYPE . ' = "mail"')
            ->where(Db_Notification::NOTIFICATION_ID . ' =?', $mailId)
            ->having("address !='' and address is not null");

        return $this->db->fetchAll($select);
    }

    public function getNotificationEmailAddresses($mailId)
    {
        $select    = $this->db->select()
            ->from(Db_Notification::TABLE_NAME,
                array(
                    Db_Notification::ADDRESS,
                ))
            ->where(Db_Notification::NOTIFICATION_TYPE . ' = "mail"')
            ->where(Db_Notification::NOTIFICATION_ID . ' = ?', $mailId)
            ->where(
                Db_Notification::ADDRESS . "!='' and " . Db_Notification::ADDRESS .
                " is not null");
        $addresses = $this->db->fetchAll($select);
        return $addresses;
    }

    public function getReportingRecipients($reportingId)
    {
        $select = $this->db->select()
            ->from(Db_Notification::TABLE_NAME)
            ->where(Db_Notification::NOTIFICATION_TYPE . ' = "reporting"')
            ->where(Db_Notification::TYPE . ' = "mail"')
            ->where(Db_Notification::NOTIFICATION_ID . ' =?', $reportingId);
        return $this->db->fetchAll($select);
    }

    public function insertNotification($data)
    {
        $notification = new Db_Notification();
        return $notification->insert($data);
    }

    public function getNotificationRecipientsMultiSelect($mailId)
    {
        $select = $this->db->select()
            ->from(Db_User::TABLE_NAME,
                array(
                    "userId" => Db_User::ID,
                    Db_User::EMAIL,
                ))
            ->joinLeft(Db_Notification::TABLE_NAME,
                Db_Notification::TABLE_NAME . "." . Db_Notification::USER_ID .
                "=" . Db_User::TABLE_NAME . "." . Db_User::ID . " and " .
                Db_Notification::NOTIFICATION_TYPE . " = 'mail' and " .
                Db_Notification::NOTIFICATION_ID . "='" . $mailId . "'",
                array(
                    "notificationId" => Db_Notification::ID,
                ))
            ->where(
                Db_User::TABLE_NAME . "." . Db_User::EMAIL . "!='' and " .
                Db_User::TABLE_NAME . "." . Db_User::EMAIL . " is not null");
        return $this->db->fetchAll($select);
    }
}