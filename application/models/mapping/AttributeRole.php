<?php

/**
 * TABLE => 'ATTRIBUTE_ROLE'
 *
 *
 *
 */
class Db_AttributeRole extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute_role';
    protected $_primary = 'id';

    const TABLE_NAME = 'attribute_role';

    // define db attributes
    const ID               = 'id';
    const ATTRIBUTE_ID     = 'attribute_id';
    const ROLE_ID          = 'role_id';
    const PERMISSION_READ  = 'permission_read';
    const PERMISSION_WRITE = 'permission_write';
}