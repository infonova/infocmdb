<?php

/**
 * TABLE => 'ATTRIBUTE'
 *
 *
 *
 */
class Db_Notification extends Zend_Db_Table_Abstract
{

    protected $_name    = 'notification';
    protected $_primary = 'id';

    const TABLE_NAME = 'notification';

    // define db attributes
    const ID                = 'id';
    const NOTIFICATION_ID   = 'notification_id';
    const NOTIFICATION_TYPE = 'notification_type';
    const TYPE              = 'type';
    const ADDRESS           = 'address';
    const USER_ID           = 'user_id';
}