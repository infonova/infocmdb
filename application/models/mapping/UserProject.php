<?php

/**
 * TABLE => 'USER_PROJECT'
 *
 *
 *
 */
class Db_UserProject extends Zend_Db_Table_Abstract
{

    protected $_name    = 'user_project';
    protected $_primary = 'id';

    const TABLE_NAME = 'user_project';

    // define db attributes
    const ID         = 'id';
    const USER_ID    = 'user_id';
    const PROJECT_ID = 'project_id';
}