<?php

/**
 * TABLE => 'AttributeGroup'
 *
 *
 *
 */
class Db_AttributeGroup extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute_group';
    protected $_primary = 'id';

    const TABLE_NAME = 'attribute_group';

    // define db attributes
    const ID                        = 'id';
    const NAME                      = 'name';
    const DESCRIPTION               = 'description';
    const PARENT_ATTRIBUTE_GROUP_ID = 'parent_attribute_group_id';
    const NOTE                      = 'note';
    const ORDER_NUMBER              = 'order_number';
    const IS_ACTIVE                 = 'is_active';
    const IS_DUPLICATE_ALLOW        = 'is_duplicate_allow';

    const USER_ID = 'user_id';
}