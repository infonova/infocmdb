<?php

class Dao_AttributeGroup extends Dao_Abstract
{

    public function getAttributeGroups()
    {

        $table  = new Db_AttributeGroup();
        $select = $table->select();

        return $select;
    }

    public function getAttributeGroupsForPagination($orderBy = null, $direction = null, $filter = null)
    {
        $select = $this->db->select()
            ->from(array('vt' => Db_AttributeGroup::TABLE_NAME))
            ->joinLeft(array('vp' => Db_AttributeGroup::TABLE_NAME), 'vt.' . Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID . ' = vp.' . Db_AttributeGroup::ID, array('parent' => Db_AttributeGroup::DESCRIPTION));

        if ($filter) {
            $select = $select
                ->where('vt.' . Db_AttributeGroup::NAME . ' LIKE "%' . $filter . '%"')
                ->orWhere('vt.' . Db_AttributeGroup::DESCRIPTION . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_AttributeGroup::NAME);
        }

        return $select;
    }

    public function getAttributeGroupParent($id)
    {
        $select = $this->db->select()
            ->from(Db_AttributeGroup::TABLE_NAME, array())
            ->join(Db_AttributeGroup::TABLE_NAME . ' as parent', Db_AttributeGroup::TABLE_NAME . '.' . Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID . '= parent.' . Db_AttributeGroup::ID, array('parentname' => Db_AttributeGroup::NAME))
            ->where(Db_AttributeGroup::TABLE_NAME . '.' . Db_AttributeGroup::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }

    //returns all parent elements of $attribute_group_id and writes it in $result
    //the first element(0) is the top of hierarchy, the second(1) the first child, ...
    //ATTENTION: returns only parents not other childs!
    public function getAttributeGroupHierarchy($attribute_group_id, $result = array())
    {
        $select                = $this->db->select()
            ->from(Db_AttributeGroup::TABLE_NAME)
            ->where(Db_AttributeGroup::TABLE_NAME . '.' . Db_AttributeGroup::ID . ' =?', $attribute_group_id);
        $attribute_group_child = $this->db->fetchRow($select);
        $result[]              = $attribute_group_child;
        if (!empty($attribute_group_child[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID])) {
            return $this->getAttributeGroupHierarchy($attribute_group_child[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID], $result);
        }
        return array_reverse($result);
    }

    public function getAttributeGroup($id)
    {
        $select = $this->db->select()
            ->from(Db_AttributeGroup::TABLE_NAME)
            ->where(Db_AttributeGroup::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }

    public function getAttributeGroupByName($name)
    {
        $select = $this->db->select()
            ->from(Db_AttributeGroup::TABLE_NAME)
            ->where(Db_AttributeGroup::NAME . ' LIKE "' . $name . '"');

        return $this->db->fetchRow($select);
    }

    public function getAttributeGroupRowset($orderBy = null, $direction = null)
    {
        $table  = new Db_AttributeGroup();
        $select = $table->select();

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else $select->order(Db_AttributeGroup::DESCRIPTION, ' ASC');

        $rowset = $table->fetchAll($select);

        return $rowset;
    }


    public function insertAttributeGroup($attributeGroup)
    {
        $table = new Db_AttributeGroup();
        return $table->insert($attributeGroup);
    }

    public function updateAttributeGroup($attributeGroupId, $attributeGroup)
    {
        $table = new Db_AttributeGroup();
        $where = $this->db->quoteInto(Db_AttributeGroup::ID . ' =?', $attributeGroupId);
        return $table->update($attributeGroup, $where);
    }


    public function deleteAttributeGroup($attributeGroupId)
    {
        $table = new Db_AttributeGroup();
        $where = $this->db->quoteInto(Db_AttributeGroup::ID . ' =?', $attributeGroupId);
        return $table->delete($where);
    }

    public function deactivateAttributeGroup($attributeGroupId)
    {
        $select = "UPDATE " . Db_AttributeGroup::TABLE_NAME . " SET " . Db_AttributeGroup::IS_ACTIVE . " = '0' 
		WHERE " . Db_AttributeGroup::ID . " = '" . $attributeGroupId . "'";
        $this->db->query($select);
    }

    public function activateAttributeGroup($attributeGroupId)
    {
        $select = "UPDATE " . Db_AttributeGroup::TABLE_NAME . " SET " . Db_AttributeGroup::IS_ACTIVE . " = '1' 
		WHERE " . Db_AttributeGroup::ID . " = '" . $attributeGroupId . "'";
        $this->db->query($select);
    }

    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_AttributeGroup::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_AttributeGroup::NAME . ' LIKE ?', $value);

        if($id > 0) {
            $select->where(Db_AttributeGroup::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }
}