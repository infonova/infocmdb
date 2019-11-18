<?php

/**
 *
 *
 *
 */
class Service_Announcement_Create extends Service_Abstract
{
    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1502, $themeId);
    }

    public function getCreateAnnouncementForm()
    {
        $templates = array();

        $templateDao = new Dao_Template();
        $templates   = $templateDao->getTemplates();

        $templateList       = array();
        $templateList[null] = '';
        foreach ($templates as $template) {
            $templateList[$template[Db_Templates::ID]] = $template[Db_Templates::NAME];
        }

        return new Form_Announcement_Create($this->translator, $templateList);
    }

    public function insertAnnouncement($values, $user)
    {
        $announcement = array();
        $message_de   = array();
        $message_en   = array();

        // announcement
        $announcement[Db_Announcement::NAME]           = $values['name'];
        $announcement[Db_Announcement::SHOW_FROM_DATE] = $values['show_from_date'];
        $announcement[Db_Announcement::SHOW_TO_DATE]   = $values['show_to_date'];
        $announcement[Db_Announcement::TYPE]           = $values['type'];
        $announcement[Db_Announcement::IS_ACTIVE]      = $values['valid'];
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

        $announcementId_create = $announcementDaoImpl->insertAnnouncement($announcement);

        $announcementMessageDaoImpl->saveMessageForAnnouncement($announcementId_create, 'de', $message_de);
        $announcementMessageDaoImpl->saveMessageForAnnouncement($announcementId_create, 'en', $message_en);
    }
}