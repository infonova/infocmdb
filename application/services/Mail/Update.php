<?php

/**
 *
 *
 *
 */
class Service_Mail_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3203, $themeId);
    }

    public function getRecipientForm($userList = array())
    {
        return new Form_Mail_Recipients($this->translator, $userList);
    }

    public function getRecipientsMultiSelectByMailId($mailId)
    {
        $mailDao = new Dao_Notification();
        return $mailDao->getNotificationRecipientsMultiSelect($mailId);
    }

    public function getRecipientsByMailId($mailId)
    {
        $mailDao = new Dao_Notification();
        return $mailDao->getNotificationRecipients($mailId);
    }

    public function getMailById($mailId)
    {
        $mailDao = new Dao_Mail();
        return $mailDao->getMail($mailId);
    }

    public function getMailForUpdateById($mailId)
    {
        $mailDao = new Dao_Mail();
        $mail    = $mailDao->getMail($mailId);

        $notificationDao = new Dao_Notification();
        $addresses       = $notificationDao->getNotificationEmailAddresses($mailId);
        $addressesArray  = array();
        foreach ($addresses as $address) {
            array_push($addressesArray, $address["address"]);
        }

        if (empty($mail[Db_Mail::EDITOR])) {
            $mail[Db_Mail::EDITOR] = 'tiny_mce';
        }

        $mail['editor_enbaled'] = 1;
        if (
            $mail[Db_Mail::EDITOR] === 'none' ||
            ($mail[Db_Mail::EDITOR] === 'ace' && $mail[Db_Mail::MIME_TYPE] == Zend_Mime::TYPE_HTML)

        ) {
            $mail['editor_enbaled'] = 0;
        }

        $mail["customRecipients"] = implode("\n", $addressesArray);

        return $mail;
    }

    public function getUpdateMailForm()
    {
        $templates = array();

        $templateDao = new Dao_Template();
        $templates   = $templateDao->getTemplates();

        $templateList       = array();
        $templateList[null] = '';
        foreach ($templates as $template) {
            $templateList[$template[Db_Templates::ID]] = $template[Db_Templates::NAME];
        }

        return new Form_Mail_Update($this->translator, $templateList);
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

    public function updateMail($formData, $mailId)
    {
        if (!$mailId)
            throw new Exception_Mail_UpdateFailed();

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
        $mailDao->updateMail($data, $mailId);

        $this->insertRecipients($formData, $mailId);

        return $mailId;
    }
}