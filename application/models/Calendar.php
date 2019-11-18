<?php

class Dao_Calendar extends Dao_Abstract
{


    public function getEventsByTime($timeFrom, $timeTo, $userId = null, $projectId = null, $limit = null)
    {
        $select = $this->db->select()
            ->distinct()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array(Db_Attribute::DESCRIPTION))
            ->join(Db_Ci::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID, array(Db_Ci::CI_TYPE_ID))
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_EVENT . ' =?', '1')
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DATE . ' BETWEEN "' . $timeFrom . '" AND "' . $timeTo . '"');
        if (!is_null($projectId)) {
            $select->join(Db_CiProject::TABLE_NAME, Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID, array());
            $select->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID . ' = ?', $projectId);
        }


        if ($userId) {
            $subSelect = $this->db->select()
                ->from(Db_UserRole::TABLE_NAME, array())
                ->join(Db_AttributeRole::TABLE_NAME, Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ROLE_ID . ' = ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID, array(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::ATTRIBUTE_ID))
                ->where(Db_AttributeRole::TABLE_NAME . '.' . Db_AttributeRole::PERMISSION_READ . ' =?', 1)
                ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . '=?', $userId);
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' IN(' . $subSelect . ')');
        }

        $select->order(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DATE . ' ASC');

        if ($limit)
            $select->limit($limit);

        return $this->db->fetchAll($select);
    }

    public function updateTodoItem($id, $data)
    {
        $table = new Db_TodoItems();
        $where = $this->db->quoteInto(Db_TodoItems::TABLE_NAME . '.' . Db_TodoItems::ID . ' = ?', $id);

        return $table->update($data, $where);

    }

    public function getTodoList($userId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array(Db_Attribute::ATTRIBUTE_TYPE_ID, Db_Attribute::IS_TODO_ITEM))
            ->join(Db_TodoItems::TABLE_NAME, Db_TodoItems::TABLE_NAME . '.' . Db_TodoItems::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ID)
            ->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_TODO_ITEM . '="1"')
            ->where(Db_TodoItems::TABLE_NAME . '.' . Db_TodoItems::USER_ID . ' =?', $userId)
            ->where(Db_TodoItems::TABLE_NAME . '.' . Db_TodoItems::STATUS . ' != "deleted"')
            ->order(Db_TodoItems::TABLE_NAME . '.' . Db_TodoItems::STATUS)
            ->order(Db_TodoItems::TABLE_NAME . '.' . Db_TodoItems::PRIORITY . ' desc')
            ->order(Db_TodoItems::TABLE_NAME . '.' . Db_TodoItems::CREATED);

        return $this->db->fetchAll($select);
    }

    public function createTodoItem($ciAttributeId, $userId)
    {
        $table = new Db_TodoItems();

        $todoItem                        = array();
        $todoItem[Db_TodoItems::ID]      = $ciAttributeId;
        $todoItem[Db_TodoItems::USER_ID] = $userId;

        return $table->insert($todoItem);
    }
}