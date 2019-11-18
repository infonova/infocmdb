<?php

/**
 * TABLE => 'USER_PROJECT'
 *
 *
 *
 */
class Db_UserRole extends Zend_Db_Table_Abstract
{

    protected $_name    = 'user_role';
    protected $_primary = 'id';

    const TABLE_NAME = 'user_role';

    // define db attributes
    const ID      = 'id';
    const USER_ID = 'user_id';
    const ROLE_ID = 'role_id';
}