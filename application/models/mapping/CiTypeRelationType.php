<?php

/**
 * TABLE => 'CI_TYPE_RELATION_TYPE'
 *
 *
 *
 */
class Db_CiTypeRelationType extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_type_relation_type';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci_type_relation_type';

    // define db attributes
    const ID                  = 'id';
    const CI_TYPE_ID          = 'ci_type_id';
    const CI_RELATION_TYPE_ID = 'ci_relation_type_id';
    const MAX_AMOUNT          = 'max_amount';
    const ORDER_NUMBER        = 'order_number';
}