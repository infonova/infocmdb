<?php

class Dao_Project extends Dao_Abstract
{

    public function getProjects($orderBy = null, $direction = null)
    {

        $table  = new Db_Project();
        $select = $table->select();

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;
            $select->order($orderBy);
        } else $select->order(Db_Project::NAME, ' ASC');

        return $table->fetchAll($select);
    }

    public function getProjectsForPagination($orderBy = null, $direction = null, $filter = null)
    {

        $table  = new Db_Project();
        $select = $table->select();

        if ($filter) {
            $select = $select
                ->where(Db_Project::TABLE_NAME . '.' . Db_Project::NAME . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Project::TABLE_NAME . '.' . Db_Project::DESCRIPTION . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else $select->order(Db_Project::NAME . ' ASC');

        return $select;
    }

    public function getProjectRowset($checkValid = false, $projectId = null, $orderBy = null, $direction = null)
    {
        $table  = new Db_Project();
        $select = $table->select();

        if ($checkValid) {
            $select->where(Db_Project::IS_ACTIVE . ' =?', '1');

            if ($projectId) {
                $select->orWhere(Db_Project::ID . ' =?', $projectId);
            }
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;
            $select->order($orderBy);
        } else $select->order(Db_Project::NAME, ' ASC');


        $rowset = $table->fetchAll($select);

        return $rowset;
    }

    public function getProjectRowsetVirtualized($userId, $checkValid = false, $orderBy = null, $direction = null)
    {
        $table  = new Db_Project();
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME)
            ->join(Db_UserProject::TABLE_NAME, Db_UserProject::TABLE_NAME . '.' . Db_UserProject::PROJECT_ID . ' = ' . Db_Project::TABLE_NAME . '.' . Db_Project::ID, array())
            ->where(Db_UserProject::TABLE_NAME . '.' . Db_UserProject::USER_ID . ' = ?', $userId);

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;
            $select->order($orderBy);
        } else $select->order(Db_Project::NAME, ' ASC');

        if ($checkValid) {
            $select->where(Db_Project::IS_ACTIVE . ' =?', '1');
        }

        return $this->db->fetchAll($select);;
    }

    public function insertProject($project)
    {
        $table = new Db_Project();
        return $table->insert($project);
    }

    public function updateProject($project, $projectId)
    {
        $table = new Db_Project();
        $where = $this->db->quoteInto(Db_Project::ID . ' =?', $projectId);
        return $table->update($project, $where);
    }


    public function deleteProject($projectId)
    {
        $table = new Db_Project();
        $where = $this->db->quoteInto(Db_Project::ID . ' =?', $projectId);
        return $table->delete($where);
    }

    public function deactivateProject($projectId)
    {
        $select = "UPDATE " . Db_Project::TABLE_NAME . " SET " . Db_Project::IS_ACTIVE . " = '0' 
		WHERE " . Db_Project::ID . " = '" . $projectId . "'";
        return $this->db->query($select);
    }

    public function activateProject($projectId)
    {
        $select = "UPDATE " . Db_Project::TABLE_NAME . " SET " . Db_Project::IS_ACTIVE . " = '1' 
		WHERE " . Db_Project::ID . " = '" . $projectId . "'";
        return $this->db->query($select);
    }

    public function getProject($pId)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME)
            ->where(Db_Project::ID . ' =?', $pId);

        return $this->db->fetchRow($select);
    }

    public function getUserMappingByProjectId($projectId)
    {
        $select = $this->db->select()
            ->from(Db_UserProject::TABLE_NAME)
            ->where(Db_UserProject::PROJECT_ID . ' =?', $projectId);

        return $this->db->fetchAll($select);
    }

    public function getProjectsByCiId($ciId)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME)
            ->join(Db_CiProject::TABLE_NAME, Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' = ' . Db_Project::TABLE_NAME . '.' . Db_Project::ID, array('ci_project_valid_from' => Db_CiProject::VALID_FROM, 'ci_project_history_id' => Db_CiProject::HISTORY_ID))
            ->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID . ' = ?', $ciId);

        return $this->db->fetchAll($select);
    }

    public function getProjectsByUserId($userId, $onlyActiveProjects = false)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME, array(Db_Project::ID, Db_Project::NAME, Db_Project::DESCRIPTION, Db_Project::NOTE, Db_Project::IS_ACTIVE))
            ->join(Db_UserProject::TABLE_NAME, Db_UserProject::TABLE_NAME . '.' . Db_UserProject::PROJECT_ID . ' = ' . Db_Project::TABLE_NAME . '.' . Db_Project::ID, array())
            ->where(Db_UserProject::TABLE_NAME . '.' . Db_UserProject::USER_ID . ' = ?', $userId);

        if ($onlyActiveProjects === true) {
            $select->where(Db_Project::TABLE_NAME . '.' . Db_Project::IS_ACTIVE . ' = ?', '1');
        }
        $select->order(Db_Project::TABLE_NAME . '.' . Db_Project::ORDER_NUMBER . ' ASC');
        $select->order(Db_Project::TABLE_NAME . '.' . Db_Project::DESCRIPTION . ' ASC');
        return $this->db->fetchAll($select);
    }

    public function getProjectMappingByUserId($userId)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME, array(Db_Project::ID, Db_Project::NAME, Db_Project::DESCRIPTION))
            ->joinLeft(Db_UserProject::TABLE_NAME, Db_UserProject::TABLE_NAME . '.' . Db_UserProject::PROJECT_ID . ' = ' . Db_Project::TABLE_NAME . '.' . Db_Project::ID . ' AND ' . Db_UserProject::TABLE_NAME . '.' . Db_UserProject::USER_ID . ' = ' . $userId, array(Db_UserProject::USER_ID))
            ->order(Db_Project::TABLE_NAME . '.' . Db_Project::NAME);
        return $this->db->fetchAll($select);
    }


    public function deleteProjectMappingByUserId($userId)
    {
        $table = new Db_UserProject();

        $where = $this->db->quoteInto(Db_UserProject::USER_ID . ' =?', $userId);
        $table->delete($where);
    }

    public function deleteProjectMapping($userId, $projectId)
    {
        $table = new Db_UserProject();

        $where = array(
            $this->db->quoteInto(Db_UserProject::USER_ID . ' = ?', $userId),
            $this->db->quoteInto(Db_UserProject::PROJECT_ID . ' =?', $projectId),
        );
        $table->delete($where);
    }

    public function addProjectMapping($userId, $projectId)
    {
        $table = new Db_UserProject();

        $data                             = array();
        $data[Db_UserProject::USER_ID]    = $userId;
        $data[Db_UserProject::PROJECT_ID] = $projectId;

        $table->insert($data);
    }

    public function getCiProject($ciId, $projectId)
    {
        $select = $this->db->select()
            ->from(Db_CiProject::TABLE_NAME)
            ->where(Db_CiProject::CI_ID . ' =?', $ciId)
            ->where(Db_CiProject::PROJECT_ID . ' =?', $projectId);

        return $this->db->fetchRow($select);
    }

    public function getCiProjectById($ciprojectId)
    {
        $select = $this->db->select()
            ->from(Db_CiProject::TABLE_NAME)
            ->where(Db_CiProject::ID . ' =?', $ciprojectId);

        return $this->db->fetchRow($select);
    }

    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_Project::NAME . ' LIKE ?', $value);

        if($id > 0) {
            $select->where(Db_Project::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }

    /**
     * @param $ciId       int
     * @param $history_id id of history row
     *
     * @return mixed
     */
    public function getProjectDataForPointInTime($ciId, $history_id)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME)
            ->joinLeft(Db_History_CiProject::TABLE_NAME, Db_History_CiProject::TABLE_NAME . '.' . Db_History_CiProject::PROJECT_ID . ' = ' . Db_Project::TABLE_NAME . '.' . Db_Project::ID, array('ci_project_valid_from' => Db_History_CiProject::VALID_FROM, 'ci_project_valid_to' => Db_History_CiProject::VALID_TO))
            ->where(Db_History_CiProject::TABLE_NAME . '.' . Db_History_CiProject::CI_ID . ' = ?', $ciId)
            ->where(Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::HISTORY_ID . "<= ?", $history_id)
            ->where(Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::HISTORY_ID_DELETE . "> ?", $history_id)
            ->group(Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::ID);

        return $this->db->fetchAll($select);
    }
}