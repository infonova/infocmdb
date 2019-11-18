<?php

/**
 *
 *
 *
 */
class Db_AttributeDefaultCitype extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute_default_citype';
    protected $_primary = 'id';


    const TABLE_NAME = 'attribute_default_citype';

    // define db attributes
    const ID                     = 'id';
    const ATTRIBUTE_ID           = 'attribute_id';
    const CI_TYPE_ID             = 'ci_type_id';
    const JOIN_ATTRIBUTE_ID_FROM = 'join_attribute_id_from';
    const JOIN_ATTRIBUTE_ID_TO   = 'join_attribute_id_to';
    const JOIN_ORDER             = 'join_order';

}