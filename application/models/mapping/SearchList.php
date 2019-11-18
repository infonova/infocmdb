<?php

/**
 * TABLE => 'ROLE'
 *
 *
 *
 */
class Db_SearchList extends Zend_Db_Table_Abstract
{

    protected $_name    = 'search_list';
    protected $_primary = 'id';

    const TABLE_NAME = 'search_list';

    // define db attributes
    const ID            = 'id';
    const IS_ACTIVE     = 'is_active';
    const CI_TYPE_ID    = 'ci_type_id';
    const IS_SCROLLABLE = 'is_scrollable';
}