<?php

class Dao_History extends Dao_Abstract
{

    private $_shema;

    public function __construct()
    {
        $config       = new Zend_Config_Ini(APPLICATION_PATH . '/configs/database.ini', APPLICATION_ENV);
        $this->_shema = $config->database->params->dbname;
        parent::__construct();
    }

    /**
     * returns a list of all history entries
     *
     * for Pagination objects only!
     */
    public function getCiHistoryForPagination($page = null, $orderBy = null, $direction = null, $filter = null, $limit = null, $limit_from = null)
    {
        $select = $this->db->select()
            ->from(Db_History::TABLE_NAME)
            ->joinLeft(Db_User::TABLE_NAME, Db_History::TABLE_NAME . '.' . Db_History::USER_ID . ' = ' . Db_User::TABLE_NAME . '.' . Db_User::ID, array(Db_User::USERNAME));

        if ($filter) {

            if ($filter == 'system') {
                $select->where(Db_History::TABLE_NAME . '.' . Db_History::USER_ID . ' =?', '0');
            } else {
                $select->where(Db_User::TABLE_NAME . '.' . Db_User::USERNAME . ' LIKE ?', '%' . $filter . '%')
                    ->orWhere(Db_History::TABLE_NAME . '.' . Db_History::NOTE . ' LIKE ?', '%' . $filter . '%');
            }

        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_History::DATESTAMP . ' DESC');
        }

        if (isset($limit) && isset($limit_from))
            $select->limit($limit, $limit_from);


        return $this->db->fetchAll($select);
    }


    public function getHistoryCount()
    {

        $select = 'SELECT count(*) c from ' . Db_History::TABLE_NAME . ';';

        $count = $this->db->fetchRow($select);
        return $count['c'];


    }


    public function getHistoryById($id)
    {
        $select = $this->db->select()
            ->from(Db_History::TABLE_NAME)
            ->where(Db_History::ID . ' = ?', $id);
        return $this->db->fetchRow($select);
    }


    /**
     * updates the history_id of all active ci_attributes of the given ciId
     *
     * USE THIS ONLY FOR NEW CI'S. Result can differ when using it on existing ci's
     *
     * @param unknown_type $ciId
     * @param unknown_type $historyId
     */
    public function updateCiAttributeHistoryId($ciId, $historyId)
    {
        $sql = "
		UPDATE " . Db_CiAttribute::TABLE_NAME . " SET " . Db_CiAttribute::V_HISTORY_ID . " = '" . $historyId . "'
		WHERE " . Db_CiAttribute::V_HISTORY_ID . " IS NULL
		AND " . Db_CiAttribute::V_VALID_TO . " IS NULL
		AND " . Db_CiAttribute::CI_ID . " = '" . $ciId . "'
		";

        $this->db->query($sql);
    }


    public function updateCiAttributeHistoryIdDelete($ciId, $historyId)
    {
        $sql = "
		UPDATE " . Db_CiAttribute::TABLE_NAME . " SET " . Db_CiAttribute::V_HISTORY_ID_DELETE . " = '" . $historyId . "'
		WHERE " . Db_CiAttribute::V_HISTORY_ID_DELETE . " IS NULL
		AND " . Db_CiAttribute::V_VALID_TO . " IS NULL
		AND " . Db_CiAttribute::CI_ID . " = '" . $ciId . "'
		";

        $this->db->query($sql);
    }


    /**
     * updates a single entry
     */
    public function updateSingleCiAttributeHistoryId($ciId, $ciAttributeId, $historyId)
    {
        $sql = "
		UPDATE " . Db_CiAttribute::TABLE_NAME . " SET " . Db_CiAttribute::V_HISTORY_ID . " = '" . $historyId . "'
		WHERE " . Db_CiAttribute::V_HISTORY_ID . " IS NULL
		AND " . Db_CiAttribute::V_VALID_TO . " IS NULL
		AND " . Db_CiAttribute::CI_ID . " = '" . $ciId . "'
		AND " . Db_CiAttribute::ID . " = '" . $ciAttributeId . "'
		";

        $this->db->query($sql);
    }

    /**
     * updates a single entry
     */
    public function updateSingleCiAttributeHistoryIdDelete($ciAttributeId, $historyId)
    {
        $sql = "
		UPDATE " . Db_CiAttribute::TABLE_NAME . " SET " . Db_CiAttribute::V_HISTORY_ID_DELETE . " = '" . $historyId . "'
		WHERE " . Db_CiAttribute::V_HISTORY_ID_DELETE . " IS NULL
		AND " . Db_CiAttribute::V_VALID_TO . " IS NULL
		AND " . Db_CiAttribute::ID . " = '" . $ciAttributeId . "'
		";

        $this->db->query($sql);
    }


    /**
     * updates a ci -> set history id
     *
     * @param $ciId
     */
    public function updateCiHistoryId($ciId, $historyId)
    {
        $sql = "
		UPDATE " . Db_Ci::TABLE_NAME . " SET " . Db_Ci::V_HISTORY_ID . " = '" . $historyId . "'
		WHERE " . Db_Ci::V_HISTORY_ID . " = '0'
		AND " . Db_Ci::V_VALID_TO . " IS NULL
		AND " . Db_Ci::ID . " = '" . $ciId . "'
		";

        $this->db->query($sql);
    }

    /**
     * updates a ci -> set history id for delete id
     *
     * @param $ciId
     */
    public function updateCiHistoryIdDelete($ciId, $historyId)
    {
        $sql = "
		UPDATE " . Db_Ci::TABLE_NAME . " SET " . Db_Ci::V_HISTORY_ID_DELETE . " = '" . $historyId . "'
		WHERE " . Db_Ci::V_HISTORY_ID_DELETE . " IS NULL
		AND " . Db_Ci::V_VALID_TO . " IS NULL
		AND " . Db_Ci::ID . " = '" . $ciId . "'
		";

        $this->db->query($sql);
    }

    /**
     * updates the history_id of all active ci_projects of the given ciId
     *
     * USE THIS ONLY FOR NEW CI'S. Result can differ when using it on existing ci's
     *
     * @param unknown_type $ciId
     * @param unknown_type $historyId
     */
    public function updateCiProjectHistoryId($ciId, $historyId)
    {
        $sql = "
		UPDATE " . Db_CiProject::TABLE_NAME . " SET " . Db_CiProject::V_HISTORY_ID . " = '" . $historyId . "'
		WHERE " . Db_CiProject::V_HISTORY_ID . " IS NULL
		AND " . Db_CiProject::V_VALID_TO . " IS NULL
		AND " . Db_CiProject::CI_ID . " = '" . $ciId . "'
		";

        $this->db->query($sql);
    }

    public function updateCiProjectHistoryIdDelete($ciId, $historyId)
    {
        $sql = "
		UPDATE " . Db_CiProject::TABLE_NAME . " SET " . Db_CiProject::V_HISTORY_ID_DELETE . " = '" . $historyId . "'
		WHERE " . Db_CiProject::V_HISTORY_ID_DELETE . " IS NULL
		AND " . Db_CiProject::V_VALID_TO . " IS NULL
		AND " . Db_CiProject::CI_ID . " = '" . $ciId . "'
		";

        $this->db->query($sql);
    }

    /**
     * updates a single entry
     */
    public function updateSingleCiProjectHistoryId($ciId, $projectId, $historyId)
    {
        $sql = "
		UPDATE " . Db_CiProject::TABLE_NAME . " SET " . Db_CiProject::V_HISTORY_ID . " = '" . $historyId . "'
		WHERE " . Db_CiProject::V_HISTORY_ID . " IS NULL
		AND " . Db_CiProject::V_VALID_TO . " IS NULL
		AND " . Db_CiProject::CI_ID . " = '" . $ciId . "'
		AND " . Db_CiProject::PROJECT_ID . " = '" . $projectId . "'
		";
        $this->db->query($sql);
    }

    public function updateSingleCiProjectHistoryIdDelete($ciId, $projectId, $historyId)
    {
        $sql = "
		UPDATE " . Db_CiProject::TABLE_NAME . " SET " . Db_CiProject::V_HISTORY_ID_DELETE . " = '" . $historyId . "'
		WHERE " . Db_CiProject::V_HISTORY_ID_DELETE . " IS NULL
		AND " . Db_CiProject::V_VALID_TO . " IS NULL
		AND " . Db_CiProject::CI_ID . " = '" . $ciId . "'
		AND " . Db_CiProject::PROJECT_ID . " = '" . $projectId . "'
		";
        $this->db->query($sql);
    }


    /**
     * updates the history_id of all active ci_relations of the given ciId
     *
     * USE THIS ONLY FOR NEW CI'S. Result can differ when using it on existing ci's
     *
     * @param unknown_type $ciId
     * @param unknown_type $historyId_1
     * @param unknown_type $historyId_2
     */
    public function updateCiRelationHistoryId($ciId, $ci_id2, $historyId_1, $historyId_2)
    {
        $sql = "
		UPDATE " . Db_CiRelation::TABLE_NAME . " 
		SET " . Db_CiRelation::V_HISTORY_ID_1 . " = '" . $historyId_1 . "', 
		" . Db_CiRelation::V_HISTORY_ID_2 . " = '" . $historyId_2 . "'  
		WHERE " . Db_CiRelation::V_HISTORY_ID_1 . " IS NULL 
		AND " . Db_CiRelation::V_HISTORY_ID_2 . " IS NULL 
		AND " . Db_CiRelation::V_VALID_TO . " IS NULL 
		AND " . Db_CiRelation::CI_ID_1 . " = '" . $ciId . "' 
		AND " . Db_CiRelation::CI_ID_2 . " = '" . $ci_id2 . "'
		";

        $this->db->query($sql);
    }


    public function updateCiRelationHistoryIdDelete($ciRelationId, $historyId_1, $historyId_2)
    {
        $sql = "
		UPDATE " . Db_CiRelation::TABLE_NAME . " 
		SET " . Db_CiRelation::V_HISTORY_ID_1_DELETE . " = '" . $historyId_1 . "', 
		" . Db_CiRelation::V_HISTORY_ID_2_DELETE . " = '" . $historyId_2 . "'  
		WHERE " . Db_CiRelation::V_HISTORY_ID_1_DELETE . " IS NULL
		AND " . Db_CiRelation::V_HISTORY_ID_2_DELETE . " IS NULL
		AND " . Db_CiRelation::V_VALID_TO . " IS NULL
		AND " . Db_CiRelation::ID . " = '" . $ciRelationId . "'
		";

        $this->db->query($sql);
    }

    /**
     * returns the created_at and updated_at-columns of a CI
     *
     * @param integer $ciId
     */
    public function getModificationDatesForCi($ciId)
    {

        $query = "SELECT created_at, updated_at FROM ci WHERE id = " . $ciId;

        return $this->db->fetchRow($query);
    }


    public function getFirstHistoryEntryByCiId($ciId)
    {

        $query = "select * from (
				select valid_from from h_ci where id = $ciId
				union
				select valid_from from ci where id = $ciId
				) a order by valid_from asc limit 1";

        return $this->db->fetchRow($query);
    }


    /**
     *    Gets the history of a single ci with the following params:
     *
     * @param        string  $ciId
     * @param        string  $page
     * @param        string  $rowcount
     * @param        string  $fromDate
     * @param        string  $toDate
     * @param        null    $filteredHistoryIds if filterSet
     * @param        boolean $filterSet          any filter option set
     * @param        boolean $onlyDateFilter     only date filter selected
     *
     * @return    array
     *
     * @author        Martina Reiter
     * @since         August 2016
     */
    public function getCiHistoryForCi($ciId, $page, $rowcount, $fromDate, $toDate, $filteredHistoryIds = null, $filterSet, $onlyDateFilter)
    {

        $endDate        = (is_null($toDate)) ? date('Y-m-d') : $toDate;
        $historyList    = $this->getHistoryItems($ciId);
        $historyCounter = $this->db->fetchAll($historyList);
        $resCount       = count($historyCounter);

        $select = $this->db->select()
            ->from(array('history_ids' => $historyList))
            ->joinLeft(Db_History::TABLE_NAME, 'history_ids.history = ' . Db_History::TABLE_NAME . '.' . Db_History::ID)
            ->joinLeft(Db_User::TABLE_NAME, Db_User::TABLE_NAME . '.' . Db_User::ID . ' = ' . Db_History::TABLE_NAME . '.' . Db_History::USER_ID, array(Db_User::USERNAME))
            ->where('date(' . Db_History::TABLE_NAME . '.' . Db_History::DATESTAMP . ") >= ?", date('Y-m-d', strtotime($fromDate)) . ' 00:00:00')
            ->where('date(' . Db_History::TABLE_NAME . '.' . Db_History::DATESTAMP . ") <= ?", date('Y-m-d', strtotime($endDate)) . ' 00:00:00');

        if ($filteredHistoryIds) {
            $select->where(Db_History::TABLE_NAME . '.' . Db_History::ID . ' IN ' . '(' . $filteredHistoryIds . ')');
        }
        if (is_null($filterSet) || $onlyDateFilter) {
            $select->limitPage($page, $rowcount);
        }

        $select->order(Db_History::TABLE_NAME . '.' . Db_History::ID . ' DESC');

        $all = $this->db->fetchAll($select);

        if ($filteredHistoryIds) {
            $select->limitPage($page, $rowcount);
        }
        $res = $this->db->fetchAll($select);

        return array('cnt' => $resCount, 'res' => $res, 'all' => $all);
    }


    private function getHistoryItems($ciId)
    {
        $ciId = $this->db->quote($ciId);


        $selects = array(
            "select history_id 			as 'history' from ci_attribute 		where ci_id = $ciId",
            "select history_id_delete 	as 'history' from h_ci_attribute 	where ci_id = $ciId",
            "select history_id 			as 'history' from h_ci_attribute 	where ci_id = $ciId",
            "select history_id 			as 'history' from ci_project 		where ci_id = $ciId",
            "select history_id_delete 	as 'history' from h_ci_project 		where ci_id = $ciId",
            "select history_id 			as 'history' from h_ci_project 		where ci_id = $ciId",
            "select history_id 			as 'history' from ci 				where id = $ciId",
            "select history_id_delete 	as 'history' from h_ci 				where id = $ciId",
            "select history_id 			as 'history' from h_ci 				where id = $ciId",
            "select history_id 			as 'history' from ci_relation 		where ci_id_1 = $ciId",
            "select history_id_delete 	as 'history' from h_ci_relation 	where ci_id_1 = $ciId",
            "select history_id 			as 'history' from h_ci_relation 	where ci_id_1 = $ciId",
            "select history_id 			as 'history' from ci_relation 		where ci_id_2 = $ciId",
            "select history_id_delete 	as 'history' from h_ci_relation 	where ci_id_2 = $ciId",
            "select history_id 			as 'history' from h_ci_relation 	where ci_id_2 = $ciId",
        );

        return
            $this->db->select()
                ->union($selects)
                ->group('history');
    }


    public function getCiProjectByHistoryList($historyId, $ciId)
    {
        $ciId = $this->db->quote($ciId);

        $select = "SELECT " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::ID . ", " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::CI_ID . ", " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::PROJECT_ID . ", " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::HISTORY_ID . ", " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::VALID_FROM . ", NULL as " . Db_History_CiProject::HISTORY_ID_DELETE . ", NULL as " . Db_History_CiProject::VALID_TO . ", " . Db_Project::TABLE_NAME . "." . Db_Project::DESCRIPTION . " 
		FROM " . Db_CiProject::TABLE_NAME . " 
		INNER JOIN " . Db_Project::TABLE_NAME . " ON " . Db_Project::TABLE_NAME . "." . Db_Project::ID . " = " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::PROJECT_ID;
        $select .= " WHERE " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::HISTORY_ID . "  IN(" . $historyId . ")";
        $select .= " AND " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::CI_ID . " = $ciId";

        $select .= " UNION ";

        $select .= "SELECT " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::ID . ", " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::CI_ID . ", " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::PROJECT_ID . ", " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::HISTORY_ID . ", " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::VALID_FROM . ", " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::HISTORY_ID_DELETE . ", " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::VALID_TO . ", " . Db_Project::TABLE_NAME . "." . Db_Project::DESCRIPTION . " 
		FROM " . Db_History_CiProject::TABLE_NAME . " 
		INNER JOIN " . Db_Project::TABLE_NAME . " ON " . Db_Project::TABLE_NAME . "." . Db_Project::ID . " = " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::PROJECT_ID;
        $select .= " WHERE " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::HISTORY_ID_DELETE . "  IN(" . $historyId . ")";
        $select .= " AND " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::CI_ID . " = $ciId";

        return $this->db->fetchAll($select);
    }


    public function getCiRelationByHistoryList($historyId, $deleted = false)
    {
        if (!$deleted) {
            $select1 = $this->db->select()
                ->from(Db_CiRelation::TABLE_NAME, array(Db_CiRelation::ID, Db_CiRelation::CI_RELATION_TYPE_ID, Db_CiRelation::CI_ID_1, Db_CiRelation::CI_ID_2, Db_CiRelation::ATTRIBUTE_ID, Db_CiRelation::LINKED_ATTRIBUTE_ID, Db_CiRelation::DIRECTION, Db_CiRelation::WEIGHTING, Db_CiRelation::COLOR, Db_CiRelation::NOTE, Db_CiRelation::HISTORY_ID, Db_CiRelation::VALID_FROM))
                ->join(Db_CiRelationType::TABLE_NAME, Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID, array(Db_CiRelationType::DESCRIPTION, Db_CiRelationType::DESCRIPTION_OPTIONAL, Db_CiRelationType::NAME))
                ->where(Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::HISTORY_ID . ' IN(' . $historyId . ')');

            $select2 = $this->db->select()
                ->from(Db_History_CiRelation::TABLE_NAME, array(Db_History_CiRelation::ID, Db_History_CiRelation::CI_RELATION_TYPE_ID, Db_History_CiRelation::CI_ID_1, Db_History_CiRelation::CI_ID_2, Db_History_CiRelation::ATTRIBUTE_ID, Db_History_CiRelation::LINKED_ATTRIBUTE_ID, Db_History_CiRelation::DIRECTION, Db_History_CiRelation::WEIGHTING, Db_History_CiRelation::COLOR, Db_History_CiRelation::NOTE, Db_History_CiRelation::HISTORY_ID, Db_History_CiRelation::VALID_FROM))
                ->join(Db_CiRelationType::TABLE_NAME, Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ' . Db_History_CiRelation::TABLE_NAME . '.' . Db_History_CiRelation::CI_RELATION_TYPE_ID, array(Db_CiRelationType::DESCRIPTION, Db_CiRelationType::DESCRIPTION_OPTIONAL, Db_CiRelationType::NAME))
                ->where(Db_History_CiRelation::TABLE_NAME . '.' . Db_History_CiRelation::HISTORY_ID . ' IN(' . $historyId . ')');

            $select = $this->db->select()->union(array($select1, $select2));

        } else {
            $select = $this->db->select()
                ->from(Db_History_CiRelation::TABLE_NAME)
                ->join(Db_CiRelationType::TABLE_NAME, Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ' . Db_History_CiRelation::TABLE_NAME . '.' . Db_History_CiRelation::CI_RELATION_TYPE_ID, array(Db_CiRelationType::DESCRIPTION, Db_CiRelationType::DESCRIPTION_OPTIONAL, Db_CiRelationType::NAME))
                ->where(Db_History_CiRelation::TABLE_NAME . '.' . Db_History_CiRelation::HISTORY_ID_DELETE . ' IN(' . $historyId . ')');

        }


        return $this->db->fetchAll($select);
    }

    /**
     *    Gets all Attributes From History Ci Attribute and Ci Attribute:
     *
     * @param        string $ciId
     * @param        string $userId
     *
     * @return    array
     *
     * @author        Martina Reiter
     * @since         August 2016
     */
    public function getAttributesFromHistoryCiAttributeAndCiAttribute($ciId, $userId)
    {

        $subSelect1 = "
			SELECT  distinct "
            . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::ATTRIBUTE_ID . ", "
            . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . ", "
            . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_ACTIVE . "		
								
			FROM " . Db_History_CiAttribute::TABLE_NAME . "
			
			INNER JOIN " . Db_Attribute::TABLE_NAME . " 			ON " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "								= " . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::ATTRIBUTE_ID . "	
			INNER JOIN " . Db_AttributeRole::TABLE_NAME . " 		ON " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ATTRIBUTE_ID . "				= " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "	
			INNER JOIN " . Db_Role::TABLE_NAME . " 					ON " . Db_Role::TABLE_NAME . "." . Db_Role::ID . "											= " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ROLE_ID . "	
			INNER JOIN " . Db_UserRole::TABLE_NAME . " 				ON " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::ROLE_ID . "								= " . Db_Role::TABLE_NAME . "." . Db_Role::ID . "	
			
			Where " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::USER_ID . " 						= " . $userId . "
	        AND " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::PERMISSION_READ . " 		= '1' 
	        AND " . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::CI_ID . "		= " . $ciId;

        $subSelect2 = "
				SELECT  distinct "
            . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . ", "
            . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . ", "
            . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_ACTIVE . "	
										
				FROM " . Db_CiAttribute::TABLE_NAME . "
				
				INNER JOIN " . Db_Attribute::TABLE_NAME . " 			ON " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "								= " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . "	
				INNER JOIN " . Db_AttributeRole::TABLE_NAME . " 		ON " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ATTRIBUTE_ID . "				= " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "	
				INNER JOIN " . Db_Role::TABLE_NAME . " 					ON " . Db_Role::TABLE_NAME . "." . Db_Role::ID . "											= " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ROLE_ID . "	
				INNER JOIN " . Db_UserRole::TABLE_NAME . " 				ON " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::ROLE_ID . "								= " . Db_Role::TABLE_NAME . "." . Db_Role::ID . "	
				
				Where " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::USER_ID . " 							= " . $userId . "
				AND " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::PERMISSION_READ . " 		= '1'
				AND " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . "						= " . $ciId;

        $select = $this->db->select()->union(array('(' . $subSelect1 . ')', '(' . $subSelect2 . ')'));

        $select->order(Db_Attribute::DESCRIPTION . ' ASC');

        return $this->db->fetchAll($select);

    }

    /**
     *    Gets Attribute Details and Checks Permission Read
     *
     * @param        $ciTypeIds
     * @param        $userId
     *
     * @return    array
     *
     * @author        Martina Reiter
     * @since         August 2016
     */
    public function getAttributeDetailsAndCheckPermission($ciTypeIds, $userId)
    {

        $select = "
			SELECT  distinct "
            . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . ", "
            . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . ","
            . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_ACTIVE . "
								
			FROM " . Db_Attribute::TABLE_NAME . " 
			
			INNER JOIN " . Db_CiTypeAttribute::TABLE_NAME . " 	ON " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::ATTRIBUTE_ID . " 	= " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
			INNER JOIN " . Db_AttributeRole::TABLE_NAME . " 	ON " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ATTRIBUTE_ID . "		= " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "				
			INNER JOIN " . Db_Role::TABLE_NAME . "  			ON " . Db_Role::TABLE_NAME . "." . Db_Role::ID . " 									= " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ROLE_ID . "
		    INNER JOIN " . Db_UserRole::TABLE_NAME . " 			ON " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::ROLE_ID . " 					= " . Db_Role::TABLE_NAME . "." . Db_Role::ID . "
			
			WHERE (" . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::CI_TYPE_ID . "  IN(" . $ciTypeIds . ")) 
			AND " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_ACTIVE . "	 			= '1'
			AND " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::USER_ID . " 					= " . $userId . "
	        AND " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::PERMISSION_READ . " 	= '1' 
	        ORDER BY " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION;

        return $this->db->fetchAll($select);
    }


    public function getCiAttributeByHistoryList($historyId, $ciId, $userId = null)
    {
        $ciId = $this->db->quote($ciId);

        $select = "SELECT " . Db_CiAttribute::TABLE_NAME . ".ID, 
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . ", 
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . ", 
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . ", 
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_DATE . ",
		" . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::VALUE . " AS " . Db_CiAttribute::VALUE_DEFAULT . ",  
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_CI . ", 
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::NOTE . ", 
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::IS_INITIAL . ", 
		" . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::NAME . " as attributeType,
		
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::HISTORY_ID . ", 
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALID_FROM . ", 
		
		NULL as " . Db_History_CiAttribute::HISTORY_ID_DELETE . ", 
		NULL as " . Db_History_CiAttribute::VALID_TO . ", 
		
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . ", 
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . ",
	 	" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DISPLAY_STYLE . ",
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NAME . ", 
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . " as 'attributeId',
		" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::NOTE . " as 'valueNote'
		FROM " . Db_CiAttribute::TABLE_NAME . " 
		INNER JOIN " . Db_Attribute::TABLE_NAME . " ON " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . " = " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . "
		INNER JOIN " . Db_AttributeType::TABLE_NAME . " ON " . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . "
		LEFT JOIN " . Db_AttributeDefaultValues::TABLE_NAME . " ON " . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::ID . " = " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_DEFAULT;

        if ($userId) {
            $select .= "
			LEFT JOIN " . Db_AttributeRole::TABLE_NAME . " ON " . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . "
			LEFT JOIN " . Db_UserRole::TABLE_NAME . " ON " . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' = ' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . "
			";
        }

        $select .= " WHERE " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::HISTORY_ID . " IN(" . $historyId . ")";
        $select .= " AND " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = $ciId";

        if ($userId) {
            $select .= ' AND (' . Db_AttributeRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' .
                Db_UserRole::ROLE_ID . ' OR ' . Db_AttributeRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' IS NULL )';
            $select .= sprintf(' AND (' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . ' = %d OR ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . ' IS NULL)', $userId);
            $select .= ' AND (' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' = \'1\' 
					OR ' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' IS NULL )';
        }
        $select .= " UNION ";

        $select .= "SELECT " . Db_History_CiAttribute::TABLE_NAME . ".ID, 
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::CI_ID . ", 
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::ATTRIBUTE_ID . ", 
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::VALUE_TEXT . ", 
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::VALUE_DATE . ",
		" . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::VALUE . " AS " . Db_History_CiAttribute::VALUE_DEFAULT . ",  
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::VALUE_CI . ", 
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::NOTE . ", 
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::IS_INITIAL . ", 
		" . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::NAME . " as attributeType,
		
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::HISTORY_ID . ", 
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::VALID_FROM . ", 
		
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::HISTORY_ID_DELETE . ", 
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::VALID_TO . ", 
		
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . ", 
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . ", 
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DISPLAY_STYLE . ",
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NAME . ", 
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . " as 'attributeId',
		" . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::NOTE . " as 'valueNote'
		FROM " . Db_History_CiAttribute::TABLE_NAME . " 
		INNER JOIN " . Db_Attribute::TABLE_NAME . " ON " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . " = " . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::ATTRIBUTE_ID . "
		INNER JOIN " . Db_AttributeType::TABLE_NAME . " ON " . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . "
		LEFT JOIN " . Db_AttributeDefaultValues::TABLE_NAME . " ON " . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::ID . " = " . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::VALUE_DEFAULT;

        if ($userId) {
            $select .= "
			LEFT JOIN " . Db_AttributeRole::TABLE_NAME . " ON " . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . "
			LEFT JOIN " . Db_UserRole::TABLE_NAME . " ON " . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' = ' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . "
			";
        }

        $select .= " WHERE " . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::HISTORY_ID_DELETE . " IN(" . $historyId . ")";
        $select .= " AND " . Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::CI_ID . " = $ciId";

        if ($userId) {
            $select .= ' AND (' . Db_AttributeRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' .
                Db_UserRole::ROLE_ID . ' OR ' . Db_AttributeRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' IS NULL )';
            $select .= sprintf(' AND (' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . ' = %d OR ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . ' IS NULL)', $userId);
            $select .= ' AND (' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' = \'1\'
					OR ' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' IS NULL )';
        }
        return $this->db->fetchAll($select);
    }


    public function getRelationsForDeleteByCiId($ciId)
    {
        $select = "SELECT * FROM " . Db_CiRelation::TABLE_NAME . " 
		WHERE " . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::V_VALID_TO . " IS NULL 
		AND (" . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::CI_ID_1 . " = '" . $ciId . "'  OR " . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::CI_ID_2 . " = '" . $ciId . "')
		";

        return $this->db->fetchAll($select);
    }


    public function getCiTypeByCiId($ciId, $date = null)
    {

        if ($date) {
            $select = "SELECT " . Db_CiType::TABLE_NAME . ".* FROM " . Db_CiType::TABLE_NAME . " 
			INNER JOIN " . Db_History_Ci::TABLE_NAME . " ON " . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::CI_TYPE_ID . " = " . Db_CiType::TABLE_NAME . "." . Db_CiType::ID
                . " WHERE " . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::ID . " = '" . $ciId . "'";

            $select .= " AND " . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::VALID_FROM . " <= '" . $date . "' 
			AND (" . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::VALID_TO . " IS NULL OR " . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::VALID_TO . " > '" . $date . "')";
        } else {
            $select = "SELECT " . Db_CiType::TABLE_NAME . ".* 
			FROM " . Db_CiType::TABLE_NAME . " 
			INNER JOIN " . Db_Ci::TABLE_NAME . " ON " . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " = " . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . " 
			WHERE " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " = '" . $ciId . "'";
        }

        return $this->db->fetchRow($select);
    }


    public function getProjectsByCiId($ciId, $date = null)
    {

        if ($date) {
            $select = "SELECT " . Db_Project::TABLE_NAME . ".* FROM " . Db_History_CiProject::TABLE_NAME . "
			INNER JOIN " . Db_Project::TABLE_NAME . " ON " . Db_Project::TABLE_NAME . "." . Db_Project::ID . " = " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::PROJECT_ID
                . " WHERE " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::CI_ID . " = '" . $ciId . "'";

            $select .= " AND " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::VALID_FROM . " <= '" . $date . "' 
			AND (" . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::VALID_TO . "  IS NULL OR " . Db_History_CiProject::TABLE_NAME . "." . Db_History_CiProject::VALID_TO . " > '" . $date . "')";
        } else {
            $select = "SELECT " . Db_Project::TABLE_NAME . ".* FROM " . Db_CiProject::TABLE_NAME . "
			INNER JOIN " . Db_Project::TABLE_NAME . " ON " . Db_Project::TABLE_NAME . "." . Db_Project::ID . " = " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::PROJECT_ID
                . " WHERE " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::CI_ID . " = '" . $ciId . "'";
        }

        return $this->db->fetchAll($select);
    }


    public function getRelationssByCiId($ciId, $date = null)
    {
        if ($date) {

            $select = "SELECT " . Db_History_CiRelation::TABLE_NAME . ".*, " . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::DESCRIPTION . " 
			FROM " . Db_History_CiRelation::TABLE_NAME . "
			INNER JOIN " . Db_CiRelationType::TABLE_NAME . " ON " . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::ID . " = " . Db_History_CiRelation::TABLE_NAME . "." . Db_History_CiRelation::CI_RELATION_TYPE_ID
                . " WHERE (" . Db_History_CiRelation::TABLE_NAME . "." . Db_History_CiRelation::CI_ID_1 . " = '" . $ciId . "'
				OR " . Db_History_CiRelation::TABLE_NAME . "." . Db_History_CiRelation::CI_ID_2 . " = '" . $ciId . "')";

            $select .= " AND " . Db_History_CiRelation::TABLE_NAME . "." . Db_History_CiRelation::VALID_FROM . " <= '" . $date . "' 
			AND (" . Db_History_CiRelation::TABLE_NAME . "." . Db_History_CiRelation::VALID_TO . "  IS NULL OR " . Db_History_CiRelation::TABLE_NAME . "." . Db_History_CiRelation::VALID_TO . " > '" . $date . "')";
        } else {

            $select = "SELECT " . Db_CiRelation::TABLE_NAME . ".*, 
			" . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::DESCRIPTION . " 
			FROM " . Db_CiRelation::TABLE_NAME . "
			INNER JOIN " . Db_CiRelationType::TABLE_NAME . " ON " . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::ID . " = " . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::CI_RELATION_TYPE_ID
                . " WHERE (" . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::CI_ID_1 . " = '" . $ciId . "'
				OR " . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::CI_ID_2 . " = '" . $ciId . "')";
        }

        return $this->db->fetchAll($select);
    }


    public function restoreSingleCiAttribute($ciAttributeId, $historyId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ID . ' =?', $ciAttributeId);
        $res    = $this->db->fetchRow($select);

        unset($res[Db_CiAttribute::ID]);
        unset($res[Db_CiAttribute::V_VALID_FROM]);
        unset($res[Db_CiAttribute::V_VALID_TO]);
        unset($res[Db_CiAttribute::V_HISTORY_ID]);
        unset($res[Db_CiAttribute::V_HISTORY_ID_DELETE]);

        $res[Db_CiAttribute::V_HISTORY_ID] = $historyId;
        $table                             = new Db_CiAttribute();
        return $table->insert($res);
    }

    public function restoreSingleCiRelation($ciRelationId, $historyId1, $historyId2)
    {
        $select = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME)
            ->where(Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::ID . ' =?', $ciRelationId);
        $res    = $this->db->fetchRow($select);

        unset($res[Db_CiRelation::ID]);
        unset($res[Db_CiRelation::V_VALID_FROM]);
        unset($res[Db_CiRelation::V_VALID_TO]);
        unset($res[Db_CiRelation::V_HISTORY_ID_1]);
        unset($res[Db_CiRelation::V_HISTORY_ID_2]);
        unset($res[Db_CiRelation::V_HISTORY_ID_1_DELETE]);
        unset($res[Db_CiRelation::V_HISTORY_ID_2_DELETE]);

        $res[Db_CiRelation::V_HISTORY_ID_1] = $historyId1;
        $res[Db_CiRelation::V_HISTORY_ID_2] = $historyId2;
        $table                              = new Db_CiRelation();

        return $table->insert($res);
    }

    public function getCiTypeByHistoryList($historyId)
    {
        $select = "SELECT " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . ", 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . ", 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::HISTORY_ID . ", 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::VALID_FROM . ", 
		" . Db_CiType::TABLE_NAME . "." . Db_CiType::DESCRIPTION . ",
		NULL AS  " . Db_History_Ci::HISTORY_ID_DELETE . ", 
		NULL AS  " . Db_History_Ci::VALID_TO . " 
		FROM " . Db_Ci::TABLE_NAME . " 
		INNER JOIN " . Db_CiType::TABLE_NAME . " ON " . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID;
        $select .= " WHERE " . Db_Ci::TABLE_NAME . "." . Db_History_Ci::HISTORY_ID . " IN(" . $historyId . ")";

        $select .= " UNION ";

        $select .= "SELECT " . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::ID . ", 
		" . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::CI_TYPE_ID . ", 
		" . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::HISTORY_ID . ", 
		" . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::VALID_FROM . ", 
		" . Db_CiType::TABLE_NAME . "." . Db_CiType::DESCRIPTION . ", 
		" . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::HISTORY_ID_DELETE . ", 
		" . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::VALID_TO . " 
		FROM " . Db_History_Ci::TABLE_NAME . " 
		INNER JOIN " . Db_CiType::TABLE_NAME . " ON " . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . " = " . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::CI_TYPE_ID;
        $select .= " WHERE " . Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::HISTORY_ID_DELETE . " IN(" . $historyId . ")";

        return $this->db->fetchAll($select);
    }

    public function createHistory($userId, $note = null)
    {
        $stmt = $this->db->query('select create_history(' . $userId . ', "' . $note . '") as historyId');
        $res  = $stmt->fetch();
        return $res['historyId'];
    }

    public function restoreCi($ci, $history_id)
    {

        //insert ci here
        $table          = new Db_Ci();
        $historyCreated = $this->getFirstHistoryEntryByCiId($ci[Db_Ci::ID]);

        $ci[Db_Ci::HISTORY_ID] = $history_id;
        $ci[Db_Ci::VALID_FROM] = date('Y-m-d H:i:s');
        $ci[Db_Ci::CREATED_AT] = $historyCreated['valid_from'];

        unset($ci['history_id_delete']);
        unset($ci['valid_to']);

        $table->insert($ci);

        //update created_at cause it gets overridden on insert
        $where = $this->db->quoteInto(Db_Ci::ID . ' =?', $ci[Db_Ci::ID]);
        $table->update($ci, $where);

    }


    public function restoreCiAttributes($ciattributes, $history_id)
    {

        $table = new Db_CiAttribute();

        foreach ($ciattributes as $ciattribute) {

            unset($ciattribute[Db_CiAttribute::ID]);
            unset($ciattribute['history_id_delete']);
            unset($ciattribute['valid_to']);
            $ciattribute[Db_CiAttribute::VALID_FROM] = date('Y-m-d H:i:s');

            $ciattribute[Db_CiAttribute::HISTORY_ID] = $history_id;

            $table->insert($ciattribute);

        }


    }

    public function restoreCiProjects($ciprojects, $history_id)
    {

        $table = new Db_CiProject();

        foreach ($ciprojects as $ciproject) {

            unset($ciproject[Db_CiProject::ID]);
            unset($ciproject['history_id_delete']);
            unset($ciproject['valid_to']);

            $ciproject[Db_CiProject::VALID_FROM] = date('Y-m-d H:i:s');
            $ciproject[Db_CiProject::HISTORY_ID] = $history_id;

            $table->insert($ciproject);


        }


    }


    public function restoreCiRelations($cirelations, $history_id)
    {


        $table = new Db_CiRelation();

        foreach ($cirelations as $cirelation) {

            unset($cirelation[Db_CiRelation::ID]);
            unset($cirelation['history_id_delete']);
            unset($cirelation['valid_to']);
            $cirelation[Db_CiRelation::VALID_FROM] = date('Y-m-d H:i:s');
            $cirelation[Db_CiRelation::HISTORY_ID] = $history_id;


            $table->insert($cirelation);


        }


    }


    /**
     * @param unknown_type $historyId
     */
    public function retrieveCiIdByHistoryId($historyId)
    {

        // check CI incl history
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, array(Db_Ci::ID))
            ->where(Db_Ci::HISTORY_ID . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_Ci::ID])
            return $hId[Db_Ci::ID];


        $select = $this->db->select()
            ->from(Db_History_Ci::TABLE_NAME, array(Db_History_Ci::ID))
            ->where(Db_History_Ci::HISTORY_ID . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_History_Ci::ID])
            return $hId[Db_History_Ci::ID];

        $select = $this->db->select()
            ->from(Db_History_Ci::TABLE_NAME, array(Db_History_Ci::ID))
            ->where(Db_History_Ci::HISTORY_ID_DELETE . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_History_Ci::ID])
            return $hId[Db_History_Ci::ID];


        // check CI_ATTRIBUTE incl history
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME, array(Db_CiAttribute::CI_ID))
            ->where(Db_CiAttribute::HISTORY_ID . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_CiAttribute::CI_ID])
            return $hId[Db_CiAttribute::CI_ID];

        $select = $this->db->select()
            ->from(Db_History_CiAttribute::TABLE_NAME, array(Db_History_CiAttribute::CI_ID))
            ->where(Db_History_CiAttribute::HISTORY_ID_DELETE . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_History_CiAttribute::CI_ID])
            return $hId[Db_History_CiAttribute::CI_ID];


        $select = $this->db->select()
            ->from(Db_History_CiAttribute::TABLE_NAME, array(Db_History_CiAttribute::CI_ID))
            ->where(Db_History_CiAttribute::HISTORY_ID . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_History_CiAttribute::CI_ID])
            return $hId[Db_History_CiAttribute::CI_ID];


        // check CI_PROJECT incl history
        $select = $this->db->select()
            ->from(Db_CiProject::TABLE_NAME, array(Db_CiProject::CI_ID))
            ->where(Db_CiProject::HISTORY_ID . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_CiProject::CI_ID])
            return $hId[Db_CiProject::CI_ID];

        $select = $this->db->select()
            ->from(Db_History_CiProject::TABLE_NAME, array(Db_History_CiProject::CI_ID))
            ->where(Db_History_CiProject::HISTORY_ID_DELETE . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_History_CiProject::CI_ID])
            return $hId[Db_History_CiProject::CI_ID];

        $select = $this->db->select()
            ->from(Db_History_CiProject::TABLE_NAME, array(Db_History_CiProject::CI_ID))
            ->where(Db_History_CiProject::HISTORY_ID . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_History_CiProject::CI_ID])
            return $hId[Db_History_CiProject::CI_ID];


        // check CI_RELATION CI_ID 1 incl history
        $select = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME, array(Db_CiRelation::CI_ID_1))
            ->where(Db_CiRelation::HISTORY_ID . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_CiRelation::CI_ID_1])
            return $hId[Db_CiRelation::CI_ID_1];

        $select = $this->db->select()
            ->from(Db_History_CiRelation::TABLE_NAME, array(Db_History_CiRelation::CI_ID_1))
            ->where(Db_History_CiRelation::HISTORY_ID_DELETE . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_History_CiRelation::CI_ID_1])
            return $hId[Db_History_CiRelation::CI_ID_1];


        $select = $this->db->select()
            ->from(Db_History_CiRelation::TABLE_NAME, array(Db_History_CiRelation::CI_ID_1))
            ->where(Db_History_CiRelation::HISTORY_ID . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_History_CiRelation::CI_ID_1])
            return $hId[Db_History_CiRelation::CI_ID_1];

        // check CI_RELATION CI_ID 2 incl history
        $select = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME, array(Db_CiRelation::CI_ID_2))
            ->where(Db_CiRelation::HISTORY_ID . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_CiRelation::CI_ID_2])
            return $hId[Db_CiRelation::CI_ID_2];

        $select = $this->db->select()
            ->from(Db_History_CiRelation::TABLE_NAME, array(Db_History_CiRelation::CI_ID_2))
            ->where(Db_History_CiRelation::HISTORY_ID_DELETE . ' =?', $historyId);
        $hId    = $this->db->fetchRow($select);

        if ($hId && $hId[Db_History_CiRelation::CI_ID_2])
            return $hId[Db_History_CiRelation::CI_ID_2];


        throw new Exception_History_NotFound();
    }

    public function getCiByhistoryIDdeleted($history_id_deleted)
    {

        $select = $this->db->select()
            ->from(Db_History_Ci::TABLE_NAME)
            ->where(Db_History_Ci::HISTORY_ID_DELETE . ' =?', $history_id_deleted);


        return $this->db->fetchRow($select);


    }

    public function getCiAttributesbyhistoryIDdeleted($histoy_id_deleted)
    {

        $select = $this->db->select()
            ->from(Db_History_CiAttribute::TABLE_NAME)
            ->where(Db_History_CiAttribute::HISTORY_ID_DELETE . ' =?', $histoy_id_deleted);

        return $this->db->fetchAll($select);

    }

    public function getCiProjectbyhistoryIDdeleted($histoy_id_deleted)
    {

        $select = $this->db->select()
            ->from(Db_History_CiProject::TABLE_NAME)
            ->where(Db_History_CiProject::HISTORY_ID_DELETE . ' =?', $histoy_id_deleted);

        return $this->db->fetchAll($select);


    }

    public function getCiRelationbyhistoryIDdeleted($histoy_id_deleted)
    {

        $select = $this->db->select()
            ->from(Db_History_CiRelation::TABLE_NAME)
            ->where(Db_History_CiRelation::HISTORY_ID_DELETE . ' =?', $histoy_id_deleted);

        return $this->db->fetchAll($select);


    }

}