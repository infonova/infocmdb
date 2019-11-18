<?php

/**
 * TABLE => 'CI'
 *
 *
 *
 */
class Db_ReportingHistory extends Zend_Db_Table_Abstract
{

    protected $_name    = 'reporting_history';
    protected $_primary = 'id';

    const TABLE_NAME = 'reporting_history';

    // define db attributes
    const ID           = 'id';
    const USER_ID      = 'user_id';
    const REPORTING_ID = 'reporting_id';
    const FILENAME     = 'filename';
    const NOTE         = 'note';
    const CREATED      = 'created';
}