<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_WorkflowItem extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow_item';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow_item';

    // define db attributes
    const ID                     = 'id';
    const WORKFLOW_ID            = 'workflow_id';
    const WORKFLOW_CASE_ID       = 'workflow_case_id';
    const WORKFLOW_TRANSITION_ID = 'workflow_transition_id';
    const WORKFLOW_ARG_CONTEXT   = 'arg_context';

    const CONTEXT = 'context';
    const STATUS  = 'status';

    const CREATED  = 'created';
    const FINISHED = 'finished';
}