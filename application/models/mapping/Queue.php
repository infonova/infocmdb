<?php

/**
 * TABLE => 'ATTRIBUTE'
 *
 *
 *
 */
class Db_Queue extends Zend_Db_Table_Abstract
{

    protected $_name    = 'queue';
    protected $_primary = 'id';

    const TABLE_NAME = 'queue';

    // define db attributes
    const ID        = 'id';
    const NAME      = 'name';
    const NOTE      = 'note';
    const IS_ACTIVE = 'is_active';
}