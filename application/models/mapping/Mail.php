<?php

/**
 * TABLE => 'MAIL'
 *
 *
 *
 */
class Db_Mail extends Zend_Db_Table_Abstract
{

    protected $_name    = 'mail';
    protected $_primary = 'id';


    const TABLE_NAME = 'mail';

    // define db attributes
    const ID          = 'id';
    const NAME        = 'name';
    const DESCRIPTION = 'description';
    const NOTE        = 'note';
    const SUBJECT     = 'subject';
    const MIME_TYPE   = 'mime_type';
    const EDITOR      = 'editor';
    const BODY        = 'body';
    const TEMPLATE    = 'template';

    const USER_ID = 'user_id';
}