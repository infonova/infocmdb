<?php

/**
 * TABLE => 'USER'
 *
 *
 *
 */
class Db_User extends Zend_Db_Table_Abstract
{

    protected $_name    = 'user';
    protected $_primary = 'id';


    // TODO: move this elsewhere or find a better way to access this property for Auth
    const TABLE_NAME = 'user';

    // define db attributes
    const ID                       = 'id';
    const USERNAME                 = 'username';
    const PASSWORD                 = 'password';
    const EMAIL                    = 'email';
    const FIRSTNAME                = 'firstname';
    const LASTNAME                 = 'lastname';
    const DESCRIPTION              = 'description';
    const NOTE                     = 'note';
    const THEME_ID                 = 'theme_id';
    const LANGUAGE                 = 'language';
    const LAYOUT                   = 'layout';
    const IS_ROOT                  = 'is_root';
    const IS_CI_DELETE_ENABLED     = 'is_ci_delete_enabled';
    const IS_RELATION_EDIT_ENABLED = 'is_relation_edit_enabled';
    const IS_LDAP_AUTH             = 'is_ldap_auth';
    const LAST_ACCESS              = 'last_access';
    const IS_ACTIVE                = 'is_active';
    const VALID_FROM               = 'valid_from';
    const USER_ID                  = 'user_id';
    const IS_TWO_FACTOR_AUTH       = 'is_two_factor_auth';
    const SECRET                   = 'secret';
    const API_SECRET               = 'api_secret';
    const PASSWORD_CHANGED         = 'password_changed';
    const PASSWORD_EXPIRE_OFF      = 'password_expire_off';
    const SETTINGS                 = 'settings';
}