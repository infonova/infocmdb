<?php

/**
 * TABLE => 'CI_RELATION_TYPE'
 *
 *
 *
 */
class Db_CiRelationType extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_relation_type';
    protected $_primary = 'id';


    const TABLE_NAME = 'ci_relation_type';

    // define db attributes
    const ID                   = 'id';
    const NAME                 = 'name';
    const DESCRIPTION          = 'description';
    const DESCRIPTION_OPTIONAL = 'description_optional';
    const NOTE                 = 'note';
    const COLOR                = 'color';
    const IS_ACTIVE            = 'is_active';
    const AGGREGATE            = 'aggregate';
    const VISUALIZE            = 'visualize';
}