<?php

/**
 * TABLE => 'THEME'
 *
 *
 *
 */
class Db_Theme extends Zend_Db_Table_Abstract
{

    protected $_name    = 'theme';
    protected $_primary = 'id';

    const TABLE_NAME = 'theme';

    // define db attributes
    const ID                  = 'id';
    const NAME                = 'name';
    const DESCRIPTION         = 'description';
    const NOTE                = 'note';
    const IS_WILDCARD_ENABLED = 'is_wildcard_enabled';
    const MENU_ID             = 'menu_id';
    const IS_ACTIVE           = 'is_active';
    const USER_ID             = 'user_id';
    //const SEARCH_LIST_ID = 'search_list_id';
}