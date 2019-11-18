<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_WorkflowPlace extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow_place';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow_place';

    // define db attributes
    const ID          = 'id';
    const WORKFLOW_ID = 'workflow_id';
    const TYPE        = 'type';

    const NAME        = 'name';
    const DESCRIPTION = 'description';
    const NOTE        = 'note';
}