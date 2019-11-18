<?php

/**
 * TABLE => 'ATTRIBUTE_ROLE'
 *
 *
 *
 */
class Db_ThemePrivilege extends Zend_Db_Table_Abstract
{

    protected $_name    = 'theme_privilege';
    protected $_primary = 'id';

    const TABLE_NAME = 'theme_privilege';

    // define db attributes
    const ID          = 'id';
    const RESOURCE_ID = 'resource_id';
    const THEME_ID    = 'theme_id';
}