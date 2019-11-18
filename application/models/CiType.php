<?php

class Dao_CiType extends Dao_Abstract
{

    private $ciTypesWithCis = null;
    private $handledParents = array();

    public function getCiTypes()
    {
        $table  = new Db_CiType();
        $select = $table->select();

        return $select;
    }

    public function getCiTypesForPagination($orderBy = null, $direction = null)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME);

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_CiType::NAME);
        }

        return $select;
    }


    public function getCiTypesForPaginationWithFilter($filter, $orderBy = null, $direction = null)
    {
        $filter = $filter . '%';
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME)
            ->where(Db_CiType::TABLE_NAME . '.' . Db_CiType::NAME . ' LIKE "%' . $filter . '%"')
            ->orWhere(Db_CiType::TABLE_NAME . '.' . Db_CiType::DESCRIPTION . ' LIKE "%' . $filter . '%"');

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_CiType::ID . ' DESC');
        }

        return $select;
    }

    /**
     * returns all CiTypes.
     *
     *
     * (non-PHPdoc)
     * @see application/models/CiTypeInterface#getCiTypeRowset()
     */
    public function getCiTypeRowset()
    {
        $table  = new Db_CiType();
        $select = $table->select()
            ->order(Db_CiType::TABLE_NAME . '.' . Db_CiType::NAME);

        return $table->fetchAll($select);
    }

    public function getCiTypeRowsetWithFilter($filter)
    {
        $table  = new Db_CiType();
        $select = $table->select()
            ->where(Db_CiType::NAME . ' LIKE "' . $filter . '%"')
            ->order(Db_CiType::TABLE_NAME . '.' . Db_CiType::NAME);

        return $table->fetchAll($select);
    }

    /**
     * returns all root CiTypes.
     *
     *
     * (non-PHPdoc)
     * @see application/models/CiTypeInterface#getCiTypeRowset()
     */
    public function getRootCiTypeRowset($orderBy = null, $direction = null)
    {
        $table  = new Db_CiType();
        $select = $table->select()
            ->where(Db_CiType::TABLE_NAME . '.' . Db_CiType::PARENT_CI_TYPE_ID . ' = 0')
            ->where(Db_CiType::TABLE_NAME . '.' . Db_CiType::IS_ACTIVE . ' = ?', '1');

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else $select->order(Db_CiType::TABLE_NAME . '.' . Db_CiType::NAME . ' ASC');

        return $table->fetchAll($select);
    }

    /**
     * retrieves all parent attributes of the given ci type
     *
     * @see application/models/CiTypeInterface#retrieveCiTypeHierarchy($typeId)
     */
    public function retrieveCiTypeHierarchy($typeId)
    {
        $typeArray        = array();
        $hasAnotherParent = true;

        $currenttypeId = $typeId;

        while ($hasAnotherParent) {
            $currentType = $this->getRawCiType($currenttypeId);
            if (!$currentType) {
                $hasAnotherParent = false;
                break;
            }
            array_push($typeArray, $currentType[Db_CiType::ID]);

            if ($currentType[Db_CiType::PARENT_CI_TYPE_ID] == $currentType[Db_CiType::ID]) {
                $currentType[Db_CiType::PARENT_CI_TYPE_ID] = 0;
            }

            if (is_null($currentType[Db_CiType::PARENT_CI_TYPE_ID]) || $currentType[Db_CiType::PARENT_CI_TYPE_ID] === '0') {
                $hasAnotherParent = false;
            } else {
                $currenttypeId = $currentType[Db_CiType::PARENT_CI_TYPE_ID];
            }
        }

        return $typeArray;
    }


    public function retrieveCiTypeHierarchyByCiId($ciId)
    {
        $select = $this->db->select()->from(Db_Ci::TABLE_NAME)->where(Db_Ci::ID . ' = ?', $ciId);
        $ci     = $this->db->fetchRow($select);

        $typeArray        = array();
        $hasAnotherParent = true;

        $currenttypeId = $ci[Db_Ci::CI_TYPE_ID];

        while ($hasAnotherParent) {
            $currentType = $this->getRawCiType($currenttypeId);
            array_push($typeArray, array($currentType[Db_CiType::ID]));

            if ($currentType[Db_CiType::PARENT_CI_TYPE_ID] == $currentType[Db_CiType::ID]) {
                $currentType[Db_CiType::PARENT_CI_TYPE_ID] = 0;
            }

            if (is_null($currentType[Db_CiType::PARENT_CI_TYPE_ID]) || $currentType[Db_CiType::PARENT_CI_TYPE_ID] === '0') {
                $hasAnotherParent = false;
            } else {
                $currenttypeId = $currentType[Db_CiType::PARENT_CI_TYPE_ID];
            }
        }

        return $typeArray;
    }

    public function getBreadcrumbHierarchy($typeId)
    {
        $typeArray        = array();
        $hasAnotherParent = true;

        $currenttypeId = $typeId;

        while ($hasAnotherParent) {
            $currentType              = $this->getRawCiType($currenttypeId);
            if (!$currentType) {
                $hasAnotherParent = false;
                break;
            }
            $currentType['crumbType'] = 'ci_type';
            array_push($typeArray, $currentType);

            if ($currentType[Db_CiType::PARENT_CI_TYPE_ID] == $currentType[Db_CiType::ID]) {
                $currentType[Db_CiType::PARENT_CI_TYPE_ID] = 0;
            }

            if (is_null($currentType[Db_CiType::PARENT_CI_TYPE_ID]) || $currentType[Db_CiType::PARENT_CI_TYPE_ID] === '0') {
                $hasAnotherParent = false;
            } else {
                $currenttypeId = $currentType[Db_CiType::PARENT_CI_TYPE_ID];
            }
        }

        return $typeArray;
    }

    /**
     * retrieves all childs of the given ci type
     *
     * @see application/models/CiTypeInterface#retrieveCiTypeChildElements($typeId)
     */
    public function retrieveCiTypeChildElements($typeId)
    {
        $table  = new Db_CiType();
        $select = $table->select()
            ->where(Db_CiType::TABLE_NAME . '.' . Db_CiType::PARENT_CI_TYPE_ID . ' = ?', $typeId)
            ->where(Db_CiType::TABLE_NAME . '.' . Db_CiType::IS_ACTIVE . ' = ?', '1')
            ->order(Db_CiType::TABLE_NAME . '.' . Db_CiType::NAME . ' ASC');

        $rowset = $table->fetchAll($select);
        return $rowset;
    }

    public function retrieveCiTypeChildElementsforDelete($typeId)
    {
        $table  = new Db_CiType();
        $select = $table->select()
            ->where(Db_CiType::TABLE_NAME . '.' . Db_CiType::PARENT_CI_TYPE_ID . ' = ?', $typeId)
            ->where(Db_CiType::TABLE_NAME . '.' . Db_CiType::IS_ACTIVE . ' = ?', '1')
            ->order(Db_CiType::TABLE_NAME . '.' . Db_CiType::ORDER_NUMBER . ' ASC');

        $rowset = $table->fetchAll($select);
        return $rowset->toArray();
    }

    /**
     * ATTENTION: high memory usage!!
     *
     * (non-PHPdoc)
     * @see application/models/CiTypeInterface#getCiTypeRowsetByProjectID($projectId)
     */
    public function getCiTypeRowsetByProjectID($userId, $projectId)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(array('cit' => Db_CiType::TABLE_NAME))
            ->join(Db_Ci::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' = cit.' . Db_CiType::ID, array())
            ->join(Db_CiProject::TABLE_NAME, Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID, array())
            ->join(Db_UserProject::TABLE_NAME, Db_UserProject::TABLE_NAME . '.' . Db_UserProject::PROJECT_ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID, array())
            ->where(Db_UserProject::TABLE_NAME . '.' . Db_UserProject::USER_ID . ' =?', $userId)
            ->order('cit.' . Db_CiType::PARENT_CI_TYPE_ID . ' DESC')
            ->order('cit.' . Db_CiType::ORDER_NUMBER . ' DESC')
            ->order('cit.' . Db_CiType::DESCRIPTION . ' DESC');
        if (!is_null($projectId))
            $select->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' = ?', $projectId);

        // this statement returns only those citypes with a CI. parent ci types are NOT included
        $this->ciTypesWithCis = $this->db->fetchAll($select);

        // get all ci types
        $allCiTypes = $this->getCiTypeRowset(true);

        foreach ($this->ciTypesWithCis as $key => $ciType) {
            if ($ciType[Db_CiType::PARENT_CI_TYPE_ID] == $ciType[Db_CiType::ID]) {
                $ciType[Db_CiType::PARENT_CI_TYPE_ID]                     = '0';
                $this->ciTypesWithCis[$key][Db_CiType::PARENT_CI_TYPE_ID] = '0';
            }

            $parent = $ciType[Db_CiType::PARENT_CI_TYPE_ID];
            if (!is_null($parent) && $parent !== '0' && $parent != 0 && !$this->handledParents[$parent]) {
                $this->addParentCiType($allCiTypes, $ciType);
                $this->handledParents[$parent] = true;
            }
        }

        return $this->ciTypesWithCis;
    }

    private function addParentCiType($allCiTypes, $ciType)
    {
        foreach ($allCiTypes as $key => $item) {
            if ($item[Db_CiType::PARENT_CI_TYPE_ID] == $item[Db_CiType::ID]) {
                $item[Db_CiType::PARENT_CI_TYPE_ID] = '0';
                //$allCiTypes[$key][Db_CiType::PARENT_CI_TYPE_ID] = 0;
            }

            if ($item[Db_CiType::ID] == $ciType[Db_CiType::PARENT_CI_TYPE_ID]) {
                // found a the parent CI Type
                if (!$this->isMemberOf($this->ciTypesWithCis, $item)) {
                    if (!is_null($item))
                        array_push($this->ciTypesWithCis, $item);
                    $par = $item[Db_CiType::PARENT_CI_TYPE_ID];

                    if (!is_null($par) && $par !== '0' && $par != 0 && !$this->handledParents[$par]) {
                        $this->addParentCiType($allCiTypes, $item);
                        $this->handledParents[$par] = true;
                    }
                }
                return;
            }
        }
    }

    private function isMemberOf($listToCheck, $itemToCheck)
    {
        foreach ($listToCheck as $list) {
            if ($listToCheck[Db_CiType::ID] === $itemToCheck[Db_CiType::ID]) {
                return true;
            }
        }
        return false;
    }

    public function insertCiType($ciType)
    {
        $table = new Db_CiType();
        return $table->insert($ciType);
    }

    public function updateCiType($ciType, $formId)
    {
        $table = new Db_CiType();
        $where = $table->getAdapter()->quoteInto(Db_CiType::ID . ' = ?', $formId);
        return $table->update($ciType, $where);
    }

    public function getRawCiType($id)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME)
            ->where(Db_CiType::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }


    public function getCiType($id)
    {
        return $this->getRawCiType($id);
    }

    public function getCiTypeByName($name)
    {
        $select = $this->db->select()->from(Db_CiType::TABLE_NAME)->where(Db_CiType::NAME . ' =?', $name);
        return $this->db->fetchRow($select);
    }

    public function getCiTypeByDescription($name)
    {
        $select = $this->db->select()->from(Db_CiType::TABLE_NAME)->where(Db_CiType::DESCRIPTION . ' =?', $name);
        return $this->db->fetchRow($select);
    }

    public function getDefaultProjectByCiTypeId($id)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME)
            ->where(Db_CiType::ID . ' =?', $id);

        $result = $this->db->fetchRow($select);

        if ($result[Db_CiType::DEFAULT_PROJECT_ID])
            return $result[Db_CiType::DEFAULT_PROJECT_ID];
        elseif ($result[Db_CiType::PARENT_CI_TYPE_ID])
            return $this->getDefaultProjectByCiTypeId($result[Db_CiType::PARENT_CI_TYPE_ID]);
        else
            return false;
    }


    public function getCiTypeByCiId($ciId)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME)
            ->join(Db_Ci::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' =?', $ciId);
        return $this->db->fetchRow($select);
    }

    public function getCiTypeHistoryByCiId($ciId)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME)
            ->join(Db_History_Ci::TABLE_NAME, Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->where(Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::ID . ' =?', $ciId)
            ->order(Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::VALID_FROM);
        return $this->db->fetchAll($select);
    }

    public function getCi($ciId)
    {
        $select = $this->db->select()->from(Db_Ci::TABLE_NAME)->where(Db_Ci::ID . ' =?', $ciId);
        return $this->db->fetchRow($select);
    }

    public function getCiIcon($ciId)
    {
        $ciRow = $this->db->fetchRow($this->db->select()->from(Db_Ci::TABLE_NAME, array(Db_Ci::ICON, Db_Ci::CI_TYPE_ID))->where(Db_Ci::ID . ' =?', $ciId));
        if ($ciRow[Db_Ci::ICON]) {
            return $ciRow[Db_Ci::ICON];
        }

        if (!$ciRow) {
            return null;
        }
        $ciTypeRow = $this->db->fetchRow($this->db->select()->from(Db_CiType::TABLE_NAME, array(Db_CiType::ICON))->where(Db_CiType::ID . ' =?', $ciRow[Db_Ci::CI_TYPE_ID]));
        if ($ciTypeRow[Db_CiType::ICON])
            return $ciTypeRow[Db_CiType::ICON];
        else
            return $this->getCiIconFromParent($ciRow[Db_Ci::CI_TYPE_ID]);
    }

    private function getCiIconFromParent($ciTypeId)
    {
        $parentRow = $this->db->fetchRow($this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::ID, Db_CiType::ICON))
            ->where(Db_CiType::ID . ' = ?', $this->db->select()
                ->from(Db_CiType::TABLE_NAME, array(Db_CiType::PARENT_CI_TYPE_ID))
                ->where(Db_CiType::ID . ' = ?', $ciTypeId)
                ->where(Db_CiType::PARENT_CI_TYPE_ID . ' != 0 AND ' . Db_CiType::PARENT_CI_TYPE_ID . ' IS NOT NULL'))
        );

        if (!$parentRow) {
            return null;
        }

        if ($parentRow[Db_CiType::ICON])
            return $parentRow[Db_CiType::ICON];
        else
            return $this->getCiIconFromParent($parentRow[Db_CiType::ID]);
    }

    public function getCiByCiTypeId($ciType)
    {
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME)
            ->where(Db_Ci::CI_TYPE_ID . ' =?', $ciType);

        return $this->db->fetchAll($select);
    }

    public function deactivateCiType($ciType)
    {
        $select = "UPDATE " . Db_CiType::TABLE_NAME . " SET " . Db_CiType::IS_ACTIVE . " = '0' 
		WHERE " . Db_CiType::ID . " = '" . $ciType . "'";
        $this->db->query($select);
    }

    public function activateCiType($ciType)
    {
        $select = "UPDATE " . Db_CiType::TABLE_NAME . " SET " . Db_CiType::IS_ACTIVE . " = '1' 
		WHERE " . Db_CiType::ID . " = '" . $ciType . "'";
        $this->db->query($select);
    }

    public function deleteCiType($ciType)
    {

        if ((int)$ciType == 0) {
            return false;
        }

        $select = "DELETE " . Db_ImportFileValidation::TABLE_NAME . ", " . Db_ImportFileValidationAttributes::TABLE_NAME . " 
					FROM " . Db_ImportFileValidation::TABLE_NAME . ", " . Db_ImportFileValidationAttributes::TABLE_NAME . " 
					WHERE " . Db_ImportFileValidation::TABLE_NAME . "." . Db_ImportFileValidation::ID . " = " . Db_ImportFileValidationAttributes::TABLE_NAME . "." . Db_ImportFileValidationAttributes::VALIDATION_ID . " 
						AND " . Db_ImportFileValidation::TABLE_NAME . "." . Db_ImportFileValidation::CI_TYPE_ID . " = '" . $ciType . "'";
        $this->db->query($select);

        $select = "DELETE FROM " . Db_SearchList::TABLE_NAME . " WHERE " . Db_SearchList::CI_TYPE_ID . " = '" . $ciType . "'";
        $this->db->query($select);

        $select = "DELETE FROM " . Db_AttributeDefaultCitype::TABLE_NAME . " WHERE " . Db_AttributeDefaultCitype::CI_TYPE_ID . " = '" . $ciType . "'";
        $this->db->query($select);

        $select = "DELETE FROM " . Db_CiTypeAttribute::TABLE_NAME . " WHERE " . Db_CiTypeAttribute::CI_TYPE_ID . " = '" . $ciType . "'";
        $this->db->query($select);

        $select = "DELETE FROM " . Db_CiTypeRelationType::TABLE_NAME . " WHERE " . Db_CiTypeRelationType::CI_TYPE_ID . " = '" . $ciType . "'";
        $this->db->query($select);

        $select = "DELETE FROM " . Db_CiType::TABLE_NAME . " WHERE " . Db_CiType::ID . " = '" . $ciType . "'";
        $this->db->query($select);
    }

    public function getRawCiTypeByCiId($ciId)
    {
        // get ci_type_id
        $ciDao = new Dao_Ci();
        $ciDto = $ciDao->getCi($ciId);

        if ($ciDto) {
            return $this->getRawCiType($ciDto->getCiTypeId());
        } else {
            return null;
        }
    }


    public function getCiTypesByAttributeId($attributeID)
    {
        $select = $this->db->select()
            ->from(Db_CiTypeAttribute::TABLE_NAME, array(Db_CiTypeAttribute::IS_MANDATORY))
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' = ' . Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::CI_TYPE_ID)
            ->where(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID . ' =?', $attributeID)
            ->order(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::IS_MANDATORY);

        return $this->db->fetchAll($select);
    }

    public function getAttributesByCiTypeId($ciTypeId)
    {
        $select = $this->db->select()
            ->from(Db_CiTypeAttribute::TABLE_NAME, array(Db_CiTypeAttribute::IS_MANDATORY))
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID)
            ->where(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::CI_TYPE_ID . ' =?', $ciTypeId)
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ORDER_NUMBER)
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME);

        return $this->db->fetchAll($select);
    }


    public function getAttributesByCiTypeIds(array $ciTypeIds)
    {

        $ciTypeIds = implode(',', $ciTypeIds);

        $select = $this->db->select()
            ->from(Db_CiTypeAttribute::TABLE_NAME, array(Db_CiTypeAttribute::IS_MANDATORY))
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID)
            ->where(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::CI_TYPE_ID . ' IN (' . $ciTypeIds . ')')
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ORDER_NUMBER)
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME);

        return $this->db->fetchAll($select);
    }


    public function getCitypeslinkedtoAttributes($ciTypeId, $attributeid)
    {
        if ($attributeid == null)
            $attributeid = 0;
        $select = $this->db->select()
            ->from(Db_CiTypeAttribute::TABLE_NAME)
            ->where(Db_CiTypeAttribute::ATTRIBUTE_ID . ' = ' . $attributeid . ' AND ' . Db_CiTypeAttribute::CI_TYPE_ID . ' = ' . $ciTypeId);


        return $this->db->fetchAll($select);
    }


    public function getRelationsByCiTypeId($ciTypeId)
    {
        $select = $this->db->select()
            ->from(Db_CiTypeRelationType::TABLE_NAME)
            ->join(Db_CiRelationType::TABLE_NAME, Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ' . Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_RELATION_TYPE_ID)
            ->where(Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_TYPE_ID . ' =?', $ciTypeId);

        return $this->db->fetchAll($select);
    }

    public function getAttributesByCiTypeHierarchy($ciTypeList)
    {
        $select = $this->db->select()
            ->from(Db_CiTypeAttribute::TABLE_NAME, array(Db_CiTypeAttribute::IS_MANDATORY))
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID)
            ->where(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::CI_TYPE_ID . ' IN(' . $ciTypeList . ')')
            ->order(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::IS_MANDATORY);

        return $this->db->fetchAll($select);
    }

    public function saveCiTypeAttribute($ciTypeId, $attributeId, $mandatory)
    {
        $table = new Db_CiTypeAttribute();

        $ci                                   = array();
        $ci[Db_CiTypeAttribute::CI_TYPE_ID]   = $ciTypeId;
        $ci[Db_CiTypeAttribute::ATTRIBUTE_ID] = $attributeId;
        $ci[Db_CiTypeAttribute::IS_MANDATORY] = $mandatory;

        $table->insert($ci);
    }

    public function saveCiTypeRelation($ciTypeId, $ciTypeRelationId, $maxAmount, $ordernumber)
    {
        $table = new Db_CiTypeRelationType();

        $ci                                             = array();
        $ci[Db_CiTypeRelationType::CI_TYPE_ID]          = $ciTypeId;
        $ci[Db_CiTypeRelationType::CI_RELATION_TYPE_ID] = $ciTypeRelationId;
        $ci[Db_CiTypeRelationType::MAX_AMOUNT]          = $maxAmount;
        $ci[Db_CiTypeRelationType::ORDER_NUMBER]        = $ordernumber;

        $table->insert($ci);
    }


    public function deleteCiTypeAttribute($ciTypeId, $attributeId)
    {
        $table = new Db_CiTypeAttribute();
        $where = array(Db_CiTypeAttribute::CI_TYPE_ID . ' = ?'   => $ciTypeId,
                       Db_CiTypeAttribute::ATTRIBUTE_ID . ' = ?' => $attributeId,
        );

        $table->delete($where);
    }

    public function deleteCiTypeRelationType($ciTypeId, $relationTypeId)
    {
        $table = new Db_CiTypeRelationType();
        $where = array(Db_CiTypeRelationType::CI_TYPE_ID . ' = ?'          => $ciTypeId,
                       Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' = ?' => $relationTypeId,
        );

        $table->delete($where);
    }

    public function deleteCiTypeAttributeByTypeId($ciTypeId)
    {
        $table = new Db_CiTypeAttribute();
        $where = array(Db_CiTypeAttribute::CI_TYPE_ID . ' = ?' => $ciTypeId,
        );
        $table->delete($where);
    }

    public function deleteAllCiTypeAttributes($attributeId)
    {
        $table = new Db_CiTypeAttribute();
        $where = array(Db_CiTypeAttribute::ATTRIBUTE_ID . ' = ?' => $attributeId,
        );

        $table->delete($where);
    }

    public function removeCiTypeImage($typeId)
    {
        $sql = "UPDATE " . Db_CiType::TABLE_NAME . " SET " . Db_CiType::ICON . " = NULL WHERE " . Db_CiType::ID . " = '$typeId'";
        $this->db->query($sql);
    }

    public function getActiveCiTypesAutoComplete($query)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::NAME, Db_CiType::ID))
            ->where(Db_CiType::NAME . ' LIKE "%' . $query . '%"');

        return $this->db->fetchAll($select);
    }

    public function deleteAllCiTypeRelationTypes($relationTypeId)
    {
        $table = new Db_CiTypeRelationType();
        $where = array(Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' = ?' => $relationTypeId,
        );

        $table->delete($where);
    }

    public function deleteRelationsByCiTypeId($typeId)
    {
        $table = new Db_CiTypeRelationType();
        $where = array(Db_CiTypeRelationType::CI_TYPE_ID . ' = ?' => $typeId,
        );

        $table->delete($where);
    }

    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_CiType::NAME . ' LIKE ?', $value);

        if($id > 0) {
            $select->where(Db_CiType::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }

    public function updateCiCiType($ciId, $ciType)
    {
        $update = "UPDATE " . Db_Ci::TABLE_NAME . ' SET ' . Db_Ci::CI_TYPE_ID . ' = "' . $ciType . '" WHERE ' . Db_Ci::ID . ' = "' . $ciId . '"';
        $this->db->query($update);
    }


    public function getCiTickets($ciId)
    {
        $select = $this->db->select()
            ->from(Db_CiTicket::TABLE_NAME)
            ->where(Db_CiTicket::CI_ID . ' =?', $ciId);
        return $this->db->fetchAll($select);
    }

    public function getCiEvents($ciId)
    {
        $select = $this->db->select()
            ->from(Db_CiEvent::TABLE_NAME)
            ->where(Db_CiEvent::CI_ID . ' =?', $ciId);
        return $this->db->fetchAll($select);
    }

    public function getCountProjectsByProjectId($projectId)
    {
        $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_CiType::TABLE_NAME . "
				   WHERE " . Db_CiType::DEFAULT_PROJECT_ID . " = '" . $projectId . "'
				   ";

        return $this->db->fetchRow($select);
    }

}