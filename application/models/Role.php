<?php

class Dao_Role extends Dao_Abstract
{


    const TABLE_NAME = 'role';

    // define db attributes
    const ID          = 'id';
    const NAME        = 'name';
    const DESCRIPTION = 'description';
    const NOTE        = 'note';
    const VALID       = 'valid';

    public function getRole($id)
    {
        $select = $this->db->select()
            ->from(Db_Role::TABLE_NAME)
            ->where(Db_Role::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }

    public function getRolesForPaginator($orderBy = null, $direction = null, $filter = null)
    {
        $table  = new Db_Role();
        $select = $table->select();

        if ($filter) {
            $select = $select
                ->where(Db_Role::TABLE_NAME . '.' . Db_Role::NAME . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Role::TABLE_NAME . '.' . Db_Role::DESCRIPTION . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else $select->order(Db_Role::NAME);

        return $select;
    }

    public function getRolesForUserMapping($userId)
    {
        $select = $this->db->select()
            ->from(Db_Role::TABLE_NAME, array(Db_Role::ID, Db_Role::NAME, Db_Role::DESCRIPTION))
            ->joinLeft(Db_UserRole::TABLE_NAME, Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' = ' . Db_Role::TABLE_NAME . '.' . Db_Role::ID . ' AND ' . Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . ' = ' . $userId, array(Db_UserRole::USER_ID));

        return $this->db->fetchAll($select);
    }

    public function getCurrentRolesForUser($userId)
    {
        $select = $this->db->select()
            ->from(Db_Role::TABLE_NAME, array(Db_Role::ID, Db_Role::NAME, Db_Role::DESCRIPTION))
            ->joinLeft(Db_UserRole::TABLE_NAME, Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' = ' . Db_Role::TABLE_NAME . '.' . Db_Role::ID, array(Db_UserRole::USER_ID))
            ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . ' = ' . $userId);

        return $this->db->fetchAll($select);
    }

    public function deleteRoleMappingByUserId($userId)
    {
        $table = new Db_UserRole();

        $where = $this->db->quoteInto(Db_UserRole::USER_ID . ' =?', $userId);
        $table->delete($where);
    }

    public function deleteRoleMapping($userId, $roleId)
    {
        $table = new Db_UserRole();

        $where = array(
            $this->db->quoteInto(Db_UserRole::USER_ID . ' = ?', $userId),
            $this->db->quoteInto(Db_UserRole::ROLE_ID . ' =?', $roleId),
        );
        $table->delete($where);
    }

    public function addRoleMapping($userId, $roleId)
    {
        $table = new Db_UserRole();

        $data                       = array();
        $data[Db_UserRole::USER_ID] = $userId;
        $data[Db_UserRole::ROLE_ID] = $roleId;

        $table->insert($data);
    }

    public function deleteRole($id)
    {
        $table = new Db_Role();
        $where = $table->getAdapter()->quoteInto(Db_Role::ID . ' = ?', $id);
        return $table->delete($where);
    }

    public function deleteUserRole($roleId)
    {
        $table = new Db_UserRole();
        $where = $table->getAdapter()->quoteInto(Db_UserRole::ROLE_ID . ' = ?', $roleId);
        return $table->delete($where);
    }

    public function deactivateRole($roleId)
    {
        $table = new Db_Role();

        $data = array(
            Db_Role::IS_ACTIVE => '0',
        );

        $where = $this->db->quoteInto(Db_Role::ID . ' = ?', $roleId);
        return $table->update($data, $where);
    }

    public function activateRole($roleId)
    {
        $table = new Db_Role();

        $data = array(
            Db_Role::IS_ACTIVE => '1',
        );

        $where = $this->db->quoteInto(Db_Role::ID . ' = ?', $roleId);
        return $table->update($data, $where);
    }

    public function countUserRole($roleId)
    {
        $select = $this->db->select()
            ->from(Db_UserRole::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_UserRole::ROLE_ID . ' = ?', $roleId);

        return $this->db->fetchRow($select);
    }

    public function deleteAttributeRole($roleId)
    {
        $table = new Db_AttributeRole();
        $where = $table->getAdapter()->quoteInto(Db_AttributeRole::ROLE_ID . ' = ?', $roleId);
        return $table->delete($where);
    }

    public function countAttributeRole($roleId)
    {
        $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_AttributeRole::TABLE_NAME . "
				   WHERE " . Db_AttributeRole::ROLE_ID . " = '" . $roleId . "'
				   AND (." . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::PERMISSION_READ . " = '1' OR " . Db_AttributeRole::TABLE_NAME . "." . Db_AttributeRole::PERMISSION_WRITE . " = '1')";

        return $this->db->fetchRow($select);
    }

    public function insertRole($role)
    {
        $table = new Db_Role();
        return $table->insert($role);
    }


    public function updateRole($id, $role)
    {
        $table = new Db_Role();
        $where = $this->db->quoteInto(Db_Role::ID . ' =?', $id);
        return $table->update($role, $where);
    }


    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_Role::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_Role::NAME . ' LIKE ?', $value);

        if($id > 0) {
            $select->where(Db_Role::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }

    public function getRoles($orderBy = null, $direction = null)
    {

        $table  = new Db_Role();
        $select = $table->select()->where(Db_Role::IS_ACTIVE . ' =?', '1');

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;
            $select->order($orderBy);
        } else $select->order(Db_Role::NAME, ' ASC');

        return $table->fetchAll($select);
    }
}