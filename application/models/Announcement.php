<?php


class Dao_Announcement extends Dao_Abstract
{
    /*
     * $search = 'my_cool_announcement'
     * $filters = array( 'is_active' => 1 )
     */
    public function getAllAnnouncements($search = null, $orderBy = 'id', $direction = 'asc')
    {
        $table  = new Db_Announcement();
        $select = $table->select(Db_Announcement::TABLE_NAME);

        $select->setIntegrityCheck(false);

        //  join with messages_de
        $select->joinLeft(
            array(
                'announcement_message_de' => Db_AnnouncementMessage::TABLE_NAME,
            ),
            'announcement_message_de.announcement_id = announcement.id ' .
            'AND announcement_message_de.language = "de"',
            array(
                'message_de' => 'announcement_message_de.message',
                'title_de'   => 'announcement_message_de.title',
            )
        );

        //  join with messages_en
        $select->joinLeft(
            array(
                'announcement_message_en' => Db_AnnouncementMessage::TABLE_NAME,
            ),
            'announcement_message_en.announcement_id = ' . Db_Announcement::TABLE_NAME . '.id ' .
            'AND announcement_message_en.language = "en"',
            array(
                'message_en' => 'announcement_message_en.message',
                'title_en'   => 'announcement_message_en.title',
            )
        );

        // search
        if ($search) {
            //  decode because TinyMCE editor transforms an umlaut (ä,ü,ö) into html
            $decode_search = htmlentities($search);

            $select
                ->where('announcement.name LIKE ?', '%' . $search . '%')
                ->orWhere('announcement_message_de.title LIKE ?', '%' . $search . '%')
                ->orWhere('announcement_message_en.title LIKE ?', '%' . $search . '%')
                ->orWhere('announcement_message_de.message LIKE ?', '%' . $decode_search . '%')
                ->orWhere('announcement_message_en.message LIKE ?', '%' . $decode_search . '%');
        }

        // order
        $select->order($orderBy . ' ' . $direction);

        return $select;
    }

    public function getAnnouncementById($id)
    {
        $dbAnnouncement = new Db_Announcement();
        $select         = $dbAnnouncement->select(array('announcement' => Db_Announcement::TABLE_NAME));
        $select->setIntegrityCheck(false);

        //  join with messages_de
        $select->joinLeft(
            array(
                'announcement_message_de' => Db_AnnouncementMessage::TABLE_NAME,
            ),
            'announcement_message_de.announcement_id = announcement.id ' .
            'AND announcement_message_de.language = "de"',
            array(
                'message_de' => 'announcement_message_de.message',
                'title_de'   => 'announcement_message_de.title',
            )
        );

        //  join with messages_en
        $select->joinLeft(
            array(
                'announcement_message_en' => Db_AnnouncementMessage::TABLE_NAME,
            ),
            'announcement_message_en.announcement_id = ' . Db_Announcement::TABLE_NAME . '.id ' .
            'AND announcement_message_en.language = "en"',
            array(
                'message_en' => 'announcement_message_en.message',
                'title_en'   => 'announcement_message_en.title',
            )
        );

        $select->where(Db_Announcement::TABLE_NAME . '.' . Db_Announcement::ID . '= ?', $id);
        return $this->db->fetchRow($select);
    }

    /*
     * CRUD Functions
     */
    public function insertAnnouncement($announcement)
    {
        $table = new Db_Announcement();
        return $table->insert($announcement);
    }

    public function updateAnnouncement($announcementId, $announcement)
    {
        $table = new Db_Announcement();
        $where = $table->getAdapter()->quoteInto(Db_Announcement::ID . ' = ?', $announcementId);
        $table->update($announcement, $where);
    }

    public function deleteAnnouncement($announcementId)
    {
        $table = new Db_Announcement();
        $where = $table->getAdapter()->quoteInto(Db_Announcement::ID . ' = ?', $announcementId);
        return $table->delete($where);
    }
}