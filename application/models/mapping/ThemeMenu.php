<?php

/**
 * TABLE => 'ThemeMenu'
 *
 *
 *
 */
class Db_ThemeMenu extends Zend_Db_Table_Abstract
{

    protected $_name    = 'theme_menu';
    protected $_primary = 'id';

    const TABLE_NAME = 'theme_menu';

    // define db attributes
    const ID       = 'id';
    const MENU_ID  = 'menue_id';
    const THEME_ID = 'theme_id';
}