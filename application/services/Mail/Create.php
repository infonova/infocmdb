<?php

/**
 *
 *
 *
 */
class Service_Mail_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3202, $themeId);
    }

    public function getRecipientForm($userList = array())
    {
        return new Form_Mail_Recipients($this->translator, $userList);
    }

    public function getCreateMailForm()
    {
        $templates = array();

        $templateDao = new Dao_Template();
        $templates   = $templateDao->getTemplates();

        $templateList       = array();
        $templateList[null] = '';
        foreach ($templates as $template) {
            $templateList[$template[Db_Templates::ID]] = $template[Db_Templates::NAME];
        }

        return new Form_Mail_Create($this->translator, $templateList);
    }

    public function deleteNotificationByMailId($mailId)
    {
        $notificationDao = new Dao_Notification();
        $notificationDao->deleteNotification($mailId);
    }

    public function insertRecipients($formData, $mailId)
    {
        $notificationDao = new Dao_Notification();

        if (!$mailId)
            throw new Exception_Notification_IdInvalid();

        $notificationDao->deleteNotificationsByNotificationId($mailId, 'mail');

        if ($formData['customRecipients']) {
            $list = explode("\n", $formData['customRecipients']);
            foreach ($list as $mailAddress) {
                $notificationId = $notificationDao->insertNotification(
                    array(
                        Db_Notification::NOTIFICATION_TYPE => 'mail',
                        Db_Notification::ADDRESS           => trim($mailAddress),
                        Db_Notification::TYPE              => 'mail',
                        Db_Notification::NOTIFICATION_ID   => $mailId,
                    ));
            }
        }

        if ($formData['userRecipients']) {
            $userList = $formData['userRecipients'];
            foreach ($userList as $userId) {
                $notificationDao->insertNotification(
                    array(
                        Db_Notification::NOTIFICATION_TYPE => 'mail',
                        Db_Notification::USER_ID           => $userId,
                        Db_Notification::TYPE              => 'mail',
                        Db_Notification::NOTIFICATION_ID   => $mailId,
                    ));
            }
        }
    }

    public function insertMail($formData)
    {
        $data                       = array();
        $data[Db_Mail::NAME]        = $formData['name'];
        $data[Db_Mail::DESCRIPTION] = $formData['description'];
        $data[Db_Mail::NOTE]        = $formData['note'];
        $data[Db_Mail::SUBJECT]     = $formData['subject'];
        $data[Db_Mail::MIME_TYPE]   = $formData['mime_type'];
        $data[Db_Mail::EDITOR]      = $formData['editor'];
        $data[Db_Mail::TEMPLATE]    = $formData['template'];
        $data[Db_Mail::BODY]        = $formData['body'];

        $mailDao = new Dao_Mail();
        $mailId  = $mailDao->insertMail($data);

        if (!$mailId)
            throw new Exception_Mail_InsertFailed();

        $this->insertRecipients($formData, $mailId);

        return $mailId;
    }
}