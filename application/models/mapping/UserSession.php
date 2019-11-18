<?php

/**
 * TABLE => 'USER_ADDRESS'
 *
 *
 *
 */
class Db_UserSession extends Zend_Db_Table_Abstract
{

    protected $_name    = 'user_session';
    protected $_primary = 'id';

    const TABLE_NAME = 'user_session';

    // define db attributes
    const ID         = 'id';
    const MODIFIED   = 'modified';
    const LIFETIME   = 'lifetime';
    const USER_ID    = 'user_id';
    const IP_ADDRESS = 'ip_address';
    const DATA       = 'data';
}