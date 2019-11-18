<?php

/**
 * TABLE => 'CI_PROJECT'
 *
 */
class Db_History_CiProject extends Zend_Db_Table_Abstract
{

    protected $_name    = 'h_ci_project';
    protected $_primary = 'id';

    const TABLE_NAME = 'h_ci_project';

    // define db attributes
    const ID         = 'id';
    const CI_ID      = 'ci_id';
    const PROJECT_ID = 'project_id';

    const HISTORY_ID        = 'history_id';
    const HISTORY_ID_DELETE = 'history_id_delete';

    const VALID_FROM = 'valid_from';
    const VALID_TO   = 'valid_to';
}