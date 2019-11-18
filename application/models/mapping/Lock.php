<?php

/**
 * TABLE => 'LOCK'
 *
 *
 *
 */
class Db_Lock extends Zend_Db_Table_Abstract
{

    protected $_name    = 'lock';
    protected $_primary = 'id';

    const TABLE_NAME = 'lock';

    // define db attributes
    const ID           = 'id';
    const LOCK_TYPE    = 'lock_type';
    const RESOURCE_ID  = 'resource_id';
    const HELD_BY      = 'held_by';
    const LOCKED_SINCE = 'locked_since';
    const VALID_UNTIL  = 'valid_until';

}