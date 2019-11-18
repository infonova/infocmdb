<?php

/**
 * TABLE => 'CI_PROJECT'
 *
 *
 *
 */
class Db_AttributeInheritance extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute_inheritance';
    protected $_primary = 'id';

    const TABLE_NAME = 'attribute_inheritance';

    // define db attributes
    const ID                  = 'id';
    const ATTRIBUTE_ID        = 'attribute_id';
    const PARENT_ATTRIBUTE_ID = 'parent_attribute_id';
    const SELECT_FIELD        = 'select_field';
    const ACTIVE              = 'active';
}