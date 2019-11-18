<?php


class Dao_AnnouncementMessage extends Dao_Abstract
{
    public function saveMessageForAnnouncement($announcementId, $language, $message = array())
    {
        $table = new Db_AnnouncementMessage();

        if (isset($message['announcement_id']) && !empty($message['announcement_id'])) {
            $where   = array();
            $where[] = $table->getAdapter()->quoteInto(Db_AnnouncementMessage::ANNOUNCEMENT_ID . ' = ?', $announcementId);
            $where[] = $table->getAdapter()->quoteInto(Db_AnnouncementMessage::LANGUAGE . ' = ?', $language);

            $table->update($message, $where);
        } else {
            $message[Db_AnnouncementMessage::LANGUAGE]        = $language;
            $message[Db_AnnouncementMessage::ANNOUNCEMENT_ID] = $announcementId;

            return $table->insert($message);
        }
    }
}