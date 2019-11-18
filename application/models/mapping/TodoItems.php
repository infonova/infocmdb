<?php

/**
 * TABLE => 'ATTRIBUTE'
 *
 *
 *
 */
class Db_TodoItems extends Zend_Db_Table_Abstract
{

    protected $_name    = 'todo_items';
    protected $_primary = 'id';

    const TABLE_NAME = 'todo_items';

    // define db attributes
    const ID        = 'id';
    const USER_ID   = 'user_id';
    const PRIORITY  = 'priority';
    const STATUS    = 'status';
    const CREATED   = 'created';
    const COMPLETED = 'completed';
}