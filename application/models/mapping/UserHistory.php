<?php

/**
 * TABLE => 'USER_ADDRESS'
 *
 *
 *
 */
class Db_UserHistory extends Zend_Db_Table_Abstract
{

    protected $_name    = 'user_history';
    protected $_primary = 'id';

    const TABLE_NAME = 'user_history';

    // define db attributes
    const ID         = 'id';
    const USER_ID    = 'user_id';
    const ACCESS     = 'access';
    const IP_ADDRESS = 'ip_address';
}