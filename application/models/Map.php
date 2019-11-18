<?php

class Dao_Map extends Dao_Abstract
{

    public function getCiByTypeList($ciTypeList)
    {
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME)
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID, array(Db_CiType::NAME))
            ->where(Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' IN (' . $ciTypeList . ')');

        return $this->db->fetchAll($select);
    }


    public function getValueForCi($ciId, $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME, array(Db_CiAttribute::VALUE_TEXT))
            ->where(Db_CiAttribute::CI_ID . ' =?', $ciId)
            ->where(Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId);

        return $this->db->fetchRow($select);
    }


    public function getCiTickets($ciId)
    {
        $select = $this->db->select()
            ->from(Db_CiTicket::TABLE_NAME)
            ->where(Db_CiTicket::CI_ID . ' =?', $ciId);

        return $this->db->fetchAll($select);
    }
}