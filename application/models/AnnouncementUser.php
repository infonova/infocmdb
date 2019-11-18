<?php


class Dao_AnnouncementUser extends Dao_Abstract
{
    public function updateAnnouncementUser($announcementId, $userId, $announcement)
    {
        $table   = new Db_AnnouncementUser();
        $where   = array();
        $where[] = $table->getAdapter()->quoteInto(Db_AnnouncementUser::ANNOUNCEMENT_ID . ' = ?', $announcementId);
        $where[] = $table->getAdapter()->quoteInto(Db_AnnouncementUser::USER_ID . ' = ?', $userId);
        $table->update($announcement, $where);
    }

    public function insertAnnouncementUser($announcement)
    {
        $table = new Db_AnnouncementUser();
        $table->insert($announcement);
    }

    public function deleteAnnouncementUser($announcementId, $userId = null)
    {
        $table   = new Db_AnnouncementUser();
        $where   = array();
        $where[] = $table->getAdapter()->quoteInto(Db_AnnouncementUser::ANNOUNCEMENT_ID . ' = ?', (integer)$announcementId);
        if (!empty($userId)) {
            $where[] = $table->getAdapter()->quoteInto(Db_AnnouncementUser::USER_ID . ' = ?', (integer)$userId);
        }

        return $table->delete($where);
    }

    public function getAllActiveAnnouncementIds()
    {
        $dbAnnouncement = new Db_Announcement();
        $select         = $dbAnnouncement->select(Db_Announcement::TABLE_NAME);
        $select
            ->where('is_active = ?', '1')
            ->where('show_from_date <= NOW()')
            ->where('show_to_date >= NOW()');

        //  fetchCol returns array of first column
        return $this->db->fetchCol($select);
    }

    public function userHasAcceptedAnnouncement($userId = null, $announcementId = null)
    {
        $table  = new Db_AnnouncementUser();
        $select = $table->select(Db_AnnouncementUser::TABLE_NAME);

        $select
            ->where('user_id = ?', $userId)
            ->where('announcement_id = ?', $announcementId)
            ->where('accept = ?', '1');

        $row = $this->db->fetchRow($select);

        /*
         *  true: row exists because user has accepted announcement already
         *  false: row does not exist or column "accept" contains NULL or 0
         */
        if ($row) {
            return true;
        } else {
            return false;
        }
    }

    public function userSetAnnouncementAction($accepted, $userId, $acceptedAnnouncementId)
    {
        $table  = new Db_AnnouncementUser();
        $select = $table->select(Db_AnnouncementUser::TABLE_NAME);
        $select
            ->where('user_id = ?', $userId)
            ->where('announcement_id = ?', $acceptedAnnouncementId);

        $row = $this->db->fetchRow($select);

        $acceptedAnnouncement                              = array();
        $acceptedAnnouncement[Db_AnnouncementUser::ACCEPT] = $accepted;

        $announcementUserDaoImpl = new Dao_AnnouncementUser();

        // Update or insert announcement
        if ($row) {
            $announcementUserDaoImpl->updateAnnouncementUser($acceptedAnnouncementId, $userId, $acceptedAnnouncement);
        } else {
            $acceptedAnnouncement[Db_AnnouncementUser::ANNOUNCEMENT_ID] = $acceptedAnnouncementId;
            $acceptedAnnouncement[Db_AnnouncementUser::USER_ID]         = $userId;

            $announcementUserDaoImpl->insertAnnouncementUser($acceptedAnnouncement);

        }
    }
}