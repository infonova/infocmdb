<?php

/**
 *
 *
 *
 */
class Db_AttributeDefaultQueriesParameter extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute_default_queries_parameter';
    protected $_primary = 'id';


    const TABLE_NAME = 'attribute_default_queries_parameter';

    // define db attributes
    const ID          = 'id';
    const QUERIES_ID  = 'queries_id';
    const PARAMETER   = 'parameter';
    const ORDER_NUMER = 'order_number';

}