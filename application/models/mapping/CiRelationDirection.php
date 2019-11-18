<?php

/**
 * TABLE => 'CiRelationDirection'
 *
 *
 *
 */
class Db_CiRelationDirection extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_relation_direction';
    protected $_primary = 'id';


    const TABLE_NAME = 'ci_relation_direction';

    // define db attributes
    const ID          = 'id';
    const NAME        = 'name';
    const DESCRIPTION = 'description';
    const NOTE        = 'note';

    const UNDIRECTED  = 'undirected';
    const AB_DIRECTED = 'ab_direction';
    const BA_DIRECTED = 'ba_direction';
    const BI_DIRECTED = 'bidirected';

    public static function getDefaultDirection()
    {
        $dao       = new Dao_CiRelation();
        $direction = $dao->getDirectionByName(self::UNDIRECTED);
        return $direction[Db_CiRelationDirection::ID];
    }

    public static function baDirected()
    {
        $dao       = new Dao_CiRelation();
        $direction = $dao->getDirectionByName(self::BA_DIRECTED);
        return $direction[Db_CiRelationDirection::ID];
    }

    public static function abDirected()
    {
        $dao       = new Dao_CiRelation();
        $direction = $dao->getDirectionByName(self::AB_DIRECTED);
        return $direction[Db_CiRelationDirection::ID];
    }
}