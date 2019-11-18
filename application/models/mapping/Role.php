<?php

/**
 * TABLE => 'ROLE'
 *
 *
 *
 */
class Db_Role extends Zend_Db_Table_Abstract
{

    protected $_name    = 'role';
    protected $_primary = 'id';

    const TABLE_NAME = 'role';

    // define db attributes
    const ID          = 'id';
    const NAME        = 'name';
    const DESCRIPTION = 'description';
    const NOTE        = 'note';
    const IS_ACTIVE   = 'is_active';
    const USER_ID     = 'user_id';
}