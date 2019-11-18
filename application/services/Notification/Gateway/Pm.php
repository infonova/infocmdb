<?php

class Notification_Gateway_Pm implements Notification_Gateway
{

    public static function send($reciever, $subject, $body, array $attachments = array(), array $parameter = array())
    {
        if (!$reciever || count($reciever) <= 0) {
            return;
        }

        $messageDaoImpl = new Dao_Message();

        $data                                  = array();
        $data[Db_PrivateMessage::FROM_USER_ID] = 0; // system
        $data[Db_PrivateMessage::SUBJECT]      = $subject;
        $data[Db_PrivateMessage::MESSAGE]      = nl2br($body);
        $data[Db_PrivateMessage::SENT]         = date('Y-m-d H:i:s', time());

        foreach ($reciever as $rec) {
            if ($rec['type'] == 'pm') {
                $data[Db_PrivateMessage::TO_USER_ID] = $rec['address'];
                $messageDaoImpl->insertMessage($data);
            }
        }
    }
}