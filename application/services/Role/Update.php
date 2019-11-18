<?php

/**
 *
 *
 *
 */
class Service_Role_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2203, $themeId);
    }


    public function getUsers()
    {
        $userDaoImpl = new Dao_User();
        return $userDaoImpl->getUsers();
    }

    public function getAttributes()
    {
        $attributeDaoImpl = new Dao_Attribute();
        return $attributeDaoImpl->getAllAttributes();
    }

    public function getUpdateRoleForm($users, $attributes)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/role.ini', APPLICATION_ENV);
        $form   = new Form_Role_Update($this->translator, $config);

        foreach ($users as $user) {
            $form->addUser($user[Db_User::ID], $user[Db_User::USERNAME], $user[Db_User::DESCRIPTION]);
        }

        foreach ($attributes as $attribute) {
            $form->addAttribute($attribute[Db_Attribute::ID], $attribute[Db_Attribute::NAME], $attribute[Db_Attribute::DESCRIPTION]);
        }

        return $form;
    }


    public function updateRole($roleId, $formData, $dbData, $userId)
    {
        try {
            $dbUpdate = false;

            foreach ($formData as $key => $value) {
                if ($formData[$key] != $dbData[$key])
                    $updateData[$key] = $value;
            }

            $role = array();
            if ($updateData['name'] !== null)
                $role[Db_Role::NAME] = trim($updateData['name']);
            if ($updateData['description'] !== null)
                $role[Db_Role::DESCRIPTION] = trim($updateData['description']);
            if ($updateData['note'] !== null)
                $role[Db_Role::NOTE] = trim($updateData['note']);

            if (!empty($role)) {
                $role[Db_Role::USER_ID] = $userId;
                $roleDaoImpl            = new Dao_Role();
                $ret                    = $roleDaoImpl->updateRole($roleId, $role);
                $dbUpdate               = true;
            }

            try {
                $mapping = $formData;

                $attributeDaoImpl = new Dao_Attribute();
                $userDaoImpl      = new Dao_User();
                foreach ($mapping as $id => $value) {
                    if (strpos($id, 'attributeId_') === 0 && $dbData[$id] != $value) {
                        if ($value == '0') {
                            $read  = 0;
                            $write = 0;
                        } elseif ($value == '1') {
                            $read  = 1;
                            $write = 0;
                        } elseif ($value == '2') {
                            $read  = 1;
                            $write = 1;
                        }

                        $attributeId = substr($id, strlen('attributeId_'));

                        $updated = $attributeDaoImpl->updateAttributeRole($attributeId, $roleId, $read, $write);
                        if (!$updated || $updated == 0 || $updated > 1) {
                            $attributeDaoImpl->deleteAttributeRole($roleId, $attributeId);
                            $attributeDaoImpl->insertRolesByAttributeId($attributeId, $roleId, $read, $write);
                        }

                        $dbUpdate = true;
                    } elseif (strpos($id, 'userId_') === 0 && $dbData[$id] != $value) {
                        if ($value) {
                            $userDaoImpl->updateUserRoleMapping(substr($id, strlen('userId_')), $roleId);
                            $dbUpdate = true;
                        } else {
                            $userDaoImpl->deleteUserRoleMapping(substr($id, strlen('userId_')), $roleId);
                            $dbUpdate = true;
                        }
                    }
                }
            } catch (Exception $e) {
                throw new Exception_Role_UpdateMappingFailed($e);
            }

            return $dbUpdate;
        } catch (Exception_Role $e) {
            throw new Exception_Role_UpdateItemNotFound($e);
        } catch (Exception $e) {
            if ($e instanceof Exception_Role)
                throw $e;
            throw new Exception_Role_UpdateFailed($e);
        }
    }
}