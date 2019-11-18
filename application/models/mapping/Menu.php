<?php

/**
 * TABLE => 'MENU'
 *
 *
 *
 */
class Db_Menu extends Zend_Db_Table_Abstract
{

    protected $_name    = 'menu';
    protected $_primary = 'id';

    const TABLE_NAME = 'menu';

    // define db attributes
    const ID           = 'id';
    const NAME         = 'name';
    const DESCRIPTION  = 'description';
    const NOTE         = 'note';
    const FUNCTION_    = 'function';
    const ORDER_NUMBER = 'order_number';
    const IS_ACTIVE    = 'is_active';
}