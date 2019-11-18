<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_WorkflowTransition extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow_transition';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow_transition';

    // define db attributes
    const ID          = 'id';
    const WORKFLOW_ID = 'workflow_id';

    const NAME        = 'name';
    const DESCRIPTION = 'description';
    const NOTE        = 'note';

    const TRIGGER          = 'trigger';
    const WORKFLOW_TASK_ID = 'workflow_task_id';

    const ROLE_ID      = 'role_id';
    const TRIGGER_TIME = 'trigger_time';
}