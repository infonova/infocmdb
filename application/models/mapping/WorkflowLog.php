<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_WorkflowLog extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow_log';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow_log';

    // define db attributes
    const WORKFLOW_CASE_ID = 'workflow_case_id';
    const WORKFLOW_ITEM_ID = 'workflow_item_id';
    const MESSAGE          = 'message';
    const CREATED          = 'created';
}