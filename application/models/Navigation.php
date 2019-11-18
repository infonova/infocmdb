<?php

class Dao_Navigation extends Dao_Abstract
{


    public function getCiTypes()
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::ID, Db_CiType::NAME, Db_CiType::DESCRIPTION, Db_CiType::ORDER_NUMBER, Db_CiType::NOTE, Db_CiType::PARENT_CI_TYPE_ID))
            ->order(Db_CiType::PARENT_CI_TYPE_ID . ' ASC')
            ->order(Db_CiType::ORDER_NUMBER)
            ->order(Db_CiType::DESCRIPTION);
        return $this->db->fetchAll($select);
    }


    public function getPermittedCiTypeIds($userId, $projectId)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_Ci::TABLE_NAME, array(Db_Ci::CI_TYPE_ID))
            ->join(Db_CiProject::TABLE_NAME, Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID, array())
            ->join(Db_UserProject::TABLE_NAME, Db_UserProject::TABLE_NAME . '.' . Db_UserProject::PROJECT_ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID, array())
            ->where(Db_UserProject::TABLE_NAME . '.' . Db_UserProject::USER_ID . ' =?', $userId);

        if (!is_null($projectId) && $projectId !== 0) {
            $select->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' = ?', $projectId);
        }


        //TODO

//		$select->where(Db_Ci::CI_TYPE_ID." in (select distinct(ci_type_id) from ci_type_attribute where ci_type_attribute.attribute_id in (
//				select attribute_id from attribute_role where role_id in 
//				(select role_id from user_role where user_role.user_id = $userId) and (permission_read = '1' or permission_write = '1')
//				))");


        $ciTypes = $this->db->fetchAll($select);

        $permitted = array();
        foreach ($ciTypes as $type) {
            array_push($permitted, $type[Db_Ci::CI_TYPE_ID]);
        }

        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::ID))
            ->where(Db_CiType::QUERY . " != '' AND " . Db_CiType::QUERY . ' IS NOT NULL');

        $ciTypes = $this->db->fetchAll($select);

        foreach ($ciTypes as $type) {
            array_push($permitted, $type[Db_CiType::ID]);
        }


        return $permitted;
    }
}