<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_WorkflowTask extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow_task';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow_task';

    // define db attributes
    const ID   = 'id';
    const NAME = 'name';
    const NOTE = 'note';

    const SCRIPT     = 'script';
    const SCRIPTNAME = 'scriptname';
    const IS_ASYNC   = 'is_async';
}