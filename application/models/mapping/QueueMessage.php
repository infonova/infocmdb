<?php

/**
 * TABLE => 'ATTRIBUTE'
 *
 *
 *
 */
class Db_QueueMessage extends Zend_Db_Table_Abstract
{

    protected $_name    = 'queue_message';
    protected $_primary = 'id';

    const TABLE_NAME = 'queue_message';

    // define db attributes
    const ID             = 'id';
    const QUEUE_ID       = 'queue_id';
    const ARGS           = 'args';
    const EXECUTION_TIME = 'execution_time';
    const CREATION_TIME  = 'creation_time';
    const USER_ID        = 'user_id';
    const PRIORITY       = 'priority';
    const TIMEOUT        = 'timeout';
    const STATUS         = 'status';
}