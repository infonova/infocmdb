<?php

/**
 * TABLE => 'USER'
 *
 *
 *
 */
class Db_SearchResult extends Zend_Db_Table_Abstract
{

    protected $_name    = 'search_result';
    protected $_primary = 'id';


    // TODO: move this elsewhere or find a better way to access this property for Auth
    const TABLE_NAME = 'search_result';

    // define db attributes
    const ID        = 'id';
    const SESSION   = 'session';
    const CI_ID     = 'ci_id';
    const CITYPE_ID = 'citype_id';
}