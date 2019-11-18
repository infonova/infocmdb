<?php

/**
 * TABLE => 'SEARCH_LIST_ATTRIBUTE'
 *
 *
 *
 */
class Db_SearchListAttribute extends Zend_Db_Table_Abstract
{

    protected $_name    = 'search_list_attribute';
    protected $_primary = 'id';

    const TABLE_NAME = 'search_list_attribute';

    // define db attributes
    const ID             = 'id';
    const ORDER_NUMBER   = 'order_number';
    const SEARCH_LIST_ID = 'search_list_id';
    const ATTRIBUT_ID    = 'attribute_id';
    const COLUMN_WIDTH   = 'column_width';
}