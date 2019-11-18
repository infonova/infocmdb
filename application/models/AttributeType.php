<?php

class Dao_AttributeType extends Dao_Abstract
{

    public function getAttributeTypes()
    {
        $table = new Db_AttributeType();
        return $table->select();
    }

    public function getAttributeTypeRowset()
    {
        $table  = new Db_AttributeType();
        $select = $table->select()
            ->where(Db_AttributeType::IS_ACTIVE . ' =?', '1')
            ->order(Db_AttributeType::DESCRIPTION);;

        return $table->fetchAll($select);
    }


    public function getAttributetypePagination($orderBy = null, $direction = null, $filter = null)
    {
        $select = $this->db->select()->from(Db_AttributeType::TABLE_NAME);

        if ($filter) {
            $select = $select
                ->where(Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::NAME . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $orderBy = Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::NAME;

            $select->order($orderBy);
        }
        return $select;
    }


    public function updateAttributetype($data, $menuId)
    {
        $table = new Db_AttributeType();
        $where = $this->db->quoteInto(Db_AttributeType::ID . ' =?', $menuId);
        return $table->update($data, $where);
    }

    public function getAttributetype($menuId, $orderBy = null, $direction = null)
    {
        $table  = new Db_AttributeType();
        $select = $table->select()->where(Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ?', $menuId);

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else $select->order(Db_AttributeType::NAME);

        return $this->db->fetchRow($select);
    }

}