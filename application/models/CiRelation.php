<?php

class Dao_CiRelation extends Dao_Abstract
{

    public function getRelationsForPagination($orderBy = null, $direction = null)
    {
        $select = $this->db->select()->from(Db_CiRelationType::TABLE_NAME);

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::NAME);
        }

        return $select;
    }

    public function getColorByRelationType($relationTypeId)
    {
        $select = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME, array(
                Db_CiRelationType::COLOR,
            ))
            ->where(Db_CiRelationType::ID . ' = ?', $relationTypeId);
        $result = $this->db->fetchRow($select);
        return $result[Db_CiRelationType::COLOR];
    }

    public function getRelationsForPaginationWithFilter($filter, $orderBy = null, $direction = null)
    {
        $select = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME)
            ->where(Db_CiRelationType::NAME . ' LIKE "%' . $filter . '%"')
            ->orWhere(Db_CiRelationType::DESCRIPTION . ' LIKE "%' . $filter . '%"')
            ->orWhere(Db_CiRelationType::DESCRIPTION_OPTIONAL . ' LIKE "%' . $filter . '%"');

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        }

        return $select;
    }

    public function getRelations()
    {
        $select = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME)
            ->where(Db_CiRelationType::IS_ACTIVE . ' = ?', '1')
            ->order(Db_CiRelationType::NAME);

        return $this->db->fetchAll($select);
    }

    public function getRelationTypesByCiId($ciId)
    {
        $select = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME)
            ->join(Db_CiTypeRelationType::TABLE_NAME,
                Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' = ' . Db_CiRelationType::TABLE_NAME . '.' .
                Db_CiRelationType::ID, array(
                    Db_CiTypeRelationType::MAX_AMOUNT,
                ))
            ->join(Db_CiType::TABLE_NAME,
                Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->join(Db_Ci::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ?', $ciId)
            ->where(Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::IS_ACTIVE . ' = ?', '1')
            ->order(Db_CiRelationType::NAME);

        return $this->db->fetchAll($select);
    }

    public function getRelationTypesByCiTypeId($ciTypeId)
    {
        $ciTypeIds = implode(',', $this->getRelationTypesRecursiveByCiTypeId($ciTypeId));
        $select    = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME)
            ->join(Db_CiTypeRelationType::TABLE_NAME,
                Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' = ' . Db_CiRelationType::TABLE_NAME . '.' .
                Db_CiRelationType::ID, array(
                    Db_CiTypeRelationType::MAX_AMOUNT,
                ))
            ->join(Db_CiType::TABLE_NAME,
                Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->where(Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_TYPE_ID . ' IN (' . $ciTypeIds . ')')
            ->where(Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::IS_ACTIVE . ' = ?', '1')
            ->order(Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::NAME);

        return $this->db->fetchAll($select);
    }

    public function getCiTypeRelationTypeByCiTypeId($ciTypeID, $ciTypeRelationId)
    {
        $select = $this->db->select()
            ->from(Db_CiTypeRelationType::TABLE_NAME)
            ->where(Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_TYPE_ID . ' =?', $ciTypeID)
            ->where(Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' =?', $ciTypeRelationId);

        return $this->db->fetchAll($select);
    }

    public function getRelationTypesRecursiveByCiTypeId($ciTypeId)
    {
        $select  = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, 'parent_ci_type_id')
            ->where('id = ?', $ciTypeId)
            ->where('parent_ci_type_id is not null AND parent_ci_type_id != 0');
        $parents = $this->db->fetchAll($select);

        $ciTypeIds = array(
            $ciTypeId,
        );
        if ($parents && count($parents)) {
            foreach ($parents as $parent) {
                $tmp = $this->getRelationTypesRecursiveByCiTypeId($parent['parent_ci_type_id']);
                foreach ($tmp as $id)
                    array_push($ciTypeIds, $id);
            }
        }

        return $ciTypeIds;
    }

    public function getDirections($orderBy = null, $direction = null)
    {
        $select = $this->db->select()->from(Db_CiRelationDirection::TABLE_NAME);
        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;
            $select->order($orderBy);
        } else $select->order(Db_CiRelationDirection::DESCRIPTION, ' ASC');

        return $this->db->fetchAll($select);
    }

    public function getDirectionByName($name)
    {
        $select = $this->db->select()
            ->from(Db_CiRelationDirection::TABLE_NAME)
            ->where(Db_CiRelationDirection::NAME . ' = ?', $name);

        return $this->db->fetchRow($select);
    }

    public function insertRelationType($relationType)
    {
        $table = new Db_CiRelationType();
        return $table->insert($relationType);
    }

    public function updateRelation($relationTypeId, $relation)
    {
        $table = new Db_CiRelationType();
        $where = $this->db->quoteInto(Db_CiRelationType::ID . ' = ?', $relationTypeId);
        return $table->update($relation, $where);
    }

    public function getRelation($ciRelationTypeId)
    {
        $select = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME)
            ->where(Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ?', $ciRelationTypeId);

        return $this->db->fetchRow($select);
    }

    public function getCiTypesByRelationTypeId($relationTypeId)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME)
            ->join(Db_CiTypeRelationType::TABLE_NAME,
                Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->where(Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' = ?', $relationTypeId);

        return $this->db->fetchAll($select);
    }

    public function getRelationsByCiTypeId($typeId)
    {
        $select = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME)
            ->join(Db_CiTypeRelationType::TABLE_NAME,
                Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' = ' . Db_CiRelationType::TABLE_NAME . '.' .
                Db_CiRelationType::ID, array())
            ->where(Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_TYPE_ID . ' = ?', $typeId);

        return $this->db->fetchAll($select);
    }

    public function deleteRelationsByCiTypeId($typeId)
    {
        $table = new Db_CiTypeRelationType();

        $where = $this->db->quoteInto(Db_CiTypeRelationType::CI_TYPE_ID . ' = ?', $typeId);
        return $table->delete($where);
    }

    public function saveCiTypeRelation($typeId, $relationTypeId, $maxAmount = null, $ordernumber = null)
    {
        $table = new Db_CiTypeRelationType();

        $data[Db_CiTypeRelationType::CI_TYPE_ID]          = $typeId;
        $data[Db_CiTypeRelationType::CI_RELATION_TYPE_ID] = $relationTypeId;

        if ($maxAmount)
            $data[Db_CiTypeRelationType::MAX_AMOUNT] = $maxAmount;

        if ($ordernumber)
            $data[Db_CiTypeRelationType::ORDER_NUMBER] = $ordernumber;

        return $table->insert($data);
    }

    public function deleteCiTypeRelation($typeId, $relationTypeId)
    {
        $table = new Db_CiTypeRelationType();

        $where = $this->db->quoteInto(Db_CiTypeRelationType::CI_TYPE_ID . ' = ' . $typeId . ' AND ' . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' = ?',
            $relationTypeId);
        return $table->delete($where);
    }

    public function getCiRelationsByCiId($ciId, $relationTypeId = null, $projectlist = null)
    {
        $select = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME,
                array(
                    'relationId'             => Db_CiRelation::ID,
                    Db_CiRelation::CI_ID_1,
                    Db_CiRelation::CI_RELATION_TYPE_ID,
                    Db_CiRelation::CI_ID_2,
                    Db_CiRelation::ATTRIBUTE_ID,
                    Db_CiRelation::LINKED_ATTRIBUTE_ID,
                    Db_CiRelation::WEIGHTING,
                    Db_CiRelation::DIRECTION => new Zend_Db_Expr('if((' . Db_CiRelation::DIRECTION . ' IS NULL OR ' . Db_CiRelation::DIRECTION . ' = 0), 4, ' . Db_CiRelation::DIRECTION . ')'),
                    'ciRelationNote'         => Db_CiRelation::NOTE,
                ))
            ->join(Db_CiRelationType::TABLE_NAME,
                Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID)
            ->join(Db_Ci::TABLE_NAME . ' AS ci1', 'ci1.' . Db_Ci::ID . ' = ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1, array())
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' = ci1.' . Db_Ci::CI_TYPE_ID,
                array(
                    'citypeName1'        => Db_CiType::NAME,
                    'citypeId1'          => Db_CiType::ID,
                    'citypeDescription1' => Db_CiType::DESCRIPTION,
                    'citypeNote1'        => Db_CiType::NOTE,
                ))
            ->join(Db_Ci::TABLE_NAME . ' AS ci2', 'ci2.' . Db_Ci::ID . ' = ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2, array())
            ->join(Db_CiType::TABLE_NAME . ' AS ci_type_2', 'ci_type_2.' . Db_CiType::ID . ' = ci2.' . Db_Ci::CI_TYPE_ID,
                array(
                    'citypeName2'        => Db_CiType::NAME,
                    'citypeId2'          => Db_CiType::ID,
                    'citypeDescription2' => Db_CiType::DESCRIPTION,
                    'citypeNote2'        => Db_CiType::NOTE,
                ));


        if ($relationTypeId !== null) {
            $select->where(Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID . ' = ' . $relationTypeId);
        }

        if ($projectlist) {
            $select->where(
                '(' .
                Db_CiRelation::CI_ID_1 . ' = ' . $ciId . ' AND ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2 .
                ' IN (SELECT ci_id from ci_project where project_id in (' . $projectlist . '))' .
                ') OR (' .

                Db_CiRelation::CI_ID_2 . ' = ' . $ciId . ' AND ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 .
                ' IN (SELECT ci_id from ci_project where project_id in (' . $projectlist . '))' .
                ')'
            );
        }

        // order by foreign ci_id DESC
        $select->order(new Zend_Db_Expr('if(' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 . ' = ' . $ciId . ', ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2 . ', ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 . ') DESC'));

        return $this->db->fetchAll($select);
    }

    public function countCiRelations($ciId)
    {
        $select = "SELECT COUNT(*) AS cnt FROM " . Db_CiRelation::TABLE_NAME . " 
		WHERE " . Db_CiRelation::CI_ID_1 . " = '" . $ciId . "' or " . Db_CiRelation::CI_ID_2 . " = '" . $ciId .
            "'";

        return $this->db->fetchRow($select);
    }

    /**
     *
     * @param unknown $ciID
     *            of main CI
     * @param unknown $projectList
     *            needed for permissions
     * @param int     $count
     *            the number of Cis beyond which the user is asked for
     *            conformation
     * @param boolean $confirm
     *            weather or not the user is asked for conformation
     *
     * @return array array( 'ci' => $ciArray,'relation' =>$relationArray)
     */
    public function getCiRelationForGraph($ciID, $projectList, $count, $confirm)
    {
        // gets all cis in relation with ci
        $ciSelect = $this->db->select()
            ->from(Db_Ci::TABLE_NAME,
                array(
                    Db_Ci::TABLE_NAME . '.' . Db_Ci::ID,
                    Db_Ci::TABLE_NAME . '.' . Db_Ci::ICON,
                    'ci_type'      => Db_CiType::TABLE_NAME . '.' . Db_CiType::DESCRIPTION,
                    'default_text' => Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT,
                ))
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . '=' . Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID, array())
            ->join(Db_CiRelation::TABLE_NAME,
                Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 . '=' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' or ' . Db_CiRelation::TABLE_NAME . '.' .
                Db_CiRelation::CI_ID_2 . '=' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID, array())
            ->join(Db_CiRelationType::TABLE_NAME,
                Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . '=' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID,
                array())
            ->join(Db_CiProject::TABLE_NAME, Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID . '=' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID, array())
            ->joinLeft(Db_CiAttribute::TABLE_NAME,
                Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . '=' . Db_CiType::TABLE_NAME . '.' . Db_CiType::DEFAULT_ATTRIBUTE_ID . ' and ' .
                Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . '=' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID, array())
            ->where(Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 . ' =? or ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2 . ' =?',
                $ciID)
            ->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' IN(' . $projectList . ')')
            ->group(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);

        $ciArray = $this->db->fetchAll($ciSelect);

        //gets a single ci, if no relations exists for ci
        if (empty($ciArray)) {
            $ciSelect = $this->db->select()
                ->from(Db_Ci::TABLE_NAME,
                    array(
                        Db_Ci::TABLE_NAME . '.' . Db_Ci::ID,
                        Db_Ci::TABLE_NAME . '.' . Db_Ci::ICON,
                        'ci_type'      => Db_CiType::TABLE_NAME . '.' . Db_CiType::DESCRIPTION,
                        'default_text' => Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT,
                    ))
                ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . '=' . Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID, array())
                ->join(Db_CiProject::TABLE_NAME, Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID . '=' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID, array())
                ->joinLeft(Db_CiAttribute::TABLE_NAME,
                    Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . '=' . Db_CiType::TABLE_NAME . '.' . Db_CiType::DEFAULT_ATTRIBUTE_ID . ' and ' .
                    Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . '=' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID, array())
                ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' =?', $ciID)
                ->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' IN(' . $projectList . ')');

            $ciArray = $this->db->fetchAll($ciSelect);

        }


        // if the user did not confirm yet and there are more than the given
        // amount of Cis
        if ((!$confirm) && (count($ciArray) >= $count)) {
            return true;
        } elseif (count($ciArray) > 500) { // if too much Ci for server processing
            return false;
        }

        // generates every possible relation between Cis for relations
        // between Cis other than $CiID
        $possibleRelations = array();
        foreach ($ciArray as $line1) {
            foreach ($ciArray as $line2) {
                array_push($possibleRelations, '(' . $line1['id'] . ',' . $line2['id'] . ')');
            }
        }

        // get all relations for ci with possible relations
        $relationSelect = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME,
                array(
                    Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::DIRECTION,
                    Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1,
                    Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2,
                    'ci_relation_type1' => Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::DESCRIPTION,
                    'ci_relation_type2' => Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::DESCRIPTION_OPTIONAL,
                ))
            ->join(Db_CiRelationType::TABLE_NAME,
                Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . '=' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID,
                array())
            ->where(
                '(' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 . ',' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2 . ') in (' .
                implode(',', $possibleRelations) . ')');

        $relationArray = $this->db->fetchAll($relationSelect);

        return array(
            'ci'       => $ciArray,
            'relation' => $relationArray,
        );
    }

    public function getCiRelationsByCiIdExcludingInternalGroupById($ciId, $projectList, $ciTypeID)
    {
        $select1 = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME,
                array(
                    'relationId' => Db_CiRelation::ID,
                    'base'       => new Zend_Db_Expr('1'),
                    Db_CiRelation::CI_ID_1,
                    Db_CiRelation::CI_ID_2,
                    Db_CiRelation::ATTRIBUTE_ID,
                    Db_CiRelation::LINKED_ATTRIBUTE_ID,
                    Db_CiRelation::CI_RELATION_TYPE_ID,
                ))
            ->join(Db_CiRelationType::TABLE_NAME,
                Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID)
            ->join(Db_CiProject::TABLE_NAME,
                Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2 . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array())
            ->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' IN(' . $projectList . ')')
            ->where(Db_CiRelation::CI_ID_1 . ' = ?', $ciId);

        $select2 = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME,
                array(
                    'relationId' => Db_CiRelation::ID,
                    'base'       => new Zend_Db_Expr('0'),
                    Db_CiRelation::CI_ID_1,
                    Db_CiRelation::CI_ID_2,
                    Db_CiRelation::ATTRIBUTE_ID,
                    Db_CiRelation::LINKED_ATTRIBUTE_ID,
                    Db_CiRelation::CI_RELATION_TYPE_ID,
                ))
            ->join(Db_CiRelationType::TABLE_NAME,
                Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID)
            ->join(Db_CiProject::TABLE_NAME,
                Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array())
            ->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' IN(' . $projectList . ')')
            ->where(Db_CiRelation::CI_ID_2 . ' =?', $ciId);

        $select = $this->db->select()->union(array(
            $select1,
            $select2,
        ));

        $data = array();
        $data = $this->db->fetchAll($select);

        return $data;
    }

    public function addCiRelation($ciId, $linkedCiId, $relationTypeId, $attributeId = null, $linkedAttributeId = null)
    {
        $table = new Db_CiRelation();

        $data                                     = array();
        $data[Db_CiRelation::CI_ID_1]             = $ciId;
        $data[Db_CiRelation::CI_ID_2]             = $linkedCiId;
        $data[Db_CiRelation::CI_RELATION_TYPE_ID] = $relationTypeId;

        if ($attributeId)
            $data[Db_CiRelation::ATTRIBUTE_ID] = $attributeId;

        if ($linkedAttributeId)
            $data[Db_CiRelation::LINKED_ATTRIBUTE_ID] = $linkedAttributeId;

        return $table->insert($data);
    }

    public function addCiRelationArray($data)
    {
        $table = new Db_CiRelation();
        return $table->insert($data);
    }

    public function deleteCiRelation($ciRelationId)
    {
        $table = new Db_CiRelation();
        $where = $this->db->quoteInto(Db_CiRelation::ID . ' =?', $ciRelationId);
        $table->delete($where);
    }

    public function updateCiRelationCiAttributeDelete($ciId, $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME, array(
                Db_CiRelation::ID,
            ))
            ->where('(' . Db_CiRelation::CI_ID_1 . ' =? OR ' . Db_CiRelation::CI_ID_2 . ' = "' . $ciId . '")', $ciId)
            ->where('(' . Db_CiRelation::ATTRIBUTE_ID . ' =? OR ' . Db_CiRelation::LINKED_ATTRIBUTE_ID . ' = "' . $attributeId . '")', $attributeId);
        $res    = $this->db->fetchAll($select);

        if (!$res)
            return;

        $list = array();
        foreach ($res as $ciRelList) {
            array_push($list, $ciRelList[Db_CiRelation::ID]);
        }

        $data                                     = array();
        $data[Db_CiRelation::ATTRIBUTE_ID]        = null;
        $data[Db_CiRelation::LINKED_ATTRIBUTE_ID] = null;

        $table = new Db_CiRelation();
        $where = $this->db->quoteInto(Db_CiRelation::ID . ' IN(?)', $list);
        return $table->update($data, $where);
    }

    public function getAmountOfRelationsForCi($ciId, $relationTypeId)
    {
        $select = $this->db->select()
            ->from(Db_CiTypeRelationType::TABLE_NAME,
                array(
                    'cnt' => 'COUNT(*)',
                    Db_CiTypeRelationType::MAX_AMOUNT,
                ))
            ->join(Db_Ci::TABLE_NAME,
                Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' = ' . Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_TYPE_ID, array())
            ->join(Db_CiRelation::TABLE_NAME,
                Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID . ' = ' . Db_CiTypeRelationType::TABLE_NAME . '.' .
                Db_CiTypeRelationType::CI_RELATION_TYPE_ID, array())
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' =?', $ciId)
            ->where(Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' =?', $relationTypeId)
            ->where(
                Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 . ' = ' . $ciId . ' OR ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2 .
                ' =?', $ciId)
            ->group(Db_CiTypeRelationType::TABLE_NAME . '.' . Db_CiTypeRelationType::MAX_AMOUNT);

        return $this->db->fetchRow($select);
    }

    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_CiRelationType::NAME . ' LIKE ?', $value);

        if($id > 0) {
            $select->where(Db_CiRelationType::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }

    public function getCiRelationById($ciRelationId)
    {
        $select = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME)
            ->where(Db_CiRelation::ID . ' =?', $ciRelationId);

        return $this->db->fetchRow($select);
    }

    public function deactivateCiRelationType($ciRelationType)
    {
        $select = "UPDATE " . Db_CiRelationType::TABLE_NAME . " SET " . Db_CiRelationType::IS_ACTIVE . " = '0' 
		WHERE " . Db_CiRelationType::ID . " = '" . $ciRelationType . "'";
        $this->db->query($select);
    }

    public function activateCiRelationType($ciRelationType)
    {
        $select = "UPDATE " . Db_CiRelationType::TABLE_NAME . " SET " . Db_CiRelationType::IS_ACTIVE . " = '1' 
		WHERE " . Db_CiRelationType::ID . " = '" . $ciRelationType . "'";
        $this->db->query($select);
    }

    public function getCiIdsByCiRelationTypeId($ciRelationId)
    {
        $select = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME)
            ->where(Db_CiRelation::CI_RELATION_TYPE_ID . ' = ?', $ciRelationId);

        return $this->db->fetchAll($select);
    }

    public function deleteCiTypeRelationTypeByRelationTypeId($relationTypeId)
    {
        $table = new Db_CiTypeRelationType();
        $where = $this->db->quoteInto(Db_CiTypeRelationType::CI_RELATION_TYPE_ID . ' = ?', $relationTypeId);
        return $table->delete($where);
    }

    public function deleteCiRelationTypeByRelationTypeId($relationTypeId)
    {
        $table = new Db_CiRelationType();
        $where = $this->db->quoteInto(Db_CiRelationType::ID . ' = ?', $relationTypeId);
        return $table->delete($where);
    }

    // depcreated? valid_to doesn't exist...
    public function checkCiRelationUnique($ciId1, $ciId2)
    {
        $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_CiRelation::TABLE_NAME . "
				   WHERE " . Db_CiRelation::CI_ID_1 . " = '" . $ciId1 . "'
				   AND " . Db_CiRelation::CI_ID_2 . " = '" . $ciId2 . "'
				   AND " . Db_CiRelation::V_VALID_TO . " IS NULL";
        return $this->db->fetchRow($select);
    }

    /*
     * Checks if Relation exists in ci_relation-table be sure to have at least
     * to 2 parameters to get a correct result @param int $ciId1 the id of the
     * ci to check @param int $ci_relation_type_id optional the id of the
     * ci_relation_type @param int $ciId2 optional the other id of the ci to
     * check relation @return booelean true if relation exists, false if
     * relation not exists
     */
    public function checkIfCiRelationExists($ciId1, $ci_relation_type_id = null, $ciId2 = null)
    {
        if (empty($ciId2)) {
            $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_CiRelation::TABLE_NAME . "
				   WHERE (" . Db_CiRelation::CI_ID_1 . " = '" . $ciId1 . "'
				   ) OR (" . Db_CiRelation::CI_ID_2 . " = '" . $ciId1 . "'
				   )";
        } else {
            $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_CiRelation::TABLE_NAME . "
				   WHERE (" . Db_CiRelation::CI_ID_1 . " = '" . $ciId1 . "'
					   AND " . Db_CiRelation::CI_ID_2 . " = '" . $ciId2 . "'	
				   ) OR (" . Db_CiRelation::CI_ID_1 . " = '" . $ciId2 . "'
					   AND " . Db_CiRelation::CI_ID_2 . " = '" . $ciId1 . "'
				   )";
        }
        if (!empty($ci_relation_type_id)) {
            $select .= " AND ci_relation_type_id = " . $ci_relation_type_id;
        }

        $result = $this->db->fetchRow($select);

        if ($result['cnt'] > 0) {
            return true;
        }
        return false;
    }

    public function getRelationsForCi($ciId)
    {
        $select      = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME)
            ->where(Db_CiRelation::CI_ID_1 . ' =?', $ciId);
        $ci_1_result = $this->db->fetchAll($select);

        $select      = $this->db->select()
            ->from(Db_CiRelation::TABLE_NAME)
            ->where(Db_CiRelation::CI_ID_2 . ' =?', $ciId);
        $ci_2_result = $this->db->fetchAll($select);

        $result = array_merge($ci_1_result, $ci_2_result);
        return $result;
    }

    public function getRelationTypeById($relationId)
    {
        $select = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME)
            ->where(Db_CiRelationType::ID . ' =?', $relationId);
        return $this->db->fetchRow($select);
    }
}