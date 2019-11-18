<?php

/**
 *
 *
 *
 */
class Service_Announcement_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1504, $themeId);
    }

    public function deleteAnnouncement($announcementId)
    {
        try {
            $announcementDao = new Dao_Announcement();
            $rows            = $announcementDao->deleteAnnouncement($announcementId);
            if ($rows != 1) {
                throw new Exception();
            }
        } catch (Exception $e) {
            throw new Exception_Announcement_DeleteFailed($e);
        }
    }
}