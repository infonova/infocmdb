<?php

/**
 * TABLE => 'CI'
 *
 *
 *
 */
class Db_Reporting extends Zend_Db_Table_Abstract
{

    protected $_name    = 'reporting';
    protected $_primary = 'id';

    const TABLE_NAME = 'reporting';

    // define db attributes
    const ID             = 'id';
    const NAME           = 'name';
    const DESCRIPTION    = 'description';
    const NOTE           = 'note';
    const INPUT          = 'input';
    const OUTPUT         = 'output';
    const TRANSPORT      = 'transport';
    const TRIGGER        = 'trigger';
    const STATEMENT      = 'statement';
    const SCRIPT         = 'script';
    const EXECUTION_TIME = 'execution_time';
    const IS_ACTIVE      = 'is_active';
    const MAIL_CONTENT   = 'mail_content';

    const USER_ID = 'user_id';
}