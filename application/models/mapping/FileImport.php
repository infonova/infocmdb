<?php

/**
 * TABLE => 'FILE_IMPORT'
 *
 *
 *
 */
class Db_FileImport extends Zend_Db_Table_Abstract
{

    protected $_name    = 'import_file';
    protected $_primary = 'id';

    const TABLE_NAME = 'import_file';

    // define db attributes
    const ID          = 'id';
    const NAME        = 'name';
    const DESCRIPTION = 'description';
    const NOTE        = 'note';
    const ACTIVE      = 'active';

    const HOST      = 'host';
    const PORT      = 'port';
    const USERNAME  = 'username';
    const PASSWORD  = 'password';
    const SUBFOLDER = 'subfolder';

    const EXECUTION_TIME = 'execution_time';
}