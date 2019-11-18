<?php

/**
 * TABLE => 'PASSWORD_RESET'
 *
 *
 *
 */
class Db_PasswordReset extends Zend_Db_Table_Abstract
{

    protected $_name    = 'password_reset';
    protected $_primary = 'id';


    const TABLE_NAME = 'password_reset';

    // define db attributes
    const ID         = 'id';
    const USER_ID    = 'user_id';
    const HASH       = 'hash';                // token generated and neccessary for user to reset password
    const VALID_TO   = 'valid_to';        // datetime until the token is valid
    const IS_VALID   = 'is_valid';        // if user uses token to reset password -> set is_valid to false; token is kept in DB for checking restrictions set in login.ini 
    const CLIENT_KEY = 'client_key';    // hash of client ip params, used to identify client
    const CREATED    = 'created';          // datetime of entry inserted in db

}