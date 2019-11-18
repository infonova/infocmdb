<?php

/**
 * TABLE => 'CI_PROJECT'
 *
 *
 *
 */
class Db_CiProject extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_project';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci_project';

    // define db attributes
    const ID         = 'id';
    const CI_ID      = 'ci_id';
    const PROJECT_ID = 'project_id';

    const HISTORY_ID = 'history_id';
    const VALID_FROM = 'valid_from';
}