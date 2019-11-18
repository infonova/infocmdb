<?php

/**
 *
 *
 *
 */
class Service_Announcement_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1503, $themeId);
    }


    public function getUpdateAnnouncementForm($announcementId)
    {
        $templates = array();

        $templateDao = new Dao_Template();
        $templates   = $templateDao->getTemplates();

        $templateList       = array();
        $templateList[null] = '';
        foreach ($templates as $template) {
            $templateList[$template[Db_Templates::ID]] = $template[Db_Templates::NAME];
        }

        return new Form_Announcement_Update($this->translator, $templateList, null, $announcementId);
    }

    public function getAnnouncementForUpdateById($announcementId)
    {
        $announcementDao = new Dao_Announcement();
        return $announcementDao->getAnnouncementById($announcementId);
    }

    public function updateAnnouncement($values, $user, $announcementId = null)
    {
        $announcement = array();
        $message_de   = array();
        $message_en   = array();

        if ($announcementId) {
            $announcement[Db_Announcement::ID]                   = $announcementId;
            $message_de[Db_AnnouncementMessage::ANNOUNCEMENT_ID] = $announcementId;
            $message_en[Db_AnnouncementMessage::ANNOUNCEMENT_ID] = $announcementId;
        }

        //  user has to accept announcement again
        if ($values['re_confirmation'] === '1') {
            $announcementUserDaoImpl = new Dao_AnnouncementUser();
            $announcementUserDaoImpl->deleteAnnouncementUser($announcementId);
        }

        // announcement
        $announcement[Db_Announcement::NAME]           = $values['name'];
        $announcement[Db_Announcement::SHOW_FROM_DATE] = $values['show_from_date'];
        $announcement[Db_Announcement::SHOW_TO_DATE]   = $values['show_to_date'];
        $announcement[Db_Announcement::IS_ACTIVE]      = $values['valid'];
        $announcement[Db_Announcement::TYPE]           = $values['type'];
        $announcement[Db_Announcement::USER_ID]        = $user;

        // german message
        $message_de[Db_AnnouncementMessage::TITLE]   = $values['title_de'];
        $message_de[Db_AnnouncementMessage::MESSAGE] = $values['message_de'];
        $message_de[Db_AnnouncementMessage::USER_ID] = $user;

        // english message
        $message_en[Db_AnnouncementMessage::TITLE]   = $values['title_en'];
        $message_en[Db_AnnouncementMessage::MESSAGE] = $values['message_en'];
        $message_en[Db_AnnouncementMessage::USER_ID] = $user;

        $announcementDaoImpl        = new Dao_Announcement();
        $announcementMessageDaoImpl = new Dao_AnnouncementMessage();

        $announcementDaoImpl->updateAnnouncement($announcementId, $announcement);

        $announcementMessageDaoImpl->saveMessageForAnnouncement($announcementId, 'de', $message_de);
        $announcementMessageDaoImpl->saveMessageForAnnouncement($announcementId, 'en', $message_en);
    }
}