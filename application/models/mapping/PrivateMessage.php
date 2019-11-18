<?php

/**
 * TABLE => 'PRIVATE_MESSAGE'
 *
 *
 *
 */
class Db_PrivateMessage extends Zend_Db_Table_Abstract
{

    protected $_name    = 'private_message';
    protected $_primary = 'id';

    const TABLE_NAME = 'private_message';

    // define db attributes
    const ID           = 'id';
    const FROM_USER_ID = 'from_user_id';
    const TO_USER_ID   = 'to_user_id';
    const SUBJECT      = 'subject';
    const MESSAGE      = 'message';
    const SENT         = 'sent';
    const READ         = 'read';
    const IS_DELETED   = 'is_deleted';
}