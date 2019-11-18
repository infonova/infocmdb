<?php

/**
 *
 *
 *
 */
class Db_AttributeDefaultCitypeAttributes extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute_default_citype_attributes';
    protected $_primary = 'id';


    const TABLE_NAME = 'attribute_default_citype_attributes';

    // define db attributes
    const ID                          = 'id';
    const ATTRIBUTE_DEFAULT_CITYPE_ID = 'attribute_default_citype_id';
    const ATTRIBUTE_ID                = 'attribute_id';
    const CONDITION                   = 'condition';
    const ORDER_NUMBER                = 'order_number';

}