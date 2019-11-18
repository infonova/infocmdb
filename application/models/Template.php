<?php

class Dao_Template extends Dao_Abstract
{


    public function getTemplatesForPagination($orderBy = null, $direction = null, $filter = null)
    {
        $select = $this->db->select()
            ->from(Db_Templates::TABLE_NAME);

        if ($filter)
            $select->where(Db_Templates::TABLE_NAME . '.' . Db_Templates::NAME . ' LIKE "%' . $filter . '%"');


        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_Templates::ID . ' DESC');
        }

        return $select;
    }


    public function deleteTemplate($templateId)
    {
        $table = new Db_Templates();
        $where = $this->db->quoteInto(Db_Templates::ID . ' = ?', $templateId);
        return $table->delete($where);
    }

    public function insertTemplate($template)
    {
        $table = new Db_Templates();
        return $table->insert($template);
    }


    public function getTemplates()
    {
        $select = $this->db->select()
            ->from(Db_Templates::TABLE_NAME);

        return $this->db->fetchAll($select);
    }

    public function getTemplate($id)
    {
        $select = $this->db->select()
            ->from(Db_Templates::TABLE_NAME)
            ->where(Db_Templates::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }
}