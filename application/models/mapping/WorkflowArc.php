<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_WorkflowArc extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow_arc';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow_arc';

    // define db attributes
    const ID                     = 'id';
    const WORKFLOW_ID            = 'workflow_id';
    const WORKFLOW_TRANSITION_ID = 'workflow_transition_id';
    const WORKFLOW_PLACE_ID      = 'workflow_place_id';
    const DIRECTION              = 'direction';

    const TYPE      = 'type';
    const CONDITION = 'condition';

}