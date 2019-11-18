<?php

class Dao_SearchList extends Dao_Abstract
{


    public function getSearchLists()
    {
        $table = new Db_SearchList();
        return $this->db->fetchAll($table->select());
    }

    public function getSearchList($searchListId)
    {
        $table = new Db_SearchList();
        return $this->db->fetchRow($table->select()->where(Db_SearchList::ID . ' = ?', $searchListId));
    }

    public function getSearchListsForTheme()
    {
        $table  = new Db_SearchList();
        $select = $table->select()
            ->where(Db_SearchList::CI_TYPE_ID . ' =?', 0)
            ->where(Db_SearchList::IS_TEMPLATE . ' =?', '1')
            ->order(Db_SearchList::NAME);
        return $this->db->fetchAll($select);
    }


    public function getSearchListByCiTypeId($ciTypeId)
    {
        $select = $this->db->select()
            ->from(Db_SearchList::TABLE_NAME, array(Db_SearchList::ID))
            ->where(Db_SearchList::CI_TYPE_ID . ' =?', $ciTypeId);

        return $this->db->fetchRow($select);
    }

    public function getSearchListAttributesByCiTypeId($ciTypeId)
    {
        $select = $this->db->select()
            ->from(Db_SearchList::TABLE_NAME, array(Db_SearchList::ID, Db_SearchList::IS_SCROLLABLE))
            ->joinLeft(Db_SearchListAttribute::TABLE_NAME, Db_SearchListAttribute::TABLE_NAME . '.' . Db_SearchListAttribute::SEARCH_LIST_ID . ' = ' . Db_SearchList::TABLE_NAME . '.' . Db_SearchList::ID, array(Db_SearchListAttribute::ORDER_NUMBER, Db_SearchListAttribute::ATTRIBUT_ID, Db_SearchListAttribute::COLUMN_WIDTH))
            ->where(Db_SearchList::CI_TYPE_ID . ' =?', $ciTypeId);

        return $this->db->fetchAll($select);
    }

    public function getSearchListAttributesDefault()
    {
        $select = $this->db->select()
            ->from(Db_SearchList::TABLE_NAME, array(Db_SearchList::ID))
            ->joinLeft(Db_SearchListAttribute::TABLE_NAME, Db_SearchListAttribute::TABLE_NAME . '.' . Db_SearchListAttribute::SEARCH_LIST_ID . ' = ' . Db_SearchList::TABLE_NAME . '.' . Db_SearchList::ID, array(Db_SearchListAttribute::ORDER_NUMBER, Db_SearchListAttribute::ATTRIBUT_ID, Db_SearchListAttribute::COLUMN_WIDTH))
            ->where(Db_SearchList::CI_TYPE_ID . ' =?', 0);
        return $this->db->fetchAll($select);
    }


    public function insertSearchList($data)
    {
        $table = new Db_SearchList();
        return $table->insert($data);
    }


    public function updateSearchList($searchListId, $data)
    {
        $table = new Db_SearchList();

        $where = $this->db->quoteInto(Db_SearchList::ID . ' =?', $searchListId);
        return $table->update($data, $where);
    }

    public function updateSearchListStatus($searchListId, $active = '0')
    {
        $data                           = array();
        $data[Db_SearchList::IS_ACTIVE] = $active;

        $table = new Db_SearchList();
        $where = $this->db->quoteInto(Db_SearchList::TABLE_NAME . '.' . Db_SearchList::ID . ' =?', $searchListId);

        $table->update($data, $where);
    }

    public function deleteSearchListAttributes($searchListId)
    {
        $table = new Db_SearchListAttribute();
        $where = $this->db->quoteInto(Db_SearchListAttribute::SEARCH_LIST_ID . ' =?', $searchListId);
        return $table->delete($where);
    }

    public function insertSearchListAttributes($searchListId, $attributeId, $orderNumber, $columnWidth = null)
    {
        $table = new Db_SearchListAttribute();

        $data                                         = array();
        $data[Db_SearchListAttribute::ORDER_NUMBER]   = $orderNumber;
        $data[Db_SearchListAttribute::SEARCH_LIST_ID] = $searchListId;
        $data[Db_SearchListAttribute::ATTRIBUT_ID]    = $attributeId;
        $data[Db_SearchListAttribute::COLUMN_WIDTH]   = $columnWidth;

        return $table->insert($data);
    }

    public function deleteSearchList($searchListId)
    {
        $table = new Db_SearchList();
        $where = $this->db->quoteInto(Db_SearchList::ID . ' =?', $searchListId);
        return $table->delete($where);
    }
}