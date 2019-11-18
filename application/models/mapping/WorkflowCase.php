<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_WorkflowCase extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow_case';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow_case';

    // define db attributes
    const ID           = 'id';
    const WORKFLOW_ID  = 'workflow_id';
    const CONTEXT      = 'context';
    const STATUS       = 'status';
    const SOLVE_STATUS = 'solve_status';

    const CREATED  = 'created';
    const FINISHED = 'finished';
    const USER_ID  = 'user_id';
}