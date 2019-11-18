<?php

/**
 * TABLE => 'USER'
 *
 *
 *
 */
class Db_SearchSession extends Zend_Db_Table_Abstract
{

    protected $_name    = 'search_session';
    protected $_primary = 'id';


    // TODO: move this elsewhere or find a better way to access this property for Auth
    const TABLE_NAME = 'search_session';

    // define db attributes
    const ID      = 'id';
    const TIMEOUT = 'timeout';
}