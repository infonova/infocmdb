<?php

/**
 *
 *
 *
 */
class Db_AttributeDefaultQueries extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute_default_queries';
    protected $_primary = 'id';


    const TABLE_NAME = 'attribute_default_queries';

    // define db attributes
    const ID           = 'id';
    const ATTRIBUTE_ID = 'attribute_id';
    const QUERY        = 'query';
    const LIST_QUERY   = 'list_query';

}
