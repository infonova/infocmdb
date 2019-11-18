<?php

class Dao_Ci extends Dao_Abstract
{

    public function updateCiIcon($ciId, $newFilename)
    {
        $table = new Db_Ci();
        $data  = array(
            Db_Ci::ICON => $newFilename,
        );

        $where = $this->db->quoteInto(Db_Ci::ID . ' =?', $ciId);
        $table->update($data, $where);
    }

    public function getCiListForCiIndex($typeId, &$projectIdList = null, $userId = null, $orderBy = null, $direction = null, $limit_from = null, $itemsCountPerPage = null, $permissionList = null, $isNumeric = false, $newRoles = null, $ciRelationTypId = null, $sourceCiid = null)
    {
        if(is_string($projectIdList)) {
            $projectIdList = explode(", ", $projectIdList);
        }
        if(is_string($permissionList)) {
            $permissionList = explode(", ", $permissionList);
        }

        $select = $this->db->select()
            ->distinct()
            ->from(Db_Ci::TABLE_NAME, array(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID))
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' = ?', $typeId);

        if (!is_null($projectIdList)) {
            $select->join(Db_CiProject::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array());
            $select->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' IN(?)', $projectIdList);
            if ($permissionList) {
                $select->orWhere(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' IN(?)', $permissionList);
            }
        }

        if ($userId) {
            $select->joinLeft(Db_CiHighlight::TABLE_NAME, Db_CiHighlight::TABLE_NAME . '.' . Db_CiHighlight::CI_ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' AND ' . Db_CiHighlight::TABLE_NAME . '.' . Db_CiHighlight::USER_ID . ' = "' . $userId . '"', array(Db_CiHighlight::COLOR));
        }

        if (!is_null($ciRelationTypId)) {

            if (!is_null($sourceCiid)) {
                $sourceCiid = (int) $sourceCiid;
                $ciRelationTable = $this->db->select()
                    ->from(Db_CiRelation::TABLE_NAME)
                    ->where(
                        new Zend_Db_Expr(
                            Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 . ' = ' . $sourceCiid .
                            ' OR ' .
                            Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2 . ' = ' . $sourceCiid
                        )
                    )
                    ->where(Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID . ' = ?',  $ciRelationTypId);
            } else {
                $ciRelationTable = Db_CiRelation::TABLE_NAME;
            }

            $select->join(
                array(Db_CiRelation::TABLE_NAME => $ciRelationTable),
                new Zend_Db_Expr(
                    '(' .
                    Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_1 . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID .
                    ' OR ' .
                    Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_ID_2 . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID .
                    ')'
                ),
                array(
                    'ciRelationId'            => Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::ID,
                    'ciRelationForeignColumn' => new Zend_Db_Expr('if(ci.id = ci_relation.ci_id_1, 1, 2)'),
                    'ciRelationDirection'     => new Zend_Db_Expr('if((' . Db_CiRelation::DIRECTION . ' IS NULL OR ' . Db_CiRelation::DIRECTION . ' = 0), 4, ' . Db_CiRelation::DIRECTION . ')'),
                    'ciRelationNote'          => Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::NOTE,
                    'ciRelationValidFrom'     => Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::VALID_FROM,
                )
            );

            $select->join(Db_CiRelationType::TABLE_NAME,
                Db_CiRelationType::TABLE_NAME . '.' . Db_CiRelationType::ID . ' = ' . Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID,
                array(
                    'ciRelationTypeDescription'         => Db_CiRelationType::DESCRIPTION,
                    'ciRelationTypeDescriptionOptional' => Db_CiRelationType::DESCRIPTION_OPTIONAL,
                )
            );

            $select->where(Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::CI_RELATION_TYPE_ID . ' = ? ', $ciRelationTypId);

        }

        if ($newRoles) {
            //TODO to slow
            $select->where("EXISTS(select " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ID . " FROM " . Db_AttributeRole::TABLE_NAME . " INNER JOIN " . Db_CiAttribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . " = " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ATTRIBUTE_ID . " WHERE " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ROLE_ID . " IN(?) and " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::PERMISSION_READ . " = '1' and " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . ")", $newRoles);

        }

        if ($orderBy) {

            $select->joinLeft(Db_CiAttribute::TABLE_NAME . ' AS v_ci_attribute_order', 'v_ci_attribute_order.' . Db_CiAttribute::CI_ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' AND v_ci_attribute_order.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . $orderBy, array());

            if (!$direction)
                $direction = 'DESC';

            if ($isNumeric) {

                $select->order('0.0+(v_ci_attribute_order.' . Db_CiAttribute::VALUE_TEXT . ') ' . $direction);
                $select->order('0.0+(v_ci_attribute_order.' . Db_CiAttribute::VALUE_DEFAULT . ') ' . $direction);

            } else {
                $select->order('v_ci_attribute_order.' . Db_CiAttribute::VALUE_TEXT . ' ' . $direction);
                $select->order('v_ci_attribute_order.' . Db_CiAttribute::VALUE_DATE . ' ' . $direction);
                $select->order('v_ci_attribute_order.' . Db_CiAttribute::VALUE_DEFAULT . ' ' . $direction);
                $select->order('v_ci_attribute_order.' . Db_CiAttribute::VALUE_CI . ' ' . $direction);
            }

        } else {

            if (!isset($direction))
                $direction = 'DESC';
            $select->order(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' ' . $direction);
        }

        if ($itemsCountPerPage && $limit_from)
            $select->limit($itemsCountPerPage, $limit_from);

        return $this->db->fetchAll($select);
    }

    public function getDefaultAttribute($ciId)
    {
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, array())
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . "." . Db_CiType::ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID, array())
            ->join(Db_CiAttribute::TABLE_NAME, Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID, array(Db_CiAttribute::VALUE_TEXT, Db_CiAttribute::VALUE_DATE, Db_CiAttribute::VALUE_CI, Db_CiAttribute::VALUE_DEFAULT))
            ->where(Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " = ?", $ciId)
            ->where(Db_CiType::DEFAULT_ATTRIBUTE_ID . " = " . Db_CiAttribute::ATTRIBUTE_ID);

        return $this->db->fetchRow($select);
    }

    public function getDefaultAttributeHistory($ciId)
    {
        $select = $this->db->select()
            ->from(Db_History_CiAttribute::TABLE_NAME, array(Db_History_CiAttribute::VALUE_TEXT, Db_History_CiAttribute::VALUE_DATE, Db_History_CiAttribute::VALUE_CI, Db_History_CiAttribute::VALUE_DEFAULT))
            ->join(Db_History_Ci::TABLE_NAME, Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::CI_ID . ' = ' . Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::ID, array())
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::DEFAULT_ATTRIBUTE_ID . ' = ' . Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::ATTRIBUTE_ID, array())
            ->where(Db_History_CiAttribute::CI_ID . ' = ?', $ciId);

        return $this->db->fetchRow($select);
    }

    public function getCiListByProjectId($projectId)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_Ci::TABLE_NAME, array(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID))
            ->join(Db_CiProject::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array())
            ->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' IN (?)', $projectId);
        return $this->db->fetchAll($select);
    }

    public function getCiListByCiTypeId($typeId, &$projectId = null, $userId = null, $orderBy = null, $direction = null, $limit_from = null, $itemsCountPerPage = null)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_Ci::TABLE_NAME, array(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID))
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' = ?', $typeId);

        if (!is_null($projectId)) {
            $select->join(Db_CiProject::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array());
            $select->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' IN (?)', $projectId);
        }

        if ($userId) {
            $select->joinLeft(Db_CiHighlight::TABLE_NAME, Db_CiHighlight::TABLE_NAME . '.' . Db_CiHighlight::CI_ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' AND ' . Db_CiHighlight::TABLE_NAME . '.' . Db_CiHighlight::USER_ID . ' = "' . $userId . '"', array(Db_CiHighlight::COLOR));
        }

        if ($orderBy) {
            $select->joinLeft(Db_CiAttribute::TABLE_NAME, Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' AND ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . $orderBy, array());

            if (!$direction)
                $direction = 'DESC';

            $select->order(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT . ' ' . $direction);
            $select->order(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DATE . ' ' . $direction);
            $select->order(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DEFAULT . ' ' . $direction);
        } else {
            $select->order(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' DESC');
        }

        if ($itemsCountPerPage && $limit_from)
            $select->limit($itemsCountPerPage, $limit_from);

        return $this->db->fetchAll($select);
    }

    public function getCiConfigurationStatementByCiTypeId(array $attributeList, $ciListString, $orderBy = null, $direction = null, $projectIdListString = null)
    {
        $ciList = "";
        if(is_string($ciListString)) {
            $ciList = explode(", ", $ciListString);
        }


        $comb = array();
        array_push($comb, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);
        array_push($comb, 'GROUP_CONCAT(DISTINCT '.Db_CiProject::TABLE_NAME . '.' . Db_CiProject::ID . ') as ci_project_ids');

        foreach ($attributeList as $attr) {
            $comb[$attr[Db_Attribute::NAME]]        = new Zend_Db_Expr('MAX( CASE WHEN ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . $attr[Db_Attribute::ID] . ' THEN '
                . 'CONCAT_WS("", cast(' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT . ' as char), '
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DATE . ', cast('
                . Db_AttributeDefaultValues::TABLE_NAME . '.' . Db_AttributeDefaultValues::VALUE . ' as char), cast('
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_CI . ' as char))' .
                ' ELSE \'\' END)');
            $comb[$attr[Db_Attribute::NAME] . 'ID'] = new Zend_Db_Expr('MAX( CASE WHEN ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . $attr[Db_Attribute::ID] . ' THEN '
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ID . ' ELSE \'\' END)');

        }

        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, $comb)
            ->joinLeft(Db_CiAttribute::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID, array())
            ->joinLeft(Db_AttributeDefaultValues::TABLE_NAME, Db_AttributeDefaultValues::TABLE_NAME . '.' . Db_AttributeDefaultValues::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DEFAULT, array())
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' IN(?)', $ciList)
            ->group(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);;

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' DESC');
        }

        $select->join(Db_CiProject::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array());

        if (!is_null($projectIdListString) && is_string($projectIdListString)) {
            $projectList = preg_split("/\s*,\s*/", $projectIdListString);
            $select->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' IN(?)', $projectList);
        }

        return $this->db->fetchAll($select);

    }

    public function getCiConfigurationStatementByCiTypeIdHistory($attributeList, $ciListString, $orderBy = null, $direction = null, $projectIdListString = null)
    {
        $ciList = "";
        if(is_string($ciListString)) {
            $ciList = explode(", ", $ciListString);
        }

        $projectList = "";
        if(is_string($projectIdListString)) {
            $projectList = explode(", ", $projectIdListString);
        }

        $comb = array();
        array_push($comb, Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::ID);

        foreach ($attributeList as $attr) {
            array_push($comb, 'MAX( CASE WHEN ' . Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::ATTRIBUTE_ID . ' = ' . $attr[Db_Attribute::ID] . ' THEN '
                . 'CONCAT_WS("", cast(' . Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::VALUE_TEXT . ' as char), '
                . Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::VALUE_DATE . ', cast('
                . Db_AttributeDefaultValues::TABLE_NAME . '.' . Db_AttributeDefaultValues::VALUE . ' as char), cast('
                . Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::VALUE_CI . ' as char))' .
                ' ELSE \'\' END) AS ' . $attr[Db_Attribute::NAME] . '');
            array_push($comb, 'MAX( CASE WHEN ' . Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::ATTRIBUTE_ID . ' = ' . $attr[Db_Attribute::ID] . ' THEN '
                . Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::ID . ' ELSE \'\' END) AS ' . $attr[Db_Attribute::NAME] . 'ID');

        }

        $select = $this->db->select()
            ->from(Db_History_Ci::TABLE_NAME, $comb)
            ->joinLeft(Db_History_CiAttribute::TABLE_NAME, Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::ID . ' = ' . Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::CI_ID, array())
            ->joinLeft(Db_AttributeDefaultValues::TABLE_NAME, Db_AttributeDefaultValues::TABLE_NAME . '.' . Db_AttributeDefaultValues::ID . ' = ' . Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::VALUE_DEFAULT, array())
            ->where(Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::ID . ' IN(?)', $ciList)
            ->group(Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::ID);;

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::ID . ' DESC');
        }

        if (!is_null($projectIdListString)) {
            $select->join(Db_CiProject::TABLE_NAME, Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array());
            $select->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' IN(?)', $projectList);

        }

        return $this->db->fetchAll($select);
    }

    public function getCiConfigurationStatementForRelation($ciId, $attributeList, &$projectId = null)
    {

        $comb = array();
        array_push($comb, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);
        array_push($comb, Db_CiType::TABLE_NAME . '.' . Db_CiType::DESCRIPTION . ' AS ciType');

        foreach ($attributeList as $attr) {
            $comb[$attr[Db_Attribute::NAME]] = new Zend_Db_Expr('MAX( CASE WHEN ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . $attr[Db_Attribute::ID] . ' THEN '
                . 'CONCAT_WS("", cast(' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT . ' as char), '
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DATE . ', cast('
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DEFAULT . ' as char), cast( '
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_CI . ' as char))' .
                ' ELSE \'\' END)');

        }

        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, $comb)
            ->join(Db_CiAttribute::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID, array())
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID, array())
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' = ?', $ciId)
            ->group(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);
        if (!is_null($projectId)) {
            $select->join(Db_CiProject::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array());
            $select->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' = ?', $projectId);
        }

        return $this->db->fetchRow($select);
    }

    public function getCiConfigurationStatementByCiId($ciId, $attributeList)
    {
        $attributesToUse = '';

        foreach ($attributeList as $attribute) {
            $attributesToUse .= (int)$attribute[Db_Attribute::ID] . ', ';
        }
        $attributesToUse = $attributesToUse . '0';

        $comb = array();
        array_push($comb, Db_CiAttribute::VALUE_TEXT);
        array_push($comb, Db_CiAttribute::VALUE_DATE);
        array_push($comb, Db_CiAttribute::VALUE_DEFAULT);
        array_push($comb, Db_CiAttribute::VALUE_CI);
        array_push($comb, Db_CiAttribute::NOTE);
        $comb['ciAttributeId'] = Db_CiAttribute::ID;

        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME, $comb)
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array(Db_Attribute::NAME, Db_Attribute::ID, Db_Attribute::ATTRIBUTE_TYPE_ID))
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('type' => Db_AttributeType::NAME))
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' IN (' . $attributesToUse . ')')
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' = ?', $ciId);

        return $this->db->fetchAll($select);
    }

    public function getCi($id)
    {
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME)
            ->where(Db_Ci::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }

    public function getCiTypeDescriptionForCi($id)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::DESCRIPTION))
            ->join(Db_Ci::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' =?', $id);

        $res = $this->db->fetchRow($select);
        return $res[Db_CiType::DESCRIPTION];
    }

    public function getCiTypeDescriptionForHistoryCi($id)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::DESCRIPTION))
            ->join(Db_History_Ci::TABLE_NAME, Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID, array())
            ->where(Db_History_Ci::TABLE_NAME . '.' . Db_History_Ci::ID . ' =?', $id);

        $res = $this->db->fetchRow($select);
        return $res[Db_CiType::DESCRIPTION];
    }

    public function procedureDeleteCi($ciId, $userId = 0, $message = null)
    {
        $ciId = (int) $ciId; // prevent sql injection the hacky way :/
        $this->db->query('call delete_ci(' . $ciId . ', ' . $userId . ', "' . $message . '")');
    }

    public function createCi($ciType, $icon = null, $historyId = null)
    {
        $table = new Db_Ci();

        $ci                    = array();
        $ci[Db_Ci::CI_TYPE_ID] = $ciType;

        if ($historyId)
            $ci[Db_Ci::ICON] = $icon;

        if ($historyId)
            $ci[Db_Ci::HISTORY_ID] = $historyId;

        return $table->insert($ci);
    }

    public function updateCiType($ciId, $ciTypeId, $historyId = null)
    {
        $table = new Db_Ci();

        $data                    = array();
        $data[Db_Ci::CI_TYPE_ID] = $ciTypeId;

        if ($historyId)
            $data[Db_Ci::HISTORY_ID] = $historyId;

        $where = $this->db->quoteInto(Db_Ci::ID . ' =?', $ciId);
        return $table->update($data, $where);
    }

    public function deleteSingleCiAttributesById($ciAttributeId, $historyId = null)
    {
        $ciAttributeId = (int) $ciAttributeId; // prevent sql injection the hacky way :/
        $this->db->query('call delete_ci_attribute(' . $ciAttributeId . ', ' . $historyId . ')');
    }

    public function deleteSingleCiRelationById($ciRelationId)
    {
        $table = new Db_CiRelation();
        $where = $this->db->quoteInto(Db_CiRelation::ID . ' =?', $ciRelationId);
        $table->delete($where);
    }

    public function addCiAttributeArray($ciId, $attributeId, $ciAttribute = array(), $isInitial = '0')
    {
        $table = new Db_CiAttribute();

        $ciAttribute[Db_CiAttribute::CI_ID]        = $ciId;
        $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] = $attributeId;
        $ciAttribute[Db_CiAttribute::IS_INITIAL]   = $isInitial;

        return $table->insert($ciAttribute);
    }

    public function getCiAttributeByCiAttributeId($ciAttributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::ID . ' =?', $ciAttributeId);
        return $this->db->fetchRow($select);
    }

    public function checkUnique($value, $attributeId = null, $excludedCiId = null)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME, array('cnt' => 'COUNT(*)'))
            ->where(Db_CiAttribute::VALUE_TEXT . ' LIKE ? OR ' . Db_CiAttribute::VALUE_DATE . ' LIKE ?', $value, $value);

        if (!is_null($attributeId)) {
            $select->where(Db_CiAttribute::ATTRIBUTE_ID . ' = ?', $attributeId);
        }

        if (!is_null($excludedCiId)) {
            $select->where(Db_CiAttribute::CI_ID . ' != ?', $excludedCiId);
        }

        return $this->db->fetchRow($select);
    }

    public function checkUniqueUpdate($value, $ciid, $attributeId = null)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME, array('cnt' => 'COUNT(*)'))
            ->where(Db_CiAttribute::VALUE_TEXT . ' LIKE ? OR ' . Db_CiAttribute::VALUE_DATE . ' LIKE ?', $value, $value)
            ->where('NOT ' . Db_CiAttribute::CI_ID . " = ?", $ciid);

        if ($attributeId)
            $select->where(Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId);

        return $this->db->fetchRow($select);
    }

    public function checkCiAttachAllowed($ciTypeId)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::IS_CI_ATTACH))
            ->where(Db_CiType::ID . ' =?', $ciTypeId);
        return $this->db->fetchRow($select);
    }

    public function getCiCiType($id)
    {
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, array('ci_id' => Db_Ci::ID))
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID, array('citype_id' => Db_CiType::ID, 'citype_name' => Db_CiType::NAME, Db_CiType::DESCRIPTION))
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function getListResultQueryForCiList($query, $orderBy = array("1" => "ASC"), $replace_params = array())
    {

        $sql = "SELECT * FROM (\n" . $query . "\n) result_for_list";
        foreach ($replace_params as $key => $value) {
            $sql = str_replace($key, $value, $sql);
        }

        $orderByString = "";
        foreach ($orderBy as $column => $direction) {
            $orderByString .= $column . " " . $direction . ", ";
        }
        //add ORDER BY syntax at the beginning if there is an ordering
        if (!empty($orderByString)) {
            $sql .= " ORDER BY " . substr($orderByString, 0, -2);
        }

        return $this->db->fetchAll($sql);
    }

    public function updateColor($userId, $ciId, $color, $delete)
    {
        $table = new Db_CiHighlight();
        $where = $this->db->quoteInto(Db_CiHighlight::CI_ID . ' =? AND ' . Db_CiHighlight::USER_ID . " = '$userId'", $ciId);
        $table->delete($where);

        if ($delete) {
            return true;
        }

        $ciHighlight                          = array();
        $ciHighlight[Db_CiHighlight::USER_ID] = $userId;
        $ciHighlight[Db_CiHighlight::CI_ID]   = $ciId;
        $ciHighlight[Db_CiHighlight::COLOR]   = $color;

        return $table->insert($ciHighlight);
    }

    public function getFavouriteCiByGroup($group = 'default', $userId = 0)
    {
        $select = $this->db->select()
            ->from(Db_CiFavourites::TABLE_NAME)
            ->join(Db_Ci::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiFavourites::TABLE_NAME . '.' . Db_CiFavourites::CI_ID, array(Db_Ci::CI_TYPE_ID))
            ->where(Db_CiFavourites::TABLE_NAME . '.' . Db_CiFavourites::GROUP . ' =?', $group)
            ->where(Db_CiFavourites::TABLE_NAME . '.' . Db_CiFavourites::USER_ID . ' =?', $userId)
            ->order(Db_Ci::CI_TYPE_ID . ' DESC');
        return $this->db->fetchAll($select);
    }

    public function getCurrentFavouriteGroups($userId)
    {
        $select = $this->db->select()
            ->from(Db_CiFavourites::TABLE_NAME, array(Db_CiFavourites::GROUP))
            ->where(Db_CiFavourites::TABLE_NAME . '.' . Db_CiFavourites::USER_ID . ' =?', $userId)
            ->group(Db_CiFavourites::TABLE_NAME . '.' . Db_CiFavourites::GROUP);
        return $this->db->fetchAll($select);
    }

    public function addCiToFavourites($ciId, $userId, $group = 'default')
    {
        $this->removeCiFromFavourites($ciId, $userId);

        $table = new Db_CiFavourites();

        $ciHighlight                           = array();
        $ciHighlight[Db_CiFavourites::USER_ID] = $userId;
        $ciHighlight[Db_CiFavourites::CI_ID]   = $ciId;
        $ciHighlight[Db_CiFavourites::GROUP]   = $group;

        return $table->insert($ciHighlight);
    }

    public function removeCiFromFavourites($ciId, $userId)
    {
        $table = new Db_CiFavourites();
        $where = array(
            $this->db->quoteInto(Db_CiFavourites::CI_ID . ' =?', $ciId),
            $this->db->quoteInto(Db_CiFavourites::USER_ID . ' =?', $userId),
        );

        $table->delete($where);
    }

    public function countActiveCi()
    {
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, array('cnt' => 'COUNT(id)'));
        return $this->db->fetchRow($select);
    }

    public function countMaxFiveCiTypes()
    {
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, array('cnt' => 'COUNT(v_ci.id)'))
            ->join(Db_CiType::TABLE_NAME, Db_CiType::TABLE_NAME . '.' . Db_CiType::ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID, array(Db_CiType::DESCRIPTION))
            ->group(Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID)
            ->order('cnt desc')
            ->limit(10);
        return $this->db->fetchAll($select);
    }

    public function getAttachmentCiAttribute($ciId, $filename)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array())
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array())
            ->where(Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' =?', Util_AttributeType_Type_Attachment::ATTRIBUTE_TYPE_ID)
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' =?', $ciId)
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT . ' like ?', $filename);
        return $this->db->fetchRow($select);
    }

    public function getCiPermissionForUser($userId)
    {
        $select = $this->db->select()
            ->from(Db_CiPermission::TABLE_NAME)
            ->where(Db_CiPermission::USER_ID . ' =?', $userId);

        return $this->db->fetchAll($select);
    }

    public function getCiForPointInTime($id, $history_id)
    {
        $selectCurrent = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, array(
                Db_Ci::ID,
                Db_Ci::CI_TYPE_ID,
                Db_Ci::ICON,
                Db_Ci::HISTORY_ID,
                'history_id_delete' => new Zend_Db_Expr('NULL'),
                Db_Ci::VALID_FROM,
                'valid_to'          => new Zend_Db_Expr('NOW()'),
            ))
            ->where(Db_Ci::ID . ' =?', $id)
            ->where(Db_Ci::TABLE_NAME . "." . Db_Ci::HISTORY_ID . "<= ?", $history_id);

        $selectHistory = $this->db->select()
            ->from(Db_History_Ci::TABLE_NAME, array(
                Db_History_Ci::ID,
                Db_History_Ci::CI_TYPE_ID,
                Db_History_Ci::ICON,
                Db_History_Ci::HISTORY_ID,
                Db_History_Ci::HISTORY_ID_DELETE,
                Db_History_Ci::VALID_FROM,
                Db_History_Ci::VALID_TO,
            ))
            ->where(Db_History_Ci::ID . ' =?', $id)
            ->where(Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::HISTORY_ID . "<= ?", $history_id)
            ->where(Db_History_Ci::TABLE_NAME . "." . Db_History_Ci::HISTORY_ID_DELETE . "> ?", $history_id);

        $select = $this->db->select()->union(array($selectCurrent, $selectHistory));

        $row = $this->db->fetchRow($select);

        return $row;
    }

}