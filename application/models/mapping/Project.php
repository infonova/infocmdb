<?php

/**
 * TABLE => 'PROJECT'
 *
 *
 *
 */
class Db_Project extends Zend_Db_Table_Abstract
{

    protected $_name    = 'project';
    protected $_primary = 'id';

    const TABLE_NAME = 'project';

    // define db attributes
    const ID           = 'id';
    const NAME         = 'name';
    const DESCRIPTION  = 'description';
    const NOTE         = 'note';
    const ORDER_NUMBER = 'order_number';
    const IS_ACTIVE    = 'is_active';
    const VALID_FROM   = 'valid_from';
    const USER_ID      = 'user_id';
}