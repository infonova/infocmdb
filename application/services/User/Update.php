<?php

/**
 *
 *
 *
 */
class Service_User_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2603, $themeId);
    }

    public function getProjects()
    {
        $projectDaoImpl = new Dao_Project();
        return $projectDaoImpl->getProjects();
    }

    public function getRoles()
    {
        $roleDaoImpl = new Dao_Role();
        return $roleDaoImpl->getRoles();
    }


    /**
     * creates a User by the given values
     *
     * @param array $user
     */
    public function updateUser($userId, $formData, $dbData, $currentUserId, $skipMapping = false)
    {
        try {
            foreach ($formData as $key => $value) {
                if ($formData[$key] !== $dbData[$key])
                    $updateData[$key] = $value;
            }


            $user = array();
            if ($updateData['theme'] !== null) {
                $user[Db_User::THEME_ID] = $updateData['theme'];
            }
            if ($updateData['name'] !== null) {
                $user[Db_User::USERNAME] = trim($updateData['name']);
            }
            if ($updateData['email'] !== null) {
                $user[Db_User::EMAIL] = trim($updateData['email']);
            }
            if ($updateData['password']) {
                $user[Db_User::PASSWORD]         = trim($updateData['password']);
                $now                             = new DateTime();
                $user[Db_User::PASSWORD_CHANGED] = $now->format("Y-m-d H:i:s");
            }
            if ($updateData['firstname'] !== null) {
                $user[Db_User::FIRSTNAME] = trim($updateData['firstname']);
            }
            if ($updateData['lastname'] !== null) {
                $user[Db_User::LASTNAME] = trim($updateData['lastname']);
            }
            if ($updateData['description'] !== null) {
                $user[Db_User::DESCRIPTION] = trim($updateData['description']);
            }
            if ($updateData['note'] !== null) {
                $user[Db_User::NOTE] = trim($updateData['note']);
            }
            if ($updateData['language'] !== null) {
                $user[Db_User::LANGUAGE] = $updateData['language'];
            }
            if ($updateData['layout'] !== null) {
                $user[Db_User::LAYOUT] = $updateData['layout'];
            }
            if ($updateData['ciDelete'] !== null) {
                $user[Db_User::IS_CI_DELETE_ENABLED] = $updateData['ciDelete'];
            }
            if ($updateData['relationDelete'] !== null) {
                $user[Db_User::IS_RELATION_EDIT_ENABLED] = $updateData['relationDelete'];
            }
            if ($updateData['ldapAuth'] !== null) {
                $user[Db_User::IS_LDAP_AUTH] = $updateData['ldapAuth'];
            }
            if ($updateData['isRoot'] !== null) {
                $user[Db_User::IS_ROOT] = $updateData['isRoot'];
            }
            if ($updateData['is_two_factor_auth'] !== null) {
                $user[Db_User::IS_TWO_FACTOR_AUTH] = $updateData['is_two_factor_auth'];
            }
            if ($updateData[Db_User::SECRET] !== null) {
                $user[Db_User::SECRET] = $updateData['secret'];
                // admin needs abillity to set SECRET to NULL -> workaround with SECRET = FALSE
                if ($updateData[Db_User::SECRET] === false) {
                    $user[Db_User::SECRET] = null;
                }
            }
            if ($updateData['password_expire_off'] !== null) {
                $user[Db_User::PASSWORD_EXPIRE_OFF] = $updateData['password_expire_off'];
            }
            if (!empty($user)) {
                $userDaoImpl = new Dao_User();
                if ($updateData[Db_User::PASSWORD]) {
                    $user_settings                              = array();
                    $user_settings['password_maxage_mail_sent'] = false;

                    $user[Db_User::SETTINGS] = Dao_User::editSettingString($dbData[Db_User::SETTINGS], $user_settings);

                }
                $userDaoImpl->updateUser($user, $userId);
            }

            if ($skipMapping === false) {
                try {
                    $mapping = $formData;

                    $projectDaoImpl = new Dao_Project();
                    $roleDaoImpl    = new Dao_Role();

                    $this->logger->log('remove project/role mapping for user: ' . $userId, Zend_Log::INFO);
                    $projectDaoImpl->deleteProjectMappingByUserId($userId);
                    $roleDaoImpl->deleteRoleMappingByUserId($userId);

                    foreach ($mapping as $id => $value) {
                        if (strpos($id, 'projectId_') === 0) {
                            if ($value === '1') {
                                $this->logger->log('add project mapping for user: ' . $userId . ', project: ' . substr($id, strlen('projectId_')), Zend_Log::INFO);
                                $projectDaoImpl->addProjectMapping($userId, substr($id, strlen('projectId_')));
                            }
                        } elseif (strpos($id, 'roleId_') === 0) {
                            if ($value === '1') {
                                $this->logger->log('add role mapping for user: ' . $userId . ', role: ' . substr($id, strlen('roleId_')), Zend_Log::INFO);
                                $roleDaoImpl->addRoleMapping($userId, substr($id, strlen('roleId_')));
                            }
                        }
                    }
                } catch (Exception $e) {
                    throw new Exception_User_MappingInsertFailed($e);
                }
            }
        } catch (Exception $e) {
            throw new Exception_User_UpdateFailed($e);
        }
    }


    public function getUpdateUserForm($projects, $roles)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);

        $themeDaoImpl = new Dao_Theme();
        $themeList    = $themeDaoImpl->getThemes();

        $newThemeList    = array();
        $newThemeList[0] = " ";
        foreach ($themeList as $theme) {
            $newThemeList[$theme[Db_Theme::ID]] = $theme[Db_Theme::DESCRIPTION];
        }

        $layouts = array();

        // select available layouts
        $dir = APPLICATION_PATH . '/modules/cmdb/layouts';
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_file($dir .'/'. $file) && substr($file, 0, 1) != '.' && substr($file, 0, 1) != '_') {
                        $fileType = explode(".", $file);
                        if ($fileType[1] == "phtml") {
                            $layouts[$fileType[0]] = $fileType[0];
                        }

                    }
                }
                closedir($dh);
            }
        }
        unset($layouts['popup']);
        unset($layouts['layout']);
        unset($layouts['clean']);
        unset($layouts['login']);
        unset($layouts['print']);
        $form = new Form_User_Update($this->translator, $config, $newThemeList, $layouts);

        foreach ($projects as $project) {
            if ($project[Db_Project::IS_ACTIVE] == 1) {
                $form->addAttribute('projectId_', $project[Db_Project::ID], $project[Db_Project::NAME], $project[Db_Project::DESCRIPTION]);
            } else {#readOnly
                $form->addAttribute('projectId_', $project[Db_Project::ID], $project[Db_Project::NAME], $project[Db_Project::DESCRIPTION], true);
            }
        }

        foreach ($roles as $role) {
            $form->addAttribute('roleId_', $role[Db_Role::ID], $role[Db_Role::NAME], $role[Db_Role::DESCRIPTION]);
        }

        return $form;
    }

    /**
     * Inserts the keys and values in $settings into the user setting json string.
     *
     * Expects $settings to be a parsed json string (associative array)
     * $settings = json_decode($json, true);
     *
     * Returns null if $settings is invalid, the current (updated) user settings json string otherwise.
     *
     * @param $userId   int
     * @param $settings array
     *
     * @return null|string
     */
    public function setUserSettings($userId, $settings)
    {
        $userDaoImpl = new Dao_User();
        try {
            return $userDaoImpl->editUserSettings($userId, $settings);
        } catch (Exception $e) {
            throw new Exception_User_UpdateFailed($e);
        }
    }

}
