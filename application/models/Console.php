<?php

class Dao_Console extends Dao_Abstract
{


    public function getCiTypeByName($name)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME)
            ->where(Db_CiType::NAME . ' =?', $name);

        return $this->db->fetchRow($select);
    }


    public function getProjectByName($name)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME)
            ->where(Db_Project::NAME . ' =?', $name);

        return $this->db->fetchRow($select);
    }


    public function createCi($ciType)
    {
        $table = new Db_Ci();

        $ci                    = array();
        $ci[Db_Ci::CI_TYPE_ID] = $ciType;

        return $table->insert($ci);
    }


    public function addCiAttribute($data)
    {
        $table = new Db_CiAttribute();
        return $table->insert($data);
    }


    public function getAttributeByName($name)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME)
            ->where(Db_Attribute::NAME . ' =?', $name);
        return $this->db->fetchRow($select);
    }
}