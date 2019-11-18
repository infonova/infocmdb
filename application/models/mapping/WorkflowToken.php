<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_WorkflowToken extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow_token';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow_token';

    // define db attributes
    const ID                = 'id';
    const WORKFLOW_ID       = 'workflow_id';
    const WORKFLOW_CASE_ID  = 'workflow_case_id';
    const WORKFLOW_PLACE_ID = 'workflow_place_id';

    const CONTEXT = 'context';
    const STATUS  = 'status';

    const CREATED  = 'created';
    const FINISHED = 'finished';
}