<?php

/**
 * TABLE => 'ATTRIBUTE'
 *
 *
 *
 */
class Db_Cron extends Zend_Db_Table_Abstract
{

    protected $_name    = 'cron';
    protected $_primary = 'id';

    const TABLE_NAME = 'cron';

    // define db attributes
    const ID             = 'id';
    const TYPE           = 'type';
    const MAPPING_ID     = 'mapping_id';
    const LAST_EXECUTION = 'last_execution';
    const VAR_DUMP       = 'var_dump';
}