<?php

/**
 * TABLE => 'CI_PERMISSION'
 *
 *
 *
 */
class Db_CiPermission extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_permission';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci_permission';

    // define db attributes
    const ID      = 'id';
    const CI_ID   = 'ci_id';
    const USER_ID = 'user_id';
}