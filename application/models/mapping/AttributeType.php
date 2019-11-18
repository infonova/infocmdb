<?php

/**
 * TABLE => 'ROLE'
 *
 *
 *
 */
class Db_AttributeType extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute_type';
    protected $_primary = 'id';

    const TABLE_NAME = 'attribute_type';

    // define db attributes
    const ID              = 'id';
    const NAME            = 'name';
    const DESCRIPTION     = 'description';
    const NOTE            = 'note';
    const IS_CUSTOMIZABLE = 'is_customizable';
    const ENABLE_REGEX    = 'enable_regex';
    const ORDER_NUMBER    = 'order_number';
    const IS_ACTIVE       = 'is_active';
}