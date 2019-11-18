<?php

/**
 * TABLE => 'ANNOUNCEMENT MESSAGE'
 *
 *
 *
 */
class Db_AnnouncementUser extends Zend_Db_Table_Abstract
{
    protected $_name    = 'announcement_user';
    protected $_primary = 'id';

    const TABLE_NAME = 'announcement_user';

    // define db announcements
    const ID              = 'id';
    const ANNOUNCEMENT_ID = 'announcement_id';
    const USER_ID         = 'user_id';
    const ACCEPT          = 'accept';
    const VALID_FROM      = 'valid_from';
}