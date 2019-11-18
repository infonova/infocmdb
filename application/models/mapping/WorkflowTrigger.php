<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_WorkflowTrigger extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow_trigger';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow_trigger';

    // define db attributes
    const ID               = 'id';
    const WORKFLOW_ID      = 'workflow_id';
    const MAPPING_ID       = 'mapping_id';
    const TYPE             = 'type';
    const METHOD           = 'method';
    const FILEIMPORT_REGEX = 'fileimport_regex';

    // constants for attributes
    const METHOD_KEY_CREATE        = 0;
    const METHOD_KEY_UPDATE        = 1;
    const METHOD_KEY_DELETE        = 2;
    const METHOD_KEY_BEFORE_IMPORT = 3;
    const METHOD_KEY_AFTER_IMPORT  = 4;

    const METHOD_CREATE                  = 'create';
    const METHOD_UPDATE                  = 'update';
    const METHOD_DELETE                  = 'delete';
    const METHOD_AFTER_IMPORT            = 'after';
    const METHOD_BEFORE_IMPORT           = 'before';
    const METHOD_BEFORE_AND_AFTER_IMPORT = 'before_and_after';

    const TYPE_CI             = 'ci';
    const TYPE_RELATION       = 'relation';
    const TYPE_PROJECT        = 'project';
    const TYPE_ATTRIBUTE      = 'attribute';
    const TYPE_CI_TYPE_CHANGE = 'ci_type_change';
    const TYPE_FILEIMPORT     = 'fileimport';
}