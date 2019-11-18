<?php

/**
 * TABLE => 'CI_HISTORY'
 *
 *
 *
 */
class Db_History extends Zend_Db_Table_Abstract
{

    protected $_name    = 'h_history';
    protected $_primary = 'id';

    const TABLE_NAME = 'history';

    // define db attributes
    const ID        = 'id';
    const USER_ID   = 'user_id';
    const DATESTAMP = 'datestamp';
    const NOTE      = 'note';
}