<?php

/**
 * TABLE => 'CI_RELATION'
 *
 *
 *
 */
class Db_CiRelation extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_relation';
    protected $_primary = 'id';


    const TABLE_NAME = 'ci_relation';

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
    const HISTORY_ID = 'history_id';


    const V_VALID_FROM          = 'valid_from';
    const V_VALID_TO            = 'valid_to';
    const V_HISTORY_ID_1        = 'ci_history_id_1';
    const V_HISTORY_ID_2        = 'ci_history_id_2';
    const V_HISTORY_ID_1_DELETE = 'ci_history_id_1_delete';
    const V_HISTORY_ID_2_DELETE = 'ci_history_id_2_delete';
}