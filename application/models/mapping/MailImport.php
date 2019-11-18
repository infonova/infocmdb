<?php

/**
 * TABLE => 'ATTRIBUTE'
 *
 *
 *
 */
class Db_MailImport extends Zend_Db_Table_Abstract
{

    protected $_name    = 'import_mail';
    protected $_primary = 'id';

    const TABLE_NAME = 'import_mail';

    // define db attributes
    const ID       = 'id';
    const HOST     = 'host';
    const USER     = 'user';
    const PASSWORD = 'password';
    const SSL      = 'ssl';

    const PROTOCOL     = 'protocol';
    const PORT         = 'port';
    const INBOX_FOLDER = 'inbox_folder';
    const MOVE_FOLDER  = 'move_folder';

    const IS_EXTENDED = 'is_extended';
    const CI_TYPE_ID  = 'ci_type_id';
    const PROJECT_ID  = 'project_id';

    const CI_FIELD                = 'ci_field';
    const IS_ATTACH_BODY          = 'is_attach_body';
    const BODY_ATTRIBUTE_ID       = 'body_attribute_id';
    const ATTACHMENT_ATTRIBUTE_ID = 'attachment_attribute_id';
    const IS_CI_MAIL_ENABLED      = 'is_ci_mail_enabled';

    const NOTE           = 'note';
    const EXECUTION_TIME = 'execution_time';
    const IS_ACTIVE      = 'is_active';
    const USER_ID        = 'user_id';
    const VALID_FROM     = 'valid_from';
}