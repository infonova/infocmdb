<?php

/**
 * TABLE => 'stored_query'
 *
 *
 *
 */
class Db_StoredQuery extends Zend_Db_Table_Abstract
{

    protected $_name    = 'stored_query';
    protected $_primary = 'id';

    const TABLE_NAME = 'stored_query';

    // define db attributes
    const ID             = 'id';
    const NAME           = 'name';
    const NOTE           = 'note';
    const QUERY          = 'query';
    const STATUS         = 'status';
    const STATUS_MESSAGE = 'status_message';
    const IS_DEFAULT     = 'is_default';
    const IS_ACTIVE      = 'is_active';

    const USER_ID    = 'user_id';
    const VALID_FROM = 'valid_from';
}