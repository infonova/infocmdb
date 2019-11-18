<?php

/**
 * TABLE => 'IMPORT_FILE_HISTORY'
 *
 *
 *
 */
class Db_ImportFileHistoryDetail extends Zend_Db_Table_Abstract
{

    protected $_name    = 'import_file_history_detail';
    protected $_primary = 'id';

    const TABLE_NAME = 'import_file_history_detail';

    // define db attributes
    const ID                     = 'id';
    const IMPORT_FILE_HISTORY_ID = 'import_file_history_id';
    const LINE                   = 'line';
    const COLUMN                 = 'column';
    const MESSAGE                = 'message';
}