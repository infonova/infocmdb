<?php

/**
 * TABLE => 'ANNOUNCEMENT MESSAGE'
 *
 *
 *
 */
class Db_AnnouncementMessage extends Zend_Db_Table_Abstract
{
    protected $_name    = 'announcement_message';
    protected $_primary = 'id';

    const TABLE_NAME = 'announcement_message';

    // define db announcements
    const ID              = 'id';
    const ANNOUNCEMENT_ID = 'announcement_id';
    const TITLE           = 'title';
    const MESSAGE         = 'message';
    const LANGUAGE        = 'language';
    const USER_ID         = 'user_id';
    const VALID_FROM      = 'valid_from';
}