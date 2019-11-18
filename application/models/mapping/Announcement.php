<?php

/**
 * TABLE => 'ANNOUNCEMENT'
 *
 *
 *
 */
class Db_Announcement extends Zend_Db_Table_Abstract
{
    protected $_name    = 'announcement';
    protected $_primary = 'id';

    const TABLE_NAME = 'announcement';

    // define db announcements
    const ID             = 'id';
    const NAME           = 'name';
    const TITLE_DE       = 'title_de';
    const MESSAGE_DE     = 'message_de';
    const TITLE_EN       = 'title_en';
    const MESSAGE_EN     = 'message_en';
    const SHOW_FROM_DATE = 'show_from_date';
    const SHOW_TO_DATE   = 'show_to_date';
    const TYPE           = 'type';
    const IS_ACTIVE      = 'is_active';
    const USER_ID        = 'user_id';
    const VALID_FROM     = 'valid_from';
}