<?php

/**
 * TABLE => 'CI_RELATION'
 *
 *
 *
 */
class Db_History_CiRelation extends Zend_Db_Table_Abstract
{

    protected $_name    = 'h_ci_relation';
    protected $_primary = 'id';


    const TABLE_NAME = 'h_ci_relation';

    // define db attributes
    const ID                  = 'id';
    const CI_ID_1             = 'ci_id_1';
    const CI_ID_2             = 'ci_id_2';
    const CI_RELATION_TYPE_ID = 'ci_relation_type_id';
    const ATTRIBUTE_ID        = 'attribute_id_1';
    const LINKED_ATTRIBUTE_ID = 'attribute_id_2';
    const DIRECTION           = 'direction';
    const WEIGHTING           = 'weighting';
    const NOTE                = 'note';
    const COLOR               = 'color';

    const VALID_FROM = 'valid_from';
    const VALID_TO   = 'valid_to';

    const HISTORY_ID        = 'history_id';
    const HISTORY_ID_DELETE = 'history_id_delete';
}