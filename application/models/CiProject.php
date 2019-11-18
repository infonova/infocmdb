<?php

class Dao_CiProject extends Dao_Abstract
{


    public function insertCiProject($ciId, $projectId, $historyId = null)
    {
        $project                           = array();
        $project[Db_CiProject::CI_ID]      = $ciId;
        $project[Db_CiProject::PROJECT_ID] = $projectId;

        if ($historyId)
            $project[Db_CiProject::HISTORY_ID] = $historyId;

        $table = new Db_CiProject();
        return $table->insert($project);
    }

    public function getCiProject($ciId, $projectId)
    {
        $select = $this->db->select()
            ->from(Db_CiProject::TABLE_NAME, array(Db_CiProject::ID))
            ->where(Db_CiProject::CI_ID . ' =?', $ciId)
            ->where(Db_CiProject::PROJECT_ID . ' =?', $projectId);

        $ciProjectId = $this->db->fetchRow($select);
        $ciProjectId = $ciProjectId[Db_CiProject::ID];
        return $ciProjectId;
    }


    public function deleteCiProject($ciProjectId, $historyId = null)
    {
        $table = new Db_CiProject();
        $where = $this->db->quoteInto(Db_CiProject::ID . ' =?', $ciProjectId);
        return $table->delete($where);
    }

    public function deleteCiProjectByParameter($ciId, $projectId, $historyId = null)
    {
        $table = new Db_CiProject();
        $where = $this->db->quoteInto(Db_CiProject::CI_ID . ' = "' . $ciId . '" AND ' . Db_CiProject::PROJECT_ID . ' =?', $projectId);
        return $table->delete($where);
    }


    public function getProjectsByCiId($ciId)
    {
        $select = $this->db->select()
            ->from(Db_CiProject::TABLE_NAME)
            ->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID . ' = ?', $ciId);
        return $this->db->fetchAll($select);
    }

    public function getCountCiProjectsByProjectId($projectId)
    {
        $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_CiProject::TABLE_NAME . "
				   WHERE " . Db_CiProject::PROJECT_ID . " = '" . $projectId . "'
				   ";

        return $this->db->fetchRow($select);
    }
}