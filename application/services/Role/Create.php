<?php

/**
 *
 *
 *
 */
class Service_Role_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2202, $themeId);
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

    public function getCreateRoleForm($users, $attributes)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/role.ini', APPLICATION_ENV);
        $form   = new Form_Role_Create($this->translator, $config);

        foreach ($users as $user) {
            $form->addUser($user[Db_User::ID], $user[Db_User::USERNAME], $user[Db_User::DESCRIPTION]);
        }

        foreach ($attributes as $attribute) {
            $form->addAttribute($attribute[Db_Attribute::ID], $attribute[Db_Attribute::NAME], $attribute[Db_Attribute::DESCRIPTION]);
        }

        return $form;
    }


    /**
     * creates a Role by the given values
     *
     * @param array $values
     */
    public function createRole($formData, $userId)
    {
        try {
            $role                          = array();
            $role[Db_Project::NAME]        = trim($formData['name']);
            $role[Db_Project::DESCRIPTION] = trim($formData['description']);
            $role[Db_Project::NOTE]        = trim($formData['note']);
            $role[Db_Project::IS_ACTIVE]   = '1';
            $role[Db_Project::USER_ID]     = $userId;

            $roleDaoImpl = new Dao_Role();
            $roleId      = $roleDaoImpl->insertRole($role);

            if (!$roleId) {
                throw new Exception();
            } else {
                try {
                    $mapping = $formData;

                    $userDaoImpl      = new Dao_User();
                    $attributeDaoImpl = new Dao_Attribute();
                    foreach ($mapping as $id => $value) {
                        if (strpos($id, 'userId_') === 0) {
                            if ($value)
                                $userDaoImpl->updateUserRoleMapping(substr($id, strlen('userId_')), $roleId);
                        } elseif (strpos($id, 'attributeId_') === 0) {
                            $read  = 0;
                            $write = 0;

                            if ($value === '2') {
                                $read  = 1;
                                $write = 1;
                            } else if ($value === '1') {
                                $read = 1;
                            }
                            $attributeDaoImpl->insertRolesByAttributeId(substr($id, strlen('attributeId_')), $roleId, $read, $write);
                        }
                    }
                } catch (Exception $e) {
                    throw new Exception_Role_InsertFailed($e);
                }
                return $roleId;
            }
        } catch (Exception $e) {
            throw new Exception_Role_InsertFailed($e);
        }
    }
}