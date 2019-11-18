<?php

/**
 * TABLE => 'WORKFLOW'
 *
 *
 *
 */
class Db_Workflow extends Zend_Db_Table_Abstract
{

    protected $_name    = 'workflow';
    protected $_primary = 'id';

    const TABLE_NAME = 'workflow';

    // define db attributes
    const ID          = 'id';
    const NAME        = 'name';
    const DESCRIPTION = 'description';
    const NOTE        = 'note';

    const EXECUTE_USER_ID        = 'execute_user_id';
    const IS_ASYNC               = 'is_async';
    const TRIGGER_CI             = 'trigger_ci';
    const TRIGGER_CI_TYPE_CHANGE = 'trigger_ci_type_change';
    const TRIGGER_ATTRIBUTE      = 'trigger_attribute';
    const TRIGGER_PROJECT        = 'trigger_project';
    const TRIGGER_RELATION       = 'trigger_relation';
    const TRIGGER_TIME           = 'trigger_time';
    const TRIGGER_FILEIMPORT     = 'trigger_fileimport';
    const EXECUTION_TIME         = 'execution_time';
    const STATUS                 = 'status';
    const STATUS_MESSAGE         = 'status_message';
    const SCRIPT_LANG            = 'script_lang';

    const USER_ID         = 'user_id';
    const IS_ACTIVE       = 'is_active';
    const VALID_FROM      = 'valid_from';
    const RESPONSE_FORMAT = 'response_format';

}