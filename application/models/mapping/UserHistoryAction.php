<?php

/**
 * TABLE => 'USER_HISTORY_ACTION'
 *
 *
 *
 */
class Db_UserHistoryAction extends Zend_Db_Table_Abstract
{

    protected $_name    = 'user_history_action';
    protected $_primary = 'id';

    const TABLE_NAME = 'user_history_action';

    // define db attributes
    const ID              = 'id';
    const USER_HISTORY_ID = 'user_history_id';
    const ACTION          = 'action';
    const ACCESS          = 'access';
}