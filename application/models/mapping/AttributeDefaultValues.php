<?php

/**
 * TABLE => 'ROLE'
 *
 *
 *
 */
class Db_AttributeDefaultValues extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute_default_values';
    protected $_primary = 'id';

    const TABLE_NAME = 'attribute_default_values';

    // define db attributes
    const ID           = 'id';
    const ATTRIBUTE_ID = 'attribute_id';
    const VALUE        = 'value';
    const ORDER_NUMBER = 'order_number';
    const IS_ACTIVE    = 'is_active';
}