<?php

/**
 * TABLE => 'ci_favourites'
 *
 *
 *
 */
class Db_CiFavourites extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_favourites';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci_favourites';

    // define db attributes
    const ID      = 'id';
    const USER_ID = 'user_id';
    const CI_ID   = 'ci_id';
    const GROUP   = 'group';

}