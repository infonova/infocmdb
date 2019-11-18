<?php

/**
 * TABLE => 'IMPORT_FILE_HISTORY'
 *
 *
 *
 */
class Db_ImportFileHistory extends Zend_Db_Table_Abstract
{

    protected $_name    = 'import_file_history';
    protected $_primary = 'id';

    const TABLE_NAME = 'import_file_history';

    // define db attributes
    const ID              = 'id';
    const USER_ID         = 'user_id';
    const FILENAME        = 'filename';
    const VALIDATION      = 'validation';
    const QUEUE           = 'queue';
    const STATUS          = 'status';
    const LINES_PROCESSED = 'lines_processed';
    const LINES_TOTAL     = 'lines_total';
    const NOTE            = 'note';
    const CREATED         = 'created';
}