<?php

/**
 * TABLE => 'TEMPLATES'
 *
 *
 *
 */
class Db_Templates extends Zend_Db_Table_Abstract
{

    protected $_name    = 'templates';
    protected $_primary = 'id';


    const TABLE_NAME = 'templates';

    // define db attributes
    const ID          = 'id';
    const NAME        = 'name';
    const DESCRIPTION = 'description';
    const NOTE        = 'note';
    const FILE        = 'file';

    const USER_ID = 'user_id';

}