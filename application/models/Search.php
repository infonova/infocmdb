<?php

class Dao_Search extends Dao_Abstract
{


    public function callSearchProcedure($addString, $removeString, $restrictString, $projectList, $sessionId, $maxSearch = 5, $relationType = '', $history = false)
    {

        if ($history) {
            $sql = "call search_simple_history('$addString', '$removeString','$restrictString', '$projectList', '$sessionId', $maxSearch, '$relationType');";
        } else {
            $sql = "call search_simple('$addString', '$removeString','$restrictString', '$projectList', '$sessionId', $maxSearch, '$relationType');";
        }
        $this->db->query($sql);
    }

    public function searchCiId($searchstring, $pid_string)
    {
        $sql = "SELECT distinct 
			c." . Db_Ci::ID . " AS ci_id, 
			ct." . Db_CiType::ID . " AS citype_id, 
			ct." . Db_CiType::NAME . " AS citype_name 
			FROM 
			" . Db_CiProject::TABLE_NAME . " cp, 
			" . Db_Ci::TABLE_NAME . " c
			INNER JOIN " . Db_CiType::TABLE_NAME . " ct ON c." . Db_Ci::CI_TYPE_ID . " = ct." . Db_CiType::ID . "
			WHERE cp." . Db_CiProject::PROJECT_ID . " IN (" . $pid_string . ")
			AND c.ID = '" . $searchstring['value'][0] . "'";

        return $this->db->fetchRow($sql);
    }

    public function getAllCiIds($pid_string)
    {
        $sql = "
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . "
		LEFT JOIN " . Db_CiProject::TABLE_NAME . " ON " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
		WHERE " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::PROJECT_ID . " IN (" . $pid_string . ")
		";

        return $this->db->fetchAll($sql);
    }

    public function getAllCiIdsWithRelation($pid_string, $relationTypeId)
    {
        $sql = "
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . " 
		INNER JOIN " . Db_CiType::TABLE_NAME . " ON " . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " = " . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . " 
		INNER JOIN " . Db_CiTypeRelationType::TABLE_NAME . " ON " . Db_CiTypeRelationType::TABLE_NAME . "." . Db_CiTypeRelationType::CI_TYPE_ID . " = " . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . "
		INNER JOIN " . Db_CiProject::TABLE_NAME . " ON " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
		WHERE " . Db_CiTypeRelationType::TABLE_NAME . "." . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . " = '" . $relationTypeId . "'
		AND " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::PROJECT_ID . " IN (" . $pid_string . ")
		";
        return $this->db->fetchAll($sql);
    }

    public function search($searchstring, $pid_string = null)
    {

        // suche nach ci_attributes
        $sql     = "
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . "
		INNER JOIN " . Db_CiProject::TABLE_NAME . " ON " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
		INNER JOIN " . Db_CiType::TABLE_NAME . " ON " . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . "  
		
		WHERE
		" . Db_CiType::TABLE_NAME . "." . Db_CiType::IS_CI_ATTACH . " = '1'  
		AND " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::PROJECT_ID . " IN (" . $pid_string . ")
		AND ( ";
        $isFirst = true;

        foreach ($searchstring['value'] as $key => $search) {
            if ($isFirst) {
                $sql .= " " . Db_CiType::TABLE_NAME . "." . Db_CiType::DESCRIPTION . " ";
                if ($searchstring['action'][$key] == '-') {
                    $sql .= " NOT ";
                }
                $isFirst = false;
            } else {
                if ($searchstring['action'][$key] == '-') {
                    $sql .= " AND " . Db_CiType::TABLE_NAME . "." . Db_CiType::DESCRIPTION . " NOT ";
                } else {
                    $sql .= " OR " . Db_CiType::TABLE_NAME . "." . Db_CiType::DESCRIPTION . " ";
                }
            }
            $sql .= "LIKE '" . $search . "'";
        }

        $sql .= ")";


        $sql .= " UNION 
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . "
		INNER JOIN " . Db_CiProject::TABLE_NAME . " ON " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
		INNER JOIN " . Db_CiAttribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
		
		WHERE " . Db_CiProject::TABLE_NAME . "." . Db_CiProject::PROJECT_ID . " IN (" . $pid_string . ") 
		AND (";


        $isFirst = true;
        foreach ($searchstring['value'] as $key => $search) {
            if ($isFirst) {
                $sql .= " " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . " ";
                if ($searchstring['action'][$key] == '-') {
                    $sql .= " NOT ";
                }
                $isFirst = false;
            } else {
                if ($searchstring['action'][$key] == '-') {
                    $sql .= " AND " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . " NOT ";
                } else {
                    $sql .= " OR " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . " ";
                }
            }
            $sql .= "LIKE '" . $search . "' ";
        }
        $sql .= ")";


        return $this->db->fetchAll($sql);
    }


    public function searchCiProject($projectList)
    {
        $sql = "
		SELECT distinct
		" . Db_CiProject::TABLE_NAME . "." . Db_CiProject::CI_ID . "
		FROM " . Db_CiProject::TABLE_NAME . " 
		WHERE " . Db_CiProject::PROJECT_ID . " IN (" . $projectList . ")
		";
        return $this->db->fetchAll($sql);
    }

    public function searchCiTypeValues($searchstring, $name = true, $description = false, $note = false)
    {
        $sql = "
		SELECT 
		" . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . "
		FROM " . Db_CiType::TABLE_NAME . " ";

        if ($name) {
            $sql .= " WHERE " . Db_CiType::TABLE_NAME . "." . Db_CiType::NAME . " LIKE '" . $searchstring . "' ";
        }

        if ($description) {
            if ($name) {
                $sql .= " OR " . Db_CiType::TABLE_NAME . "." . Db_CiType::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_CiType::TABLE_NAME . "." . Db_CiType::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            }
        }

        if ($note) {
            if ($name || $description) {
                $sql .= " OR " . Db_CiType::TABLE_NAME . "." . Db_CiType::NOTE . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_CiType::TABLE_NAME . "." . Db_CiType::NOTE . " LIKE '" . $searchstring . "' ";
            }
        }
        return $this->db->fetchAll($sql);
    }


    public function searchCiType($typeId)
    {
        $sql = "
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . "
		WHERE " . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " IN (" . $typeId . ") 
		";
        return $this->db->fetchAll($sql);
    }


    public function searchCiAttributeText($searchstring)
    {
        $sql = "
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . "
		INNER JOIN " . Db_CiAttribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "  
		
		WHERE " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . " LIKE '" . $searchstring . "' 
		";
        return $this->db->fetchAll($sql);
    }

    public function searchAttributeDefaultValues($searchstring)
    {
        $sql = "
		SELECT 
		" . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::ID . "
		FROM " . Db_AttributeDefaultValues::TABLE_NAME . "
		WHERE " . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::VALUE . " LIKE '" . $searchstring . "'  
		";

        return $this->db->fetchAll($sql);
    }

    public function searchCiAttributeDefault($advList)
    {
        $sql = "
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . "
		INNER JOIN " . Db_CiAttribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
		
		WHERE " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_DEFAULT . " IN (" . $advList . ") 
		  
		";

        return $this->db->fetchAll($sql);
    }

    public function searchCiAttributeDate($searchstring)
    {
        $sql = "
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . "
		INNER JOIN " . Db_CiAttribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "  
		
		WHERE " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_DATE . " LIKE '" . $searchstring . "' 
		";
        return $this->db->fetchAll($sql);
    }


    public function searchAttributeValues($searchstring, $name = true, $description = false, $note = false)
    {
        $sql = "
		SELECT 
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
		FROM " . Db_Attribute::TABLE_NAME . " ";


        if ($name) {
            $sql .= " WHERE " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NAME . " LIKE '" . $searchstring . "' ";
        }

        if ($description) {
            if ($name) {
                $sql .= " OR " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            }
        }

        if ($note) {
            if ($name || $description) {
                $sql .= " OR " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NOTE . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NOTE . " LIKE '" . $searchstring . "' ";
            }
        }

        return $this->db->fetchAll($sql);
    }

    public function searchAttribute($advList)
    {
        $sql = "
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . "
		INNER JOIN " . Db_CiAttribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
		
		WHERE " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . " IN (" . $advList . ") 
		  
		";

        return $this->db->fetchAll($sql);
    }


    public function getAttributeDefaultValues($searchstring)
    {
        $sql = "select 
		" . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::ID . " adv_id 
		from 
		" . Db_AttributeDefaultValues::TABLE_NAME . " 
		where " . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::VALUE . " like '" . $searchstring . "'";
        return $this->db->fetchAll($sql);
    }


    public function getProjects($userId)
    {
        $sql = $this->db->select()
            ->from(Db_UserProject::TABLE_NAME, array('project_id' => Db_UserProject::PROJECT_ID))
            ->join(Db_Project::TABLE_NAME, Db_Project::TABLE_NAME . '.' . Db_Project::ID . ' = ' . Db_UserProject::TABLE_NAME . '.' . Db_UserProject::PROJECT_ID, array())
            ->where(Db_Project::TABLE_NAME . '.' . Db_Project::IS_ACTIVE . ' =?', '1')
            ->where(Db_UserProject::TABLE_NAME . '.' . Db_UserProject::USER_ID . ' =?', $userId);
        return $this->db->fetchAll($sql);
    }


    public function getCiTypeByCiId($ciIdList)
    {
        $sql = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::ID))
            ->join(Db_Ci::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' IN (' . $ciIdList . ')');
        return $this->db->fetchAll($sql);
    }


    public function getAtrributeValuesForCi($ciId, $typeId, $attributeList, &$projectId = null)
    {
        $comb = array();
        array_push($comb, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);
        array_push($comb, Db_CiType::TABLE_NAME . '.' . Db_CiType::DESCRIPTION . ' AS ciType');

        foreach ($attributeList as $attr) {
            array_push($comb, 'MAX( CASE WHEN ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . $attr[Db_Attribute::ID] . ' THEN '
                . 'CONCAT_WS("", cast(' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT . ' as char), '
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DATE . ', cast('
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DEFAULT . ' as char)) ' .
                ' ELSE \'\' END) AS ' . $attr[Db_Attribute::NAME] . '');

        }

        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, $comb)
            ->join(Db_CiAttribute::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID, array())
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID, array())
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' = ?', $typeId)
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' IN(' . $ciId . ')')
            ->group(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);;
        if (!is_null($projectId)) {
            $select->join(Db_CiProject::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array());
            $select->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' = ?', $projectId);
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        }
        return $this->db->fetchAll($select);
    }


    public function searchCiTypeRelations($relation)
    {
        $sql = $this->db->select()
            ->distinct()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::ID))
            ->join(Db_CiTypeRelationType::TABLE_NAME, Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->where(Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' = ?', $relation);
        return $this->db->fetchAll($sql);
    }


    public function getSearchSession()
    {
        $table = new Db_SearchSession();

        $time                            = time() + 300;
        $data                            = array();
        $data[Db_SearchSession::TIMEOUT] = date($time);

        return $table->insert($data);
    }

    public function deleteForbiddenSearchResult($session, $pid_string)
    {
        $cql = "DELETE FROM " . Db_SearchResult::TABLE_NAME . " 
				WHERE " . Db_SearchResult::SESSION . " = '$session' 
				AND " . Db_SearchResult::CI_ID . " NOT IN (
														SELECT " . Db_CiProject::CI_ID . " 
														FROM " . Db_CiProject::TABLE_NAME . " 
														WHERE " . Db_CiProject::PROJECT_ID . "  IN ($pid_string))";
        $this->db->query($cql);
    }


    public function deleteNotMatchingRelationTypes($session, $relationTypeId)
    {
        $subSql = "
		SELECT 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id  
		FROM " . Db_Ci::TABLE_NAME . " 
		INNER JOIN " . Db_CiType::TABLE_NAME . " ON " . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " = " . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . " 
		INNER JOIN " . Db_CiTypeRelationType::TABLE_NAME . " ON " . Db_CiTypeRelationType::TABLE_NAME . "." . Db_CiTypeRelationType::CI_TYPE_ID . " = " . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . "
		WHERE " . Db_CiTypeRelationType::TABLE_NAME . "." . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . " = '" . $relationTypeId . "'
		";


        $cql = "DELETE FROM " . Db_SearchResult::TABLE_NAME . " 
				WHERE " . Db_SearchResult::SESSION . " = '$session' 
				AND " . Db_SearchResult::CI_ID . " NOT IN ($subSql)";
        $this->db->query($cql);
    }


    public function countSearchResult($session)
    {
        $sql = $this->db->select()
            ->distinct()
            ->from(Db_SearchResult::TABLE_NAME, array('cnt' => 'COUNT(*)'))
            ->where(Db_SearchResult::SESSION . ' =?', $session);

        return $this->db->fetchRow($sql);
    }

    public function getSearchResult($session, $limit, $offset)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_SearchResult::TABLE_NAME)
            ->where(Db_SearchResult::SESSION . ' =?', $session)
            ->group(Db_SearchResult::CI_ID)
            ->order(Db_SearchResult::CITYPE_ID)
            ->limit($limit, $offset);
        return $this->db->fetchAll($select);
    }

    public function getCurrentSearchSession($session)
    {
        $select = $this->db->select()
            ->from(Db_SearchSession::TABLE_NAME)
            ->where(Db_SearchSession::ID . ' =?', $session);
        return $this->db->fetchRow($select);
    }

    public function getCiSearch($session, $searchString)
    {
        $sql = "INSERT INTO search_result(session, ci_id, citype_id) 
				SELECT distinct '$session',
				" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
				" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
				FROM " . Db_Ci::TABLE_NAME . " 
				WHERE " . Db_Ci::ID . " = '" . $searchString . "' 
				";
        $this->db->query($sql);
    }
}