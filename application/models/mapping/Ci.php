<?php

/**
 * TABLE => 'CI'
 *
 *
 *
 */
class Db_Ci extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci';

    // define db attributes
    const ID         = 'id';
    const CI_TYPE_ID = 'ci_type_id';
    const ICON       = 'icon';

    const HISTORY_ID = 'history_id';
    const VALID_FROM = 'valid_from';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}