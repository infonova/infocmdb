<?php


class Dao_Attribute extends Dao_Abstract
{


    const TEMP_CI_CREATE = 'TempCiCreate';

    const ID                           = 'id';
    const CI_ATTRIBUTE_ID              = 'ciAttributeId';
    const GEN_ID                       = 'genId';
    const NAME                         = 'name';
    const ATTRIBUTE_TYPE_ID            = 'attribute_type_id';
    const UNIQUE_CONSTRAINT            = 'is_unique';
    const ATTRIBUTE_GROUP_ID           = 'attribute_group_id';
    const ORDER_NUMBER                 = 'order_number';
    const NOTE                         = 'note';
    const INPUT_MAXLENGTH              = 'input_maxlength';
    const TEXTAREA_COLS                = 'textarea_cols';
    const TEXTAREA_ROWS                = 'textarea_rows';
    const TEXTOUTPUT_BOLD              = 'is_bold';
    const REGEX                        = 'regex';
    const HINT                         = 'hint';
    const DESCRIPTION                  = 'description';
    const MANDATORY                    = 'is_mandatory';
    const INITIAL                      = 'initial';
    const TYPE                         = 'type';
    const VALUE                        = 'value';
    const ATTRIBUTE_GROUP_DESCRIPTION  = 'attributeGroupDescr';
    const ATTRIBUTE_GROUP_NAME         = 'attributeGroupName';
    const ATTRIBUTE_GROUP_ORDER_NUMBER = 'attributeGroupOrderNumber';
    const PERMISSION_WRITE             = 'permission_write';
    const PERMISSION_READ              = 'permission_read';
    const COLUMN                       = 'column';
    const AUTOCOMPLETE                 = 'is_autocomplete';
    const MULTISELECT                  = 'is_multiselect';
    const IS_UNIQUE_CHECK              = 'is_unique_check';
    const IS_PROJECT_RESTRICTED        = 'is_project_restricted';
    const DELETED                      = 'deleted';
    const IS_ACTIVE                    = 'is_active';

    public function getAttributeToOrder(int $attribute_group_id)
    {

        $select = $this->db
            ->select()
            ->from(Db_Attribute::TABLE_NAME,
                array(Db_Attribute::ID,
                    Db_Attribute::NAME,
                    Db_Attribute::DESCRIPTION,
                    Db_Attribute::NOTE,
                    Db_Attribute::ATTRIBUTE_TYPE_ID,
                    Db_Attribute::ATTRIBUTE_GROUP_ID,
                    Db_Attribute::ORDER_NUMBER,
                    Db_Attribute::IS_ACTIVE,
                )
            )
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_GROUP_ID . ' = ?', $attribute_group_id)
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ORDER_NUMBER);

        return $this->db->fetchAll($select);
    }

    public function getAttributes($orderBy = null, $direction = null)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME)
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('attributeTypeName' => Db_AttributeType::NAME, 'attributeType' => Db_AttributeType::DESCRIPTION))
            ->join(Db_AttributeGroup::TABLE_NAME, Db_AttributeGroup::TABLE_NAME . '.' . Db_AttributeGroup::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_GROUP_ID, array('attributeGroup' => Db_AttributeGroup::DESCRIPTION));

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $orderBy = Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME;

            $select->order($orderBy);
        }

        return $select;
    }


    public function getAllAttributes($orderBy = null, $direction = null)
    {
        $select = $this->db->select()->from(Db_Attribute::TABLE_NAME)
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME);
        return $this->db->fetchAll($select);
    }


    public function getAttributeByName(string $attributeName)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME, array(Db_Attribute::ID, Db_Attribute::NAME, Db_Attribute::IS_NUMERIC))
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME . ' = ?', $attributeName);
        return $this->db->fetchRow($select);
    }

    public function getAttributeByNameAll(string $attributeName)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME)
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME . ' = ?', $attributeName);
        return $this->db->fetchRow($select);
    }


    public function getAttributeWithTypeByName(string $attributeName)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME, array(Db_Attribute::ID, Db_Attribute::NAME, Db_Attribute::IS_NUMERIC))
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('typeName' => Db_AttributeType::NAME))
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME . ' = ?', $attributeName);
        return $this->db->fetchRow($select);
    }

    public function getAttributeDescriptionsByIds(array $ids)
    {
        $select  = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME, array(Db_Attribute::DESCRIPTION))
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' IN (?)', $ids);
        $rowsets = $this->db->fetchAll($select);
        foreach ($rowsets as $row)
            $attributeDescriptions[] = $row[Db_Attribute::DESCRIPTION];
        return $attributeDescriptions;
    }


    public function getAttributesWithFilter(string $filter, $orderBy = "", string $direction = "")
    {
        $filter = $filter . '%';
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME)
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('attributeTypeName' => Db_AttributeType::NAME))
            ->join(Db_AttributeGroup::TABLE_NAME, Db_AttributeGroup::TABLE_NAME . '.' . Db_AttributeGroup::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_GROUP_ID, array('attributeGroup' => Db_AttributeGroup::DESCRIPTION));

        if ($filter !== "") {
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::DESCRIPTION . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy !== "") {
            if ($direction !== "") {
                $orderBy .=  ' ' . $direction;
            }

            $select->order($orderBy);
        }

        return $select;
    }

    public function getActiveAttributesAutoComplete(string $query, int $attributeGroupId = 0, int $limit = -1)
    {
        $query  = "%" . $query . "%";
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME, array(Db_Attribute::NAME, Db_Attribute::ID))
            ->where(Db_Attribute::IS_ACTIVE . ' = ?', '1')
            ->where(Db_Attribute::NAME . ' LIKE ?', $query)
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME);

        if ($attributeGroupId > 0) {
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_GROUP_ID . '= ?', $attributeGroupId);
        }

        if ($limit > 0) {
            $select->limit($limit);
        }

        return $this->db->fetchAll($select);
    }

    public function getActiveAttributes(int $attributeGroupId = 0, string $direction = "", $orderBy = "")
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME)
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_ACTIVE . ' =?', '1');
        if ($attributeGroupId > 0)
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_GROUP_ID . '= ?', $attributeGroupId);

        $order = Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME . ' asc';
        if ($orderBy !== "") {
            $order = $orderBy . ' ' . $direction;
        }
        $select->order($order);

        return $this->db->fetchAll($select);
    }

    public function getAttribute(int $id)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME)
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }


    public function getSingleAttribute(int $id)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME)
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }


    public function getSingleAttributeWithType(int $id)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME, array())
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID)
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }


    public function deleteTempTableForCiCreate(string $sessionid)
    {
        Zend_Session::namespaceUnset(self::TEMP_CI_CREATE . $sessionid);
    }


    public function getInsertElements(array $typeIdList, string $sessionid, int $userId = 0)
    {
        $typeIdListString = implode(", ", $typeIdList);
        $select = "
		SELECT  distinct '" . $sessionid . "',
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . ",
				0,
				null,
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NAME . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_UNIQUE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_GROUP_ID . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ORDER_NUMBER . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NOTE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::INPUT_MAXLENGTH . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::TEXTAREA_COLS . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::TEXTAREA_ROWS . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_BOLD . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::REGEX . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::COLUMN . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_AUTOCOMPLETE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_MULTISELECT . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_UNIQUE_CHECK . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::HINT . ",
				" . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::IS_MANDATORY . ", 
				" . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::NAME . " AS type, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::DESCRIPTION . " AS attributeGroupDescr, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::NAME . " AS attributeGroupName, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ID . " AS attributeGroupId,
				";

        if ($userId > 0) {
            $select = $select . "  ( SELECT MAX(permission_write) FROM 
										" . Db_UserRole::TABLE_NAME . "
								  		 INNER JOIN " . Db_AttributeRole::TABLE_NAME . " ON " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ROLE_ID . " = " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::ROLE_ID . "
								  		 INNER JOIN " . Db_CiTypeAttribute::TABLE_NAME . " ON " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::ATTRIBUTE_ID . " = " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ATTRIBUTE_ID . "
								  		 WHERE " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::CI_TYPE_ID . " IN(" . $typeIdListString . ")
								  		 AND " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::USER_ID . " = '" . $userId . "'
								  		 AND " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
			) AS permission_write ";

        } else {
            $select = $select . " '1' AS permission_write ";
        }

        $select = $select . " 
		FROM " . Db_Attribute::TABLE_NAME . " 
		INNER JOIN " . Db_CiTypeAttribute::TABLE_NAME . " ON " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
		INNER JOIN " . Db_AttributeType::TABLE_NAME . " ON " . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . " 
		INNER JOIN " . Db_AttributeGroup::TABLE_NAME . " ON " . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ID . "  = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_GROUP_ID . " 
		WHERE (" . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::CI_TYPE_ID . " IN(" . $typeIdListString . "))";


        if ($userId > 0) {
            $subSelect = $this->db->select()
                ->from(Db_UserRole::TABLE_NAME, array())
                ->join(Db_AttributeRole::TABLE_NAME, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID, array(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID))
                ->where(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' =?', '1')
                ->join(Db_CiTypeAttribute::TABLE_NAME, Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID . ' = ' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID, array())
                ->where(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::CI_TYPE_ID . ' IN(' . $typeIdListString . ')')
                ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . '=?', $userId);
            $select    = $select . ' AND ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' IN(' . $subSelect . ')';
        }

        return $this->db->fetchAll($select);
    }

    /**
     * add to complete update form (nont-assigned attributes)
     */
    public function getInsertElementsForUpdate(string $typeIdList, string $sessionid, int $userId = 0)
    {
        $select = "
		SELECT  distinct '" . $sessionid . "',
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . ",
				0,
				null,
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NAME . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_UNIQUE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_GROUP_ID . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ORDER_NUMBER . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NOTE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::INPUT_MAXLENGTH . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::TEXTAREA_COLS . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::TEXTAREA_ROWS . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_BOLD . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::REGEX . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::COLUMN . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_AUTOCOMPLETE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_MULTISELECT . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_UNIQUE_CHECK . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::HINT . ",
                                " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_ACTIVE . ", 
				" . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::IS_MANDATORY . ", 
				" . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::NAME . " AS type, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::DESCRIPTION . " AS attributeGroupDescr, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::NAME . " AS attributeGroupName, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ID . " AS attributeGroupId,
				";
        $select = $select . " '1' AS permission_write ";
        $select = $select . " 
		FROM " . Db_Attribute::TABLE_NAME . " 
		INNER JOIN " . Db_CiTypeAttribute::TABLE_NAME . " ON " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
		INNER JOIN " . Db_AttributeType::TABLE_NAME . " ON " . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . " 
		INNER JOIN " . Db_AttributeGroup::TABLE_NAME . " ON " . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ID . "  = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_GROUP_ID . " 
		WHERE (" . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::CI_TYPE_ID . " IN(" . $typeIdList . ")) AND " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_ACTIVE . " = '1' ";


        if ($userId > 0) {
            $select = $select . " AND ( SELECT MAX(permission_write) FROM 
										" . Db_UserRole::TABLE_NAME . "
								  		 INNER JOIN " . Db_AttributeRole::TABLE_NAME . " ON " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ROLE_ID . " = " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::ROLE_ID . "
								  		 INNER JOIN " . Db_CiTypeAttribute::TABLE_NAME . " ON " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::ATTRIBUTE_ID . " = " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ATTRIBUTE_ID . "
								  		 WHERE " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::CI_TYPE_ID . " IN(" . $typeIdList . ")
								  		 AND " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::USER_ID . " = '" . $userId . "'
								  		 AND " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
			) = 1 ";

        }

        if ($userId > 0) {
            $subSelect = $this->db->select()
                ->from(Db_UserRole::TABLE_NAME, array())
                ->join(Db_AttributeRole::TABLE_NAME, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID, array(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID))
                ->where(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' =?', '1')
                ->join(Db_CiTypeAttribute::TABLE_NAME, Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID . ' = ' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID, array())
                ->where(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::CI_TYPE_ID . ' IN(' . $typeIdList . ')')
                ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . '=?', $userId);
            $select    = $select . ' AND ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' IN(' . $subSelect . ')';
        }


        return $this->db->fetchAll($select);
    }


    public function removeAttributesFromTempTable(string $sessionid, string $genId, bool $markDelete = false)
    {
        $sess     = new Zend_Session_Namespace(self::TEMP_CI_CREATE . $sessionid);
        $var      = $sessionid;
        $newArray = $sess->$var;


        if ($newArray) {
            foreach ($newArray as $key => $attribute) {
                if ($attribute[self::GEN_ID] == $genId) {
                    if ($markDelete) {
                        $newArray[$key][self::DELETED] = 1;
                    } else {
                        unset($newArray[$key]);
                    }
                    break;
                }
            }


            $sess->$var = $newArray;


        }
    }

    /**
     * used for ci create and add attribute to ci create and update form
     *
     * @param integer $attributeId
     * @param string  $sessionid
     * @param string  $typeList
     * @param integer $userId
     * @param bool    $initial
     */
    public function addAttributesToTempTable(int $attributeId, string $sessionid, array $typeList, int $userId, bool $initial = true)
    {
        $typeListString = implode(",", $typeList);
        $select = "
		SELECT  distinct '" . $sessionid . "',
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . ",
				0,
				null,
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NAME . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . ",
				" . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::NAME . " AS attributeTypeName,
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_UNIQUE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_GROUP_ID . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ORDER_NUMBER . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NOTE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::INPUT_MAXLENGTH . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::TEXTAREA_COLS . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::TEXTAREA_ROWS . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_BOLD . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::REGEX . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . ", 
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::COLUMN . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_AUTOCOMPLETE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_MULTISELECT . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_UNIQUE_CHECK . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_PROJECT_RESTRICTED . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::HINT . ",
				";

        if (!empty($typeList)) {
            $select .= "" . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::IS_MANDATORY . ", ";
        } else {
            $select .= "0 as " . Db_CiTypeAttribute::IS_MANDATORY . ", ";
        }
        $select .= "
				" . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::NAME . " AS type, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::DESCRIPTION . " AS attributeGroupDescr, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::NAME . " AS attributeGroupName, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ORDER_NUMBER . " AS attributeGroupOrderNumber, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ID . " AS attributeGroupId,";


        if ($userId) {
            $select = $select . "( SELECT distinct MAX(permission_write) FROM 
								  		 " . Db_UserRole::TABLE_NAME . "
								  		 INNER JOIN " . Db_AttributeRole::TABLE_NAME . " ON " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ROLE_ID . " = " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::ROLE_ID . "
								  		 WHERE " . Db_UserRole::TABLE_NAME . "." . Db_UserRole::USER_ID . " = '" . $userId . "'
								  		 AND " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::ATTRIBUTE_ID . " = '" . $attributeId . "'
								  		 
			) AS permission_write";

        } else {
            $select = $select . " '1' AS permission_write ";
        }

        $select .= " FROM " . Db_Attribute::TABLE_NAME;

        if (!empty($typeList)) {
            $select .= " INNER JOIN " . Db_CiTypeAttribute::TABLE_NAME . " ON " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . " ";
        }

        $select .= "
		INNER JOIN " . Db_AttributeType::TABLE_NAME . " ON " . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . " 
		INNER JOIN " . Db_AttributeGroup::TABLE_NAME . " ON " . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_GROUP_ID . " 
		WHERE " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . " = '" . $attributeId . "'";

        if (!empty($typeList)) {
            $select .= " AND " . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::CI_TYPE_ID . " IN (" . $typeListString . ")";
        }

        $select .= " AND " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_ACTIVE . " = '1'";

        $result = $this->db->fetchAll($select);


        $sess     = new Zend_Session_Namespace(self::TEMP_CI_CREATE . $sessionid);
        $var      = $sessionid;
        $newArray = $sess->$var;


        $genId = 1;
        if (!$newArray) {
            $newArray = array();
        } else {
            foreach ($newArray as $gen) {
                if ($gen[self::GEN_ID] >= $genId) {
                    $genId = $gen[self::GEN_ID] + 1;
                }
            }
        }

        foreach ($result as $res) {
            $data                           = array();
            $data[self::ID]                 = $res[self::ID];
            $data[self::CI_ATTRIBUTE_ID]    = $res[self::CI_ATTRIBUTE_ID];
            $data[self::GEN_ID]             = $genId;
            $data[self::NAME]               = $res[self::NAME];
            $data[self::ATTRIBUTE_TYPE_ID]  = $res[self::ATTRIBUTE_TYPE_ID];
            $data['attributeTypeName']      = $res['attributeTypeName'];
            $data[self::UNIQUE_CONSTRAINT]  = $res[self::UNIQUE_CONSTRAINT];
            $data[self::ATTRIBUTE_GROUP_ID] = $res[self::ATTRIBUTE_GROUP_ID];
            $data[self::ORDER_NUMBER]       = $res[self::ORDER_NUMBER];
            $data[self::NOTE]               = $res[self::NOTE];
            $data[self::INPUT_MAXLENGTH]    = $res[self::INPUT_MAXLENGTH];
            $data[self::TEXTAREA_COLS]      = $res[self::TEXTAREA_COLS];
            $data[self::TEXTAREA_ROWS]      = $res[self::TEXTAREA_ROWS];
            $data[self::TEXTOUTPUT_BOLD]    = $res[self::TEXTOUTPUT_BOLD];
            $data[self::REGEX]              = $res[self::REGEX];
            $data[self::DESCRIPTION]        = $res[self::DESCRIPTION];
            $data[self::MANDATORY]          = $res[self::MANDATORY];
            $data[self::HINT]               = $res[self::HINT];

            if ($initial) {
                $data[self::INITIAL] = '1';
            } else {
                $data[self::INITIAL] = '0';
            }
            $data[self::TYPE]                         = $res[self::TYPE];
            $data[self::VALUE]                        = $res[self::VALUE];
            $data[self::ATTRIBUTE_GROUP_DESCRIPTION]  = $res[self::ATTRIBUTE_GROUP_DESCRIPTION];
            $data[self::ATTRIBUTE_GROUP_NAME]         = $res[self::ATTRIBUTE_GROUP_NAME];
            $data[self::ATTRIBUTE_GROUP_ORDER_NUMBER] = $res[self::ATTRIBUTE_GROUP_ORDER_NUMBER];
            $data[self::PERMISSION_WRITE]             = $res[self::PERMISSION_WRITE];
            $data[self::COLUMN]                       = $res[self::COLUMN];
            $data[self::AUTOCOMPLETE]                 = $res[self::AUTOCOMPLETE];
            $data[self::MULTISELECT]                  = $res[self::MULTISELECT];
            $data[self::IS_UNIQUE_CHECK]              = $res[self::IS_UNIQUE_CHECK];
            $data[self::IS_PROJECT_RESTRICTED]        = $res[self::IS_PROJECT_RESTRICTED];


            $arrayKey = "gen_" . $genId;
            if ($res[self::CI_ATTRIBUTE_ID]) {
                $arrayKey = $res[self::CI_ATTRIBUTE_ID];
            }
            $newArray[$arrayKey] = $data;
            $genId++;
        }


        $sess->$var = $newArray;

        return $newArray;

    }

    /**
     * used for CI UPDATE
     *
     * @param integer $ciId
     * @param string  $sessionid
     * @param integer $ciTypeId
     * @param integer $userId
     * @param bool    $removeUnique
     */
    public function addCiAttributesToTempTable(int $ciId, string $sessionid, int $ciTypeId, int $userId = 0, bool $removeUnique = false)
    {
        $ciTypeDaoImpl = new Dao_CiType();
        $types         = $ciTypeDaoImpl->retrieveCiTypeHierarchy($ciTypeId);
        $typeList      = implode(',', $types);

        $select = "
		SELECT  '" . $sessionid . "',
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . ",
				" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ID . " as ciAttributeId,
				null,
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NAME . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . ",
				" . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::NAME . " AS attributeTypeName,
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_UNIQUE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_GROUP_ID . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ORDER_NUMBER . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NOTE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::INPUT_MAXLENGTH . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::TEXTAREA_COLS . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::TEXTAREA_ROWS . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_BOLD . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::HINT . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::REGEX . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::COLUMN . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_AUTOCOMPLETE . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_MULTISELECT . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_UNIQUE_CHECK . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_PROJECT_RESTRICTED . ",
				" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::HINT . ",
				" . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::IS_MANDATORY . " as " . Db_CiTypeAttribute::IS_MANDATORY . ", 
				" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::IS_INITIAL . " as initial, 
				" . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::NAME . " AS type,  
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::DESCRIPTION . " AS attributeGroupDescr, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::NAME . " AS attributeGroupName,  
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ORDER_NUMBER . " AS attributeGroupOrderNumber, 
				" . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ID . " AS attributeGroupId,
				";
        if ($userId > 0) {
            $select .= " roles.permission_read, roles.permission_write";
        } else {
            $select .= " '1' as permission_read, '1' as permission_write";
        }


        $select = $select . " FROM " . Db_Attribute::TABLE_NAME;

        if ($userId > 0) {
            $select = $select . ' inner join (select attribute_role.attribute_id, max(attribute_role.permission_read) permission_read ,max(attribute_role.permission_write) permission_write from attribute_role where role_id in (select role_id from user_role where user_role.user_id = ' . $userId . ')
			group by attribute_role.attribute_id) roles on roles.attribute_id = attribute.id';

        }

        $select = $select . " LEFT JOIN " . Db_CiTypeAttribute::TABLE_NAME . " ON (" . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID;
        if (!empty($typeList)) {
            $select .= " AND (" . Db_CiTypeAttribute::TABLE_NAME . "." . Db_CiTypeAttribute::CI_TYPE_ID . " IN(" . $typeList . "))";
        }

        $select .= ")
				INNER JOIN " . Db_CiAttribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
				INNER JOIN " . Db_AttributeType::TABLE_NAME . " ON " . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . "
				INNER JOIN " . Db_AttributeGroup::TABLE_NAME . " ON " . Db_AttributeGroup::TABLE_NAME . "." . Db_AttributeGroup::ID . "  = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_GROUP_ID . "
				WHERE " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = '" . $ciId . "'
				AND " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::IS_ACTIVE . " = '1'
				GROUP BY ci_attribute.id, attribute.id
				";;

        $result = $this->db->fetchAll($select);

        $otherResults = array();
        if ($types !== array()) {
            $otherResults = $this->getInsertElementsForUpdate($typeList, $sessionid, $userId);
        }

        $sess = new Zend_Session_Namespace(self::TEMP_CI_CREATE . $sessionid);

        $var = $sessionid;

        $newArray = $sess->$var;

        $genId = 1;
        if (!$newArray) {
            $newArray = array();
        } else {
            foreach ($newArray as $gen) {
                if ($gen[self::GEN_ID] >= $genId) {
                    $genId = $gen[self::GEN_ID] + 1;
                }
            }
        }

        foreach ($result as $res) {
            $data                        = array();
            $data[self::ID]              = $res[self::ID];
            $data[self::CI_ATTRIBUTE_ID] = $res[self::CI_ATTRIBUTE_ID];
            if ($removeUnique)
                $data[self::CI_ATTRIBUTE_ID] = null;

            $data[self::GEN_ID]             = $genId;
            $data[self::NAME]               = $res[self::NAME];
            $data[self::ATTRIBUTE_TYPE_ID]  = $res[self::ATTRIBUTE_TYPE_ID];
            $data['attributeTypeName']      = $res['attributeTypeName'];
            $data[self::UNIQUE_CONSTRAINT]  = $res[self::UNIQUE_CONSTRAINT];
            $data[self::ATTRIBUTE_GROUP_ID] = $res[self::ATTRIBUTE_GROUP_ID];
            $data[self::ORDER_NUMBER]       = $res[self::ORDER_NUMBER];
            $data[self::NOTE]               = $res[self::NOTE];
            $data[self::INPUT_MAXLENGTH]    = $res[self::INPUT_MAXLENGTH];
            $data[self::TEXTAREA_COLS]      = $res[self::TEXTAREA_COLS];
            $data[self::TEXTAREA_ROWS]      = $res[self::TEXTAREA_ROWS];
            $data[self::TEXTOUTPUT_BOLD]    = $res[self::TEXTOUTPUT_BOLD];
            $data[self::REGEX]              = $res[self::REGEX];
            $data[self::HINT]               = $res[self::HINT];
            $data[self::DESCRIPTION]        = $res[self::DESCRIPTION];

            if ($res[self::INITIAL]) {
                $data[self::MANDATORY] = $res[self::MANDATORY];
            } else {
                $data[self::MANDATORY] = '0';
            }
            $data[self::INITIAL]                      = $res[self::INITIAL];
            $data[self::TYPE]                         = $res[self::TYPE];
            $data[self::VALUE]                        = $res[self::VALUE];
            $data[self::ATTRIBUTE_GROUP_DESCRIPTION]  = $res[self::ATTRIBUTE_GROUP_DESCRIPTION];
            $data[self::ATTRIBUTE_GROUP_NAME]         = $res[self::ATTRIBUTE_GROUP_NAME];
            $data[self::ATTRIBUTE_GROUP_ORDER_NUMBER] = $res[self::ATTRIBUTE_GROUP_ORDER_NUMBER];
            $data[self::PERMISSION_WRITE]             = $res[self::PERMISSION_WRITE];
            $data[self::PERMISSION_READ]              = $res[self::PERMISSION_READ];
            $data[self::COLUMN]                       = $res[self::COLUMN];
            $data[self::AUTOCOMPLETE]                 = $res[self::AUTOCOMPLETE];
            $data[self::MULTISELECT]                  = $res[self::MULTISELECT];
            $data[self::IS_UNIQUE_CHECK]              = $res[self::IS_UNIQUE_CHECK];
            $data[self::IS_PROJECT_RESTRICTED]        = $res[self::IS_PROJECT_RESTRICTED];

            foreach ($otherResults as $key => $other) {
                if ($other[self::ID] == $res[self::ID]) {
                    unset($otherResults[$key]);
                    break;
                }
            }

            $arrayKey = "gen_" . $genId;
            if ($res[self::CI_ATTRIBUTE_ID]) {
                $arrayKey = $res[self::CI_ATTRIBUTE_ID];
            }
            $newArray[$arrayKey] = $data;

            $genId++;
        }


        foreach ($otherResults as $res) {
            $data                        = array();
            $data[self::ID]              = $res[self::ID];
            $data[self::CI_ATTRIBUTE_ID] = $res[self::CI_ATTRIBUTE_ID];
            if ($removeUnique)
                $data[self::CI_ATTRIBUTE_ID] = null;

            $data[self::GEN_ID]                       = $genId;
            $data[self::NAME]                         = $res[self::NAME];
            $data[self::ATTRIBUTE_TYPE_ID]            = $res[self::ATTRIBUTE_TYPE_ID];
            $data[self::UNIQUE_CONSTRAINT]            = $res[self::UNIQUE_CONSTRAINT];
            $data[self::ATTRIBUTE_GROUP_ID]           = $res[self::ATTRIBUTE_GROUP_ID];
            $data[self::ORDER_NUMBER]                 = $res[self::ORDER_NUMBER];
            $data[self::NOTE]                         = $res[self::NOTE];
            $data[self::INPUT_MAXLENGTH]              = $res[self::INPUT_MAXLENGTH];
            $data[self::TEXTAREA_COLS]                = $res[self::TEXTAREA_COLS];
            $data[self::TEXTAREA_ROWS]                = $res[self::TEXTAREA_ROWS];
            $data[self::TEXTOUTPUT_BOLD]              = $res[self::TEXTOUTPUT_BOLD];
            $data[self::REGEX]                        = $res[self::REGEX];
            $data[self::HINT]                         = $res[self::HINT];
            $data[self::DESCRIPTION]                  = $res[self::DESCRIPTION];
            $data[self::MANDATORY]                    = $res[self::MANDATORY];
            $data[self::INITIAL]                      = '1';
            $data[self::TYPE]                         = $res[self::TYPE];
            $data[self::VALUE]                        = $res[self::VALUE];
            $data[self::ATTRIBUTE_GROUP_DESCRIPTION]  = $res[self::ATTRIBUTE_GROUP_DESCRIPTION];
            $data[self::ATTRIBUTE_GROUP_NAME]         = $res[self::ATTRIBUTE_GROUP_NAME];
            $data[self::ATTRIBUTE_GROUP_ORDER_NUMBER] = $res[self::ATTRIBUTE_GROUP_ORDER_NUMBER];
            $data[self::PERMISSION_WRITE]             = $res[self::PERMISSION_WRITE];
            $data[self::COLUMN]                       = $res[self::COLUMN];
            $data[self::AUTOCOMPLETE]                 = $res[self::AUTOCOMPLETE];
            $data[self::MULTISELECT]                  = $res[self::MULTISELECT];
            $data[self::IS_UNIQUE_CHECK]              = $res[self::IS_UNIQUE_CHECK];
            $data[self::IS_PROJECT_RESTRICTED]        = $res[self::IS_PROJECT_RESTRICTED];

            $arrayKey = "gen_" . $genId;
            if ($res[self::CI_ATTRIBUTE_ID]) {
                $arrayKey = $res[self::CI_ATTRIBUTE_ID];
            }
            $newArray[$arrayKey] = $data;
            $genId++;
        }


        $sess->$var = $newArray;

        return $newArray;
    }


    public function getAttributesFromTempTable(string $sessionid)
    {
        $sess = new Zend_Session_Namespace(self::TEMP_CI_CREATE . $sessionid);
        $var  = $sessionid;


        // order
        $resultList = $sess->$var;
        if (!$resultList || count($resultList) <= 0) {
            return array();
        }

        uasort($resultList, array(__CLASS__, 'attributeGroupSort'));
        return $resultList;
    }

    private function attributeGroupSort(array $a, array $b)
    {
        if ($a[self::ATTRIBUTE_GROUP_ORDER_NUMBER] == $b[self::ATTRIBUTE_GROUP_ORDER_NUMBER]) {
            return $a[self::ORDER_NUMBER] > $b[self::ORDER_NUMBER];
        } else {
            return $a[self::ATTRIBUTE_GROUP_ORDER_NUMBER] > $b[self::ATTRIBUTE_GROUP_ORDER_NUMBER];
        }
    }


    public function getAttributesForExportAll(int $typeId, int $userId)
    {

        $sql = "select  
		attribute.id, attribute.name, attribute.description, attribute.note, attribute.hint, attribute.attribute_type_id, attribute.attribute_group_id, attribute.order_number, attribute.`column`, attribute.is_unique, attribute.is_numeric, attribute.is_bold, attribute.is_event, attribute.is_unique_check, attribute.is_autocomplete, attribute.is_multiselect, attribute.regex, attribute.script_name, attribute.input_maxlength, attribute.textarea_cols, attribute.textarea_rows, attribute.is_active, attribute.user_id, attribute.valid_from, attribute.tag 
		from (
		(
			select ci_attribute.attribute_id  from ci_attribute
			where ci_id in (select id from ci where ci.ci_type_id = $typeId)
			and 
			(ci_attribute.value_ci IS NOT NULL
				or (ci_attribute.value_text IS NOT NULL AND ci_attribute.value_text <> '')
				or ci_attribute.value_date IS NOT NULL 
				or ci_attribute.value_default IS NOT NULL)
		) union distinct ( 
			select cta.attribute_id from ci_type_attribute cta where ci_type_id = $typeId
			)
		) cia
		inner join attribute on cia.attribute_id = attribute.id
		inner join attribute_role on attribute_role.attribute_id = cia.attribute_id
		left join attribute_group on attribute_group.id = attribute.attribute_group_id
		left join attribute_group attribute_group_parent on attribute_group_parent.id = attribute_group.parent_attribute_group_id
		inner join user_role on user_role.role_id = attribute_role.role_id
			and user_role.user_id = $userId and attribute_role.permission_read = '1'
		where attribute.is_active = '1'
		group by attribute.id
		order by 
			if(attribute_group_parent.order_number IS NOT NULL, 
				attribute_group_parent.order_number, 
				if(attribute_group.order_number is not null, attribute_group.order_number, 0)
			) asc, 
			if(attribute_group.order_number is not null, attribute_group.order_number, 0) asc, 
			if(attribute.attribute_group_id is not null, attribute.attribute_group_id, 0) asc,
			attribute.order_number asc
		";

        return $this->db->fetchAll($sql);

    }


    /**
     *
     * (non-PHPdoc)
     * @see application/models/AttributeInterface#getAttributesByTypeId($typeId, $themeId)
     */
    public function getAttributesByTypeId(int $typeId, int $themeId, int $userId = 0)
    {

        // first select all ci types (parent style)
        $ciTypeDao = new Dao_CiType();
        $typeArray = $ciTypeDao->retrieveCiTypeHierarchy($typeId);
        $typeArray = array_reverse($typeArray);

        $attributePosition = array();
        foreach ($typeArray as $type) {
            $select = $this->db->select()
                ->from(Db_SearchListAttribute::TABLE_NAME, array(Db_SearchListAttribute::ORDER_NUMBER, Db_SearchListAttribute::COLUMN_WIDTH, Db_SearchListAttribute::ATTRIBUT_ID))
                ->join(Db_SearchList::TABLE_NAME, Db_SearchList::TABLE_NAME . '.' . Db_SearchList::ID . ' = ' . Db_SearchListAttribute::TABLE_NAME . '.' . Db_SearchListAttribute::SEARCH_LIST_ID, array(Db_SearchList::CI_TYPE_ID))
                ->where(Db_SearchList::TABLE_NAME . '.' . Db_SearchList::CI_TYPE_ID . ' =?', $type)
                ->where(Db_SearchList::TABLE_NAME . '.' . Db_SearchList::IS_ACTIVE . ' =?', '1');
            $list   = $this->db->fetchAll($select);

            foreach ($list as $l) {
                $attributePosition[$l[Db_SearchListAttribute::ORDER_NUMBER]] = $l[Db_SearchListAttribute::ATTRIBUT_ID];
                $attributeWith[$l[Db_SearchListAttribute::ATTRIBUT_ID]]      = $l[Db_SearchListAttribute::COLUMN_WIDTH];
            }
        }

        $positionList = array();
        foreach ($attributePosition as $position) {
            array_push($positionList, $position);
        }

        if (!$positionList || count($positionList) <= 0) {
            return array();
        }

        $select = $this->db->select()
            ->distinct()
            ->from(Db_Attribute::TABLE_NAME)
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' IN (?)', $positionList)
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_ACTIVE . ' = ?', '1');

        if ($userId > 0) {
            $select->join(Db_AttributeRole::TABLE_NAME, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID, array())
                ->join(Db_UserRole::TABLE_NAME, Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' = ' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID, array(Db_AttributeRole::PERMISSION_WRITE => 'MAX(' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_WRITE . ')'))
                ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . '=?', $userId)
                ->where(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' =?', '1')
                ->group(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID);
        }

        $rowset = $this->db->fetchAll($select);

        $newAttributePosition = array();
        foreach ($rowset as $row) {
            foreach ($attributePosition as $key => $val) {
                if ($row['id'] == $val) {
                    $newAttributePosition[$key]          = $row;
                    $newAttributePosition[$key]['width'] = $attributeWith[$row['id']];
                    break;
                }
            }
        }
        ksort($newAttributePosition);
        return $newAttributePosition;
    }

    public function checkUserAttributePermission(int $userId, int $attributeId, string $permission = 'r')
    {
        switch ($permission) {
            case "r":
                $column = Db_AttributeRole::PERMISSION_READ;
                break;
            case "rw":
                $column = Db_AttributeRole::PERMISSION_WRITE;
                break;
            default:
                $column = Db_AttributeRole::PERMISSION_READ;
        }


        $select = $this->db->select();
        $select->from(Db_Role::TABLE_NAME, array());

        $joinCondAttributeRole = sprintf(
            '%s.%s = %s.%s',
            Db_Role::TABLE_NAME, Db_Role::ID,
            Db_AttributeRole::TABLE_NAME, Db_AttributeRole::ROLE_ID
        );
        $select->join(Db_AttributeRole::TABLE_NAME, $joinCondAttributeRole, array());

        $joinCondUserRole = sprintf(
            '%s.%s = %s.%s',
            Db_Role::TABLE_NAME, Db_Role::ID,
            Db_UserRole::TABLE_NAME, Db_UserRole::ROLE_ID
        );
        $select->join(Db_UserRole::TABLE_NAME, $joinCondUserRole, array(
            'permission' => new Zend_Db_Expr('max(' . $column . ')')
        ));

        $select->where(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID . ' = ?', $attributeId);
        $select->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . ' = ?', $userId);

        $result = $this->db->fetchRow($select);

        if ($result !== false && $result['permission'] == 1) {
            return true;
        }

        return false;
    }


    public function getAttributeGroupList(string $viewList)
    {
        $select = $this->db->select()
            ->from(Db_AttributeGroup::TABLE_NAME)
            ->where(Db_AttributeGroup::ID . ' IN (' . $viewList . ')')
            ->order(Db_AttributeGroup::ORDER_NUMBER);;
        return $this->db->fetchAll($select);
    }

    public function getAttributeGroup(int $attributeGroupId)
    {
        $select = $this->db->select()
            ->from(Db_AttributeGroup::TABLE_NAME)
            ->where(Db_AttributeGroup::ID . ' =?', $attributeGroupId);
        return $this->db->fetchRow($select);
    }

    public function getAttributesByCiId(int $ciId, int $userId = 0, string $events = '0', string $notinattribute_Type = "")
    {
        $select = $this->getSelectForAttributeByCiId($ciId, $userId, $events, $notinattribute_Type);

        return $this->db->fetchAll($select);
    }

    public function getQueryAttributesByCITypes(string $types, int $userId = 0)
    {
        $types = explode(",", $types);

        $select = $this->db->select();

        $select->from(Db_CiTypeAttribute::TABLE_NAME, array(
            'value_text'    => new Zend_Db_Expr("''"),
            'value_date'    => new Zend_Db_Expr("''"),
            'value_default' => new Zend_Db_Expr("''"),
            'value_ci'      => new Zend_Db_Expr("''"),
            'valueNote'     => new Zend_Db_Expr("''"),
            'initial'       => new Zend_Db_Expr("'1'"),
            'ciAttributeId' => new Zend_Db_Expr("concat('Q', " . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ')'),
        ))
            ->join(Db_Attribute::TABLE_NAME, Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID)
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('attributeTypeName' => Db_AttributeType::NAME))
            ->join(Db_AttributeGroup::TABLE_NAME, Db_AttributeGroup::TABLE_NAME . '.' . Db_AttributeGroup::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_GROUP_ID, array('attribute_group' => Db_AttributeGroup::DESCRIPTION, 'parent_attribute_group' => Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID))
            ->where(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::CI_TYPE_ID . ' IN(?)', $types)
            ->where(Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . '=' . Util_AttributeType_Type_Query::ATTRIBUTE_TYPE_ID);

        if ($userId > 0) {
            $subSelect = $this->db->select()
                ->from(Db_UserRole::TABLE_NAME, array())
                ->join(Db_AttributeRole::TABLE_NAME, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID, array(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID))
                ->where(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' =?', '1')
                ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . '=?', $userId);
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' IN(' . $subSelect . ')');
            $select->joinLeft(array(
                'temp' => $this->db->select()
                    ->from(Db_AttributeRole::TABLE_NAME, array('permission_write' => 'MAX(permission_write)', 'badId' => Db_AttributeRole::ATTRIBUTE_ID))
                    ->join(Db_UserRole::TABLE_NAME, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID)
                    ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . '=?', $userId)
                    ->group('attribute_id')), Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = badId', array('permission_write'));

        }

        return $this->db->fetchAll($select);


    }


    public function getAttributesByAttributeTypeCiID(int $ciId, int $attributeType)
    {

        $select = $this->db->select()
            ->from(Db_CiTypeAttribute::TABLE_NAME, array(Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID))
            ->join(Db_Attribute::TABLE_NAME, Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID, array())
            ->join(Db_Ci::TABLE_NAME, Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::CI_TYPE_ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID, array())
            ->joinLeft(Db_CiAttribute::TABLE_NAME, Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . Db_CiTypeAttribute::TABLE_NAME . '.' . Db_CiTypeAttribute::ATTRIBUTE_ID . ' AND ' .
                Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' = ' . Db_Ci::TABLE_NAME . '.' . Db_Ci::ID, array(
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::ID,
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::CI_ID,
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::VALUE_TEXT,
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::VALUE_DATE,
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::VALUE_DEFAULT,
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::VALUE_CI,
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::NOTE,
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::IS_INITIAL,
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::HISTORY_ID,
                Db_CiAttribute::TABLE_NAME . '.' . DB_CiAttribute::VALID_FROM,
            ))
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID . ' = ?',  $attributeType)
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ?', $ciId);

        return $this->db->fetchAll($select);
    }


    public function getCiRelationLinkedValue(int $ciId, int $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::CI_ID . ' =?', $ciId)
            ->where(Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId);
        return $this->db->fetchRow($select);
    }


    public function getAttributeListForFormSelectByCiId(int $ciId, int $userId = 0)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_Attribute::TABLE_NAME, array(Db_Attribute::ID, Db_Attribute::DESCRIPTION))
            ->joinLeft(Db_CiAttribute::TABLE_NAME, Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID, array())
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' = ?', $ciId)
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ORDER_NUMBER);

        if ($userId > 0) {
            $subSelect = $this->db->select()
                ->from(Db_UserRole::TABLE_NAME, array())
                ->join(Db_AttributeRole::TABLE_NAME, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID, array(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID))
                ->where(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' =?', 1)
                ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . '=?', $userId);
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' IN(' . $subSelect . ')');
            $select->joinLeft(array(
                'temp' => $this->db->select()
                    ->from(Db_AttributeRole::TABLE_NAME, array('permission_write' => 'MAX(permission_write)', 'badId' => Db_AttributeRole::ATTRIBUTE_ID))
                    ->join(Db_UserRole::TABLE_NAME, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID)
                    ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . '=?', $userId)
                    ->group('attribute_id')), Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = badId', array());
        }
        $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' NOT IN(SELECT ' . Db_CiRelation::ATTRIBUTE_ID . ' from ' . Db_CiRelation::TABLE_NAME . ' where ' . Db_CiRelation::CI_ID_1 . ' = ' . $ciId . ' union SELECT ' . Db_CiRelation::LINKED_ATTRIBUTE_ID . ' from ' . Db_CiRelation::TABLE_NAME . ' where ' . Db_CiRelation::CI_ID_2 . ' = ' . $ciId . ' union select "0")');
        $select->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::DESCRIPTION);
        return $this->db->fetchAll($select);
    }


    public function getAttributeRowset()
    {
        $table  = new Db_Attribute();
        $select = $table->select()
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_ACTIVE . ' = ?', '1')
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ORDER_NUMBER);

        return $table->fetchAll($select);
    }

    public function getAttributeRowsetOrderName()
    {
        $table  = new Db_Attribute();
        $select = $table->select()
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_ACTIVE . ' = ?', '1')
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME);

        return $table->fetchAll($select);
    }

    public function getAttributeRowsetOrderNameWithFilter(string $filter)
    {
        $table  = new Db_Attribute();
        $select = $table->select()
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_ACTIVE . ' = ?', '1')
            ->where(Db_Attribute::NAME . ' LIKE "' . $filter . '%"')
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME);

        return $table->fetchAll($select);
    }


    public function getAttributeDefaultValues(int $attributeId, bool $checkValid = true)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultValues::TABLE_NAME)
            ->where(Db_AttributeDefaultValues::ATTRIBUTE_ID . ' =?', $attributeId);
        if ($checkValid)
            $select->where(Db_AttributeDefaultValues::IS_ACTIVE . ' =?', '1');

        $select->order(Db_AttributeDefaultValues::ORDER_NUMBER . ' ASC');
        $select->order(Db_AttributeDefaultValues::VALUE . ' ASC');
        return $this->db->fetchAll($select);
    }


    public function getAttributeCiTypeValues(int $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultCitypeAttributes::TABLE_NAME)
            ->join(Db_AttributeDefaultCitype::TABLE_NAME, Db_AttributeDefaultCitype::TABLE_NAME . '.' . Db_AttributeDefaultCitype::ID . ' = ' . Db_AttributeDefaultCitypeAttributes::TABLE_NAME . '.' . Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_DEFAULT_CITYPE_ID, array())
            ->where(Db_AttributeDefaultCitype::TABLE_NAME . '.' . Db_AttributeDefaultCitype::ATTRIBUTE_ID . ' =?', $attributeId)
            ->order(Db_AttributeDefaultCitypeAttributes::TABLE_NAME . '.' . Db_AttributeDefaultCitypeAttributes::ORDER_NUMBER . ' ASC');

        return $this->db->fetchAll($select);
    }


    public function getDefaultQuery(int $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultQueries::TABLE_NAME)
            ->where(Db_AttributeDefaultQueries::ATTRIBUTE_ID . ' =?', $attributeId);
        return $this->db->fetchRow($select);
    }

    public function getDefaultQueryParameter(int $queryId)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultQueriesParameter::TABLE_NAME)
            ->where(Db_AttributeDefaultQueriesParameter::QUERIES_ID . ' =?', $queryId);

        return $this->db->fetchAll($select);
    }


    public function deleteDefaultQuery(int $queryId)
    {
        $table = new Db_AttributeDefaultQueries();
        $where = $this->db->quoteInto(Db_AttributeDefaultQueries::ID . ' = ?', $queryId);

        return $table->delete($where);
    }

    public function deleteDefaultQueryByAttributeId(int $attributeId)
    {
        $table = new Db_AttributeDefaultQueries();
        $where = $this->db->quoteInto(Db_AttributeDefaultQueries::ATTRIBUTE_ID . ' = ?', $attributeId);

        return $table->delete($where);
    }


    public function getDefaultCiType(int $attributeId, int $ciTypeId = 0)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultCitype::TABLE_NAME)
            ->where(Db_AttributeDefaultCitype::ATTRIBUTE_ID . ' =?', $attributeId);

        if ($ciTypeId > 0) {
            $select->where(Db_AttributeDefaultCitype::CI_TYPE_ID . ' =? ', $ciTypeId);
        }

        $select->order(Db_AttributeDefaultCitype::JOIN_ORDER . ' ASC');

        return $this->db->fetchAll($select);
    }


    public function getDefaultCiTypeAttributes(int $defaultCiTypeId)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultCitypeAttributes::TABLE_NAME)
            ->where(Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_DEFAULT_CITYPE_ID . ' =?', $defaultCiTypeId)
            ->order(Db_AttributeDefaultCitypeAttributes::ORDER_NUMBER . ' ASC');

        return $this->db->fetchAll($select);
    }


    public function insertDefaultQuery(int $attributeId, string $query, string $listQuery = null)
    {
        $data                                           = array();
        $data[Db_AttributeDefaultQueries::ATTRIBUTE_ID] = $attributeId;
        $data[Db_AttributeDefaultQueries::QUERY]        = $query;
        $data[Db_AttributeDefaultQueries::LIST_QUERY]   = $listQuery;

        $table = new Db_AttributeDefaultQueries();
        return $table->insert($data);
    }

    public function insertDefaultQueryParameter(int $queryId, string $parameter)
    {
        $data                                                  = array();
        $data[Db_AttributeDefaultQueriesParameter::QUERIES_ID] = $queryId;
        $data[Db_AttributeDefaultQueriesParameter::PARAMETER]  = $parameter;

        $table = new Db_AttributeDefaultQueriesParameter();
        return $table->insert($data);
    }


    public function getSingleAttributeDefaultValues(int $attributeId, bool$checkValid = true)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultValues::TABLE_NAME)
            ->where(Db_AttributeDefaultValues::ATTRIBUTE_ID . ' =?', $attributeId);
        if ($checkValid)
            $select->where(Db_AttributeDefaultValues::IS_ACTIVE . ' =?', '1');

        return $this->db->fetchRow($select);
    }

    public function getAttributeDefaultValueByName(int $attributeId, string $value, bool $checkValid = true)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultValues::TABLE_NAME)
            ->where(Db_AttributeDefaultValues::ATTRIBUTE_ID . ' =?', $attributeId)
            ->where(Db_AttributeDefaultValues::VALUE . ' like ?', $value);
        if ($checkValid)
            $select->where(Db_AttributeDefaultValues::IS_ACTIVE . ' =?', '1');

        return $this->db->fetchRow($select);
    }

    public function getDefaultValuesForCiTypeAttribute(int $attributeId, bool $checkValid = true)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultCitype::TABLE_NAME)
            ->where(Db_AttributeDefaultCitype::ATTRIBUTE_ID . ' =?', $attributeId);

        $parent = $this->db->fetchRow($select);

        if (!$parent || !$parent[Db_AttributeDefaultCitype::ID])
            return array();

        $ciTypeDao = new Dao_CiType();
        $ciType    = $ciTypeDao->getCiType($parent[Db_AttributeDefaultCitype::CI_TYPE_ID]);


        $select = $this->db->select()
            ->from(Db_AttributeDefaultCitypeAttributes::TABLE_NAME)
            ->where(Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_DEFAULT_CITYPE_ID . ' =?', $parent[Db_AttributeDefaultCitype::ID])
            ->order(Db_AttributeDefaultCitypeAttributes::ORDER_NUMBER . ' DESC');

        $attributes = $this->db->fetchAll($select);

        $attArray = array();
        foreach ($attributes as $att) {
            array_push($attArray, $att[Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_ID]);
        }
        unset($attributes);


        $attributes = $this->getAttributesByIds($attArray);
        return array('ciType' => $ciType, 'ciTypeAttributes' => $attributes);
    }

    public function getCurrentCiTypeValue(int $ciId, string $attributeIdList)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME, array(Db_CiAttribute::VALUE_TEXT, Db_CiAttribute::VALUE_DEFAULT, Db_CiAttribute::VALUE_DATE, Db_CiAttribute::VALUE_CI, Db_CiAttribute::NOTE))
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array('attributeId' => Db_Attribute::ID))
            ->join(Db_AttributeType::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID . ' = ' . Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID, array('attributeType' => Db_AttributeType::NAME))
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' =?', $ciId)
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' IN(?)', $attributeIdList);
        return $this->db->fetchAll($select);
    }

    public function getAttributesByIds(array $ids)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME, array(Db_Attribute::ID, Db_Attribute::DESCRIPTION))
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' IN (?)', $ids);
        return $this->db->fetchAll($select);
    }

    public function getCiAttributesByCiTypeId(int $ciTypeId, string $attributeIdList, array $conditions, int $ciId = 0, $orderBy = "")
    {
        if (!$ciTypeId)
            $ciTypeId = '-1';
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME)
            ->joinLeft(Db_CiAttribute::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' AND ' . Db_CiAttribute::ATTRIBUTE_ID . ' IN(' . $attributeIdList . ')', array(Db_CiAttribute::VALUE_TEXT, Db_CiAttribute::VALUE_DATE, Db_CiAttribute::VALUE_CI, Db_CiAttribute::NOTE))
            ->joinLeft(Db_AttributeDefaultValues::TABLE_NAME, Db_AttributeDefaultValues::TABLE_NAME . '.' . Db_AttributeDefaultValues::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DEFAULT, array(Db_CiAttribute::VALUE_DEFAULT => Db_AttributeDefaultValues::VALUE))
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array('attributeId' => Db_Attribute::ID))
            ->join(Db_AttributeType::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID . ' = ' . Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID, array('attributeType' => Db_AttributeType::NAME))
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' IN(' . $ciTypeId . ')');

        foreach ($conditions as $ckey => $condition) {
            $select->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' IN(?)', $this->db->select()->from(Db_CiAttribute::TABLE_NAME, array(Db_CiAttribute::CI_ID))->where(Db_CiAttribute::ATTRIBUTE_ID . ' =?', $ckey)->where(Db_CiAttribute::VALUE_TEXT . ' LIKE "' . $condition . '" or ' . Db_CiAttribute::VALUE_DEFAULT . ' LIKE "' . $condition . '"'));
        }

        if ($ciId) {
            $select->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' =?', $ciId);
        }

        if ($orderBy === null) {
            $select->order(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);
        } else {
            $select->order($orderBy);
        }

        return $this->db->fetchAll($select);
    }

    public function getCiAttributesByCiTypeIdForCiList(int $ciTypeId, string $attributeIdList, int $ciId)
    {
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME)
            ->joinLeft(Db_CiAttribute::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' AND ' . Db_CiAttribute::ATTRIBUTE_ID . ' IN(' . $attributeIdList . ')', array(Db_CiAttribute::VALUE_TEXT, Db_CiAttribute::VALUE_DATE, Db_CiAttribute::VALUE_CI, Db_CiAttribute::NOTE))
            ->joinLeft(Db_AttributeDefaultValues::TABLE_NAME, Db_AttributeDefaultValues::TABLE_NAME . '.' . Db_AttributeDefaultValues::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DEFAULT, array(Db_CiAttribute::VALUE_DEFAULT => Db_AttributeDefaultValues::VALUE))
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array('attributeId' => Db_Attribute::ID))
            ->join(Db_AttributeType::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID . ' = ' . Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID, array('attributeType' => Db_AttributeType::NAME));

        if ($ciId > 0) {
            $select->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' =?', $ciId);
        } else {
            $select->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' IN(' . $ciTypeId . ')');
        }

        $select->order(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);

        return $this->db->fetchAll($select);
    }


    public function getAttributeDefaultValue(string $advId)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultValues::TABLE_NAME)
            ->where(Db_AttributeDefaultValues::ID . ' =?', $advId);

        return $this->db->fetchRow($select);
    }


    public function getValuesBySqlInjection(string $select)
    {
        $querys = preg_split("/;[\r|\n]/", $select);#split querys, only return last one --> works only with line-break
        foreach ($querys as $q) {
            if (trim($q) != '') {
                $sql = $this->db->query($q);
            }
        }
        if (isset($sql)) {
            return $sql->fetchAll();
        }
    }

    public function deleteAttributeDefaultValuesById(int $attributeDefaultValueId)
    {
        $table = new Db_AttributeDefaultValues();
        $where = $this->db->quoteInto(Db_AttributeDefaultValues::ID . ' = ?', $attributeDefaultValueId);

        return $table->delete($where);
    }

    public function deleteAttributeDefaultValuesByAttributeId(int $attributeId)
    {
        $table = new Db_AttributeDefaultValues();
        $where = $this->db->quoteInto(Db_AttributeDefaultValues::ATTRIBUTE_ID . ' = ?', $attributeId);

        return $table->delete($where);
    }

    public function deactivateAttributeDefaultValuesById(int $attributeDefaultValueId)
    {
        $table = new Db_AttributeDefaultValues();
        $where = $this->db->quoteInto(Db_AttributeDefaultValues::ID . ' = ?', $attributeDefaultValueId);

        $data                                       = array();
        $data[Db_AttributeDefaultValues::IS_ACTIVE] = '0';

        return $table->update($data, $where);
    }

    public function activateAttributeDefaultValuesById(int $attributeDefaultValueId)
    {
        $table = new Db_AttributeDefaultValues();
        $where = $this->db->quoteInto(Db_AttributeDefaultValues::ID . ' = ?', $attributeDefaultValueId);

        $data                                       = array();
        $data[Db_AttributeDefaultValues::IS_ACTIVE] = '1';

        return $table->update($data, $where);
    }

    public function insertAttributeDefaultValuesById(string $attributeDefaultValue, int $attributeId, int $orderNumber = 0)
    {
        $table = new Db_AttributeDefaultValues();

        $data                                          = array();
        $data[Db_AttributeDefaultValues::VALUE]        = $attributeDefaultValue;
        $data[Db_AttributeDefaultValues::ATTRIBUTE_ID] = $attributeId;
        $data[Db_AttributeDefaultValues::ORDER_NUMBER] = $orderNumber;
        $data[Db_AttributeDefaultValues::IS_ACTIVE] = '1';

        return $table->insert($data);
    }


    public function deleteCiTypeAttributes(int $attributeId)
    {
        try {
            $select = $this->db->select()
                ->from(Db_AttributeDefaultCitype::TABLE_NAME)
                ->where(Db_AttributeDefaultCitype::ATTRIBUTE_ID . ' =?', $attributeId);

            $att = $this->db->fetchRow($select);

            if ($att && $att[Db_AttributeDefaultCitype::ID]) {
                $confTable = new Db_AttributeDefaultCitypeAttributes();
                $where     = $this->db->quoteInto(Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_DEFAULT_CITYPE_ID . ' =?', $att[Db_AttributeDefaultCitype::ID]);
                $confTable->delete($where);

                $table = new Db_AttributeDefaultCitype();
                $where = $this->db->quoteInto(Db_AttributeDefaultCitype::ATTRIBUTE_ID . ' =?', $attributeId);
                $table->delete($where);
            }
        } catch (Exception $e) {
            $this->logger->log($e);
        }

        return;
    }

    public function insertCiTypeAttribute(int $ciTypeId, array $ciTypeAttributes, int $attributeId)
    {
        $table = new Db_AttributeDefaultCitype();

        $data                                          = array();
        $data[Db_AttributeDefaultCitype::ATTRIBUTE_ID] = $attributeId;
        $data[Db_AttributeDefaultCitype::CI_TYPE_ID]   = $ciTypeId;

        $parentId = $table->insert($data);

        $confTable = new Db_AttributeDefaultCitypeAttributes();

        $newData                                                                   = array();
        $newData[Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_DEFAULT_CITYPE_ID] = $parentId;

        if (is_array($ciTypeAttributes))
            foreach ($ciTypeAttributes as $attribute) {
                $newData[Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_ID] = $attribute;
                $confTable->insert($newData);
            }

    }


    public function updateCiTypeAttributesOrderNumber(array $attributes)
    {
        foreach ($attributes as $key => $attr) {
            $data                                                    = array();
            $data[Db_AttributeDefaultCitypeAttributes::ORDER_NUMBER] = $key;
            $where                                                   = $this->db->quoteInto(Db_AttributeDefaultCitypeAttributes::ID . ' =?', $attr[Db_AttributeDefaultCitypeAttributes::ID]);
            $table                                                   = new Db_AttributeDefaultCitypeAttributes();
            $table->update($data, $where);
        }
    }

    public function updateAttributeDefaultValuesByAdvId(int $advId, string $attributeDefaultValue)
    {
        $table = new Db_AttributeDefaultValues();

        $data                                   = array();
        $data[Db_AttributeDefaultValues::VALUE] = $attributeDefaultValue;

        return $table->update($data, $table->getAdapter()->quoteInto('id = ?', $advId));
    }

    public function updateAttributeDefaultValuesById(string $attributeDefaultValue, int $attributeId)
    {
        $table = new Db_AttributeDefaultValues();

        $data                                   = array();
        $data[Db_AttributeDefaultValues::VALUE] = $attributeDefaultValue;

        return $table->update($data, $table->getAdapter()->quoteInto('attribute_id = ?', $attributeId));
    }

    public function insertAttribute(array $attribute)
    {
        $table = new Db_Attribute();
        return $table->insert($attribute);
    }

    public function updateAttribute(array $attribute, int $attributeId)
    {

        $table = new Db_Attribute();
        $where = $table->getAdapter()->quoteInto(Db_Attribute::ID . ' = ?', $attributeId);

        $table->update($attribute, $where);

    }

    public function getAttributeRolesByAttributeId(int $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_Role::TABLE_NAME)
            ->joinLeft(Db_AttributeRole::TABLE_NAME, Db_AttributeRole::ROLE_ID . ' = ' . Db_Role::TABLE_NAME . '.' . Db_Role::ID . ' AND ' . Db_AttributeRole::ATTRIBUTE_ID . ' = ' . $attributeId, array(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_WRITE, Db_AttributeRole::ATTRIBUTE_ID));
        return $this->db->fetchAll($select);
    }

    public function getCurrentAttributeRolesByAttributeId(int $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_Role::TABLE_NAME)
            ->join(Db_AttributeRole::TABLE_NAME, Db_AttributeRole::ROLE_ID . ' = ' . Db_Role::TABLE_NAME . '.' . Db_Role::ID, array(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_WRITE, Db_AttributeRole::ATTRIBUTE_ID))
            ->where(Db_AttributeRole::ATTRIBUTE_ID . ' = ' . $attributeId)
            ->where(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' = "1" OR ' . Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_WRITE . ' = "1"');

        return $this->db->fetchAll($select);
    }

    public function getRolesForAttributes()
    {
        $select = $this->db->select()
            ->from(Db_Role::TABLE_NAME)
            ->order(Db_Role::NAME);
        return $this->db->fetchAll($select);
    }

    public function deleteRolesByAttributeId(int $attributeId)
    {
        $table = new Db_AttributeRole();
        $where = $table->getAdapter()->quoteInto(Db_AttributeRole::ATTRIBUTE_ID . ' = ?', $attributeId);

        $table->delete($where);
    }


    public function getAttributeRolesByRoleId(int $roleId)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_Attribute::TABLE_NAME)
            ->joinLeft(Db_AttributeRole::TABLE_NAME, Db_AttributeRole::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' AND ' . Db_AttributeRole::ROLE_ID . ' = ' . $roleId, array(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_WRITE, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID))
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME);
        return $this->db->fetchAll($select);
    }

    public function deleteRolesByRoleId(int $roleId)
    {
        $table = new Db_AttributeRole();
        $where = $table->getAdapter()->quoteInto(Db_AttributeRole::ROLE_ID . ' = ?', $roleId);

        $table->delete($where);
    }

    public function deleteAttributeRole(int $roleId, int $attributeId)
    {
        $table = new Db_AttributeRole();
        $where = array(
            $table->getAdapter()->quoteInto(Db_AttributeRole::ROLE_ID . ' = ?', $roleId),
            $table->getAdapter()->quoteInto(Db_AttributeRole::ATTRIBUTE_ID . ' = ?', $attributeId),
        );

        $table->delete($where);
    }


    public function updateRolesByAttributeId(int $attributeId, int $roleId, $read, $write)
    {
        $table = new Db_AttributeRole();

        $attributeRole                                     = array();
        $attributeRole[Db_AttributeRole::ATTRIBUTE_ID]     = $attributeId;
        $attributeRole[Db_AttributeRole::ROLE_ID]          = $roleId;
        $attributeRole[Db_AttributeRole::PERMISSION_READ]  = $read;
        $attributeRole[Db_AttributeRole::PERMISSION_WRITE] = $write;

        $table->insert($attributeRole);
    }


    public function insertRolesByAttributeId(int $attributeId, int $roleId, $read, $write)
    {
        $table = new Db_AttributeRole();

        $attributeRole                                     = array();
        $attributeRole[Db_AttributeRole::ATTRIBUTE_ID]     = $attributeId;
        $attributeRole[Db_AttributeRole::ROLE_ID]          = $roleId;
        $attributeRole[Db_AttributeRole::PERMISSION_READ]  = $read;
        $attributeRole[Db_AttributeRole::PERMISSION_WRITE] = $write;

        $table->insert($attributeRole);
    }

    public function updateAttributeRole(int $attributeId, int $roleId, $read, $write)
    {
        $table = new Db_AttributeRole();

        $ciType[Db_AttributeRole::PERMISSION_READ]  = $read;
        $ciType[Db_AttributeRole::PERMISSION_WRITE] = $write;

        $where = array(
            $table->getAdapter()->quoteInto(Db_AttributeRole::ATTRIBUTE_ID . ' = ?', $attributeId),
            $table->getAdapter()->quoteInto(Db_AttributeRole::ROLE_ID . ' = ?', $roleId),
        );

        return $table->update($ciType, $where);
    }


    public function getAttributersBySearchListId(int $searchListId)
    {
        $select = $this->db->select()
            ->from(Db_SearchListAttribute::TABLE_NAME)
            ->where(Db_SearchListAttribute::SEARCH_LIST_ID . ' = ?', $searchListId);
        return $this->db->fetchAll($select);
    }

    public function getCiAttributeUsingAttributeDefaultValue(int $advId, int $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME, array(Db_CiAttribute::ID))
            ->where(Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId)
            ->where(Db_CiAttribute::VALUE_DEFAULT . ' =?', $advId);

        return $this->db->fetchRow($select);
    }


    public function getCiAttributeUsingAttributeDefaultValueForCheckbox(string $advId, int $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME, array(Db_CiAttribute::CI_ID))
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId)
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT . ' LIKE "%' . $advId . '%"');
        return $this->db->fetchRow($select);
    }


    public function getSingleCiAttributeById(int $ciAttributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::ID . ' =?', $ciAttributeId);
        return $this->db->fetchRow($select);
    }

    public function insertCiAttribute(array $data)
    {
        $table = new Db_CiAttribute();
        return $table->insert($data);
    }

    /**
     * updates a single ci attribute
     *
     * @return int id of ci_attribute
     */
    public function updateCiAttribute(int $ciAttributeId, array $data)
    {

        $table = new Db_CiAttribute();
        $where = $this->db->quoteInto(Db_CiAttribute::ID . ' =?', $ciAttributeId);
        return $table->update($data, $where);
    }


    public function getAttributeTypeId(int $typeId)
    {
        $select = $this->db->select()
            ->from(Db_AttributeType::TABLE_NAME)
            ->where(Db_AttributeType::ID . ' =?', $typeId);

        return $this->db->fetchRow($select);
    }


    public function getCiAttributeById(int $ciAttributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array(Db_Attribute::NAME, Db_Attribute::DESCRIPTION, Db_Attribute::HINT, Db_Attribute::ATTRIBUTE_TYPE_ID, Db_Attribute::TEXTAREA_COLS, Db_Attribute::TEXTAREA_ROWS, Db_Attribute::REGEX, Db_Attribute::IS_UNIQUE, Db_Attribute::IS_AUTOCOMPLETE, Db_Attribute::IS_MULTISELECT))
            ->join(Db_AttributeType::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID . ' = ' . Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID, array('type' => Db_AttributeType::NAME, 'attributeTypeName' => Db_AttributeType::NAME, 'attribute_type_id' => Db_AttributeType::ID))
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ID . ' =?', $ciAttributeId);
        return $this->db->fetchRow($select);
    }


    public function getCiAttributesByCiId(int $ciId, int $attributeId, string $valueText)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::CI_ID . ' =?', $ciId)
            ->where(Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId)
            ->where(Db_CiAttribute::VALUE_CI . ' =?', $valueText);

        return $this->db->fetchRow($select);
    }

    public function getCiAttributesByCiIdAttributeID(int $ciId, int $attributeId, &$counter = null)
    {

        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array(Db_Attribute::NAME, Db_Attribute::DESCRIPTION, Db_Attribute::HINT, Db_Attribute::ATTRIBUTE_TYPE_ID, Db_Attribute::TEXTAREA_COLS, Db_Attribute::TEXTAREA_ROWS, Db_Attribute::REGEX, Db_Attribute::IS_UNIQUE, Db_Attribute::IS_AUTOCOMPLETE, Db_Attribute::IS_MULTISELECT))
            ->join(Db_AttributeType::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID . ' = ' . Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID, array('type' => Db_AttributeType::NAME, 'attributeTypeName' => Db_AttributeType::NAME, 'attribute_type_id' => Db_AttributeType::ID))
            ->where(Db_CiAttribute::CI_ID . ' =?', $ciId)
            ->where(Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId);

        $result = $this->db->fetchAll($select);

        if(is_array($result) && isset($result[0])) {
            $counter = count($result);
            return $result[0];
        }

        $counter = 0;

        return false;
    }


    public function countAttributeUsed(int $attributeId)
    {
        $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_CiAttribute::TABLE_NAME . "
				   WHERE " . Db_CiAttribute::ATTRIBUTE_ID . " = '" . $attributeId . "'
				   ";

        return $this->db->fetchRow($select);
    }

    public function deleteAttribute(int $attributeId)
    {
        $table = new Db_Attribute();

        $where = $table->getAdapter()->quoteInto(Db_Attribute::ID . ' = ?', $attributeId);
        $table->delete($where);
    }

    public function deactivateAttribute(int $attributeId)
    {
        $sql = "UPDATE " . Db_Attribute::TABLE_NAME . " SET " . Db_Attribute::IS_ACTIVE . " = '0' 
		WHERE " . Db_Attribute::ID . " = '" . $attributeId . "'";
        $this->db->query($sql);
    }

    public function activateAttribute(int $attributeId)
    {
        $sql = "UPDATE " . Db_Attribute::TABLE_NAME . " SET " . Db_Attribute::IS_ACTIVE . " = '1' 
		WHERE " . Db_Attribute::ID . " = '" . $attributeId . "'";
        $this->db->query($sql);
    }

    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_Attribute::NAME . ' LIKE ?', $value);

        if($id > 0) {
            $select->where(Db_Attribute::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }

    public function checkAttributeValuesUnique(int $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_CiAttribute::ATTRIBUTE_ID . ' = ?', $attributeId)
            ->group(Db_CiAttribute::VALUE_TEXT)
            ->having("cnt > 1")
            ->limit(1)
        ;

        $row = $this->db->fetchRow($select);

        // if at least one row could be fetched --> not unique
        if(is_array($row)) {
            return false;
        }

        return true;
    }

    public function updateDefaultOption(int $optionId, string $value, $orderNumber = null)
    {
        $logger = Zend_Registry::get('Log');


        $table = new Db_AttributeDefaultValues();

        $data                                   = array();
        $data[Db_AttributeDefaultValues::VALUE] = $value;


        if ($orderNumber == '')
            $orderNumber = null;

        $data[Db_AttributeDefaultValues::ORDER_NUMBER] = $orderNumber;


        $where = $this->db->quoteInto(Db_AttributeDefaultValues::ID . ' =?', $optionId);
        $table->update($data, $where);
    }


    public function getCiAttributesForCiTypeUpdate(int $ciId)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array())
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('type' => Db_AttributeType::NAME))
            ->where(Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::NAME . ' =?', Enum_AttributeType::CI_TYPE_PERSIST)
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT . ' =?', $ciId);
        return $this->db->fetchAll($select);
    }


    public function getCiAttributesByAttributeId(int $attributeId)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array())
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('type' => Db_AttributeType::NAME))
            ->where(Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::NAME . ' =?', Enum_AttributeType::CI_TYPE_PERSIST)
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId);
        return $this->db->fetchAll($select);
    }

    public function getCiAttributesByAttributeId_blank($attributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId);
        return $this->db->fetchAll($select);
    }


    public function executeStatement(string $statement)
    {
        return $this->db->fetchAll($statement);
    }

    public function getScrollable(int $ciTypeId)
    {
        $select = $this->db->select()
            ->from(Db_SearchList::TABLE_NAME, array(Db_SearchList::IS_SCROLLABLE))
            ->where(Db_SearchList::CI_TYPE_ID . ' =?', $ciTypeId);

        return $this->db->fetchRow($select);
    }

    public function getCountAttributeGroupsByAttributeGroupId(int $attributeGroupId)
    {
        $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_Attribute::TABLE_NAME . "
				   WHERE " . Db_Attribute::ATTRIBUTE_GROUP_ID . " = '" . $attributeGroupId . "'
				   ";

        return $this->db->fetchRow($select);
    }

    public function getXMLTagbyAttributeName(string $name)
    {

        $select = "SELECT " . Db_Attribute::TAG . " FROM " . Db_Attribute::TABLE_NAME . "
			   WHERE " . Db_Attribute::NAME . " = '" . $name . "'";
        $xmltag = $this->db->fetchRow($select);
        return $xmltag[Db_Attribute::TAG];

    }

    public function getAttributeIdsByCiId(int $ciId, $user = null)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::CI_ID . ' =?', $ciId);
        return $this->db->fetchAll($select);
    }

    public function getAttributeHistoryDataForCi(int $ciId)
    {
        $select = $this->db->select()
            ->from(Db_History_CiAttribute::TABLE_NAME)
            ->where(Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::CI_ID . "=?", $ciId);
        return $this->db->fetchAll($select);
    }

    /**
     * Gets all the attribute information needed for the ci/detail.phtml for a given ci.
     * This fetches only the attributes which have been changed.
     * Following condition is used to determine which attributes changed:
     * valid_from < $point_in_time < valid_to
     *
     * @param $ciId       int id of the ci to fetch the attributes from
     * @param $history_id int id of history row that should be used used for determining point in time
     *
     * @return array|null all history entries or null if there are none
     */
    public function getAttributeDataForPointInTime(int $ciId, int $history_id)
    {
        $select = $this->getSelectForAttributeByCiId($ciId, 0, 'all', "", true, $history_id);
        return $this->db->fetchAll($select);
    }

    /**
     * Returns the select statement needed to get all attributes for a given ci. By passing additional parameters
     * you can restrict the result for a given user, fetch only events or exclude a certain attribute type.
     *
     * Moved into separate function since the same code is needed in 2 different locations with minor changes.
     *
     * @param      $ciId                int id of the ci from which the attributes should be fetched
     * @param      $userId              int id of the user to check permissions for. Default null
     * @param      $events              string if events should be fetched:
     *                                  '0' -> no events, '1' -> only events, 'all' -> not events + events (= all attributes)
     *                                  Default '0'
     * @param      $notinattribute_Type int id of the attribute type to exclude. Default null
     * @param bool $use_history         true if you want to fetch the history information instead of current ci attribute information.
     *                                  if this is set to true, $point_in_time has to be set. Default false
     * @param      $history_id          id of history row that should be used used for determining point in time
     *                                  Default null, required if $use_history is true
     *
     * @return mixed returns the select statement that can be passed to db->fetchAll
     */
    private function getSelectForAttributeByCiId(int $ciId, int $userId = 0, string $events = '0', string $notinattribute_Type = "", bool $use_history = false, int $history_id = 0)
    {
        $select = $this->db->select()->distinct();

        $select->from(Db_Attribute::TABLE_NAME)
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('attributeTypeName' => Db_AttributeType::NAME))
            ->join(Db_AttributeGroup::TABLE_NAME, Db_AttributeGroup::TABLE_NAME . '.' . Db_AttributeGroup::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_GROUP_ID, array('attribute_group' => Db_AttributeGroup::DESCRIPTION, 'parent_attribute_group' => Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID));
        if ($use_history) {
            $select->joinLeft(Db_History_CiAttribute::TABLE_NAME, Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID, array(Db_History_CiAttribute::VALUE_TEXT, Db_History_CiAttribute::VALUE_DATE, Db_History_CiAttribute::VALUE_CI, 'ciAttributeId' => Db_History_CiAttribute::ID, 'initial' => Db_History_CiAttribute::IS_INITIAL, 'valueNote' => Db_History_CiAttribute::NOTE, Db_History_CiAttribute::VALID_FROM, Db_History_CiAttribute::VALID_TO))
                ->where(Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::CI_ID . "=?", $ciId)
                ->joinLeft(Db_AttributeDefaultValues::TABLE_NAME, Db_History_CiAttribute::TABLE_NAME . '.' . Db_History_CiAttribute::VALUE_DEFAULT . ' = ' . Db_AttributeDefaultValues::TABLE_NAME . '.' . Db_AttributeDefaultValues::ID, array(Db_History_CiAttribute::VALUE_DEFAULT => Db_AttributeDefaultValues::VALUE));

        } else {
            $select->joinLeft(Db_CiAttribute::TABLE_NAME, Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID, array(Db_CiAttribute::VALUE_TEXT, Db_CiAttribute::VALUE_DATE, Db_CiAttribute::VALUE_CI, 'ciAttributeId' => Db_CiAttribute::ID, 'initial' => Db_CiAttribute::IS_INITIAL, 'valueNote' => Db_CiAttribute::NOTE, Db_CiAttribute::VALID_FROM, Db_CiAttribute::HISTORY_ID))
                ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' = ?', $ciId)
                ->joinLeft(Db_AttributeDefaultValues::TABLE_NAME, Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DEFAULT . ' = ' . Db_AttributeDefaultValues::TABLE_NAME . '.' . Db_AttributeDefaultValues::ID, array(Db_CiAttribute::VALUE_DEFAULT => Db_AttributeDefaultValues::VALUE));
        }

        if ($events != 'all') {
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_EVENT . ' =?', $events);
        }
        $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_ACTIVE . ' = ?', '1')
            ->order(Db_AttributeGroup::TABLE_NAME . '.' . Db_AttributeGroup::ORDER_NUMBER)
            ->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ORDER_NUMBER);

        if ($notinattribute_Type !== "") {
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID . ' != ' . $notinattribute_Type);
        }


        if ($userId > 0) {
            $select->joinLeft(array(
                'temp' => $this->db->select()
                    ->from(Db_AttributeRole::TABLE_NAME, array(
                            'permission_read'  => 'MAX(permission_read)',
                            'permission_write' => 'MAX(permission_write)',
                            'badId'            => Db_AttributeRole::ATTRIBUTE_ID)
                    )
                    ->join(Db_UserRole::TABLE_NAME, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID)
                    ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . '=?', $userId)
                    ->group('attribute_id')), Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = badId', array('permission_write'));

            $select->where(Db_AttributeRole::PERMISSION_READ . ' = 1');

        }


        if ($use_history > 0) {
            $select
                ->where(Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::HISTORY_ID . "<= ?", $history_id)
                ->where(Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::HISTORY_ID_DELETE . "> ?", $history_id)
                ->group(Db_History_CiAttribute::TABLE_NAME . "." . Db_History_CiAttribute::ID);
        }

        return $select;
    }
}
