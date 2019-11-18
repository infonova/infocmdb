<?php

/**
 * TABLE => 'CI_TYPE_ATTRIBUTE'
 *
 *
 *
 */
class Db_CiTypeAttribute extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_type_attribute';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci_type_attribute';

    // define db attributes
    const ID           = 'id';
    const CI_TYPE_ID   = 'ci_type_id';
    const ATTRIBUTE_ID = 'attribute_id';
    const IS_MANDATORY = 'is_mandatory';
}