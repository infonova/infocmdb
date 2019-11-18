<?php

/**
 * TABLE => 'USER'
 *
 *
 *
 */
class Db_ApiSession extends Zend_Db_Table_Abstract
{

    protected $_name    = 'api_session';
    protected $_primary = 'apikey';


    // TODO: move this elsewhere or find a better way to access this property for Auth
    const TABLE_NAME = 'api_session';

    // define db attributes
    const APIKEY      = 'apikey';
    const USER_ID     = 'user_id';
    const VALID_FROM  = 'valid_from';
    const VALID_TO    = 'valid_to';
    const API_VERSION = 'api_version';

}