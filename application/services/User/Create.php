<?php

/**
 *
 *
 *
 */
class Service_User_Create extends Service_Abstract
{

    private static $userNamespace = 'userinsert';

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2602, $themeId);
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

    public function getCreateUserForm($projects, $roles)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);

        $themeDaoImpl = new Dao_Theme();
        $themeList    = $themeDaoImpl->getThemes();

        $newThemeList       = array();
        $newThemeList[null] = $this->translator->translate('pleaseChose');
        foreach ($themeList as $theme) {
            $newThemeList[$theme[Db_Theme::ID]] = $theme[Db_Theme::DESCRIPTION];
        }

        $layouts = array();

        // select available layouts
        $dir = APPLICATION_PATH . '/modules/cmdb/layouts';
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_file($dir .'/'. $file) && substr($file, 0, 1) != '.') {
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
        $form = new Form_User_Create($this->translator, $config, $newThemeList, $layouts);

        foreach ($projects as $project) {

            if ($project[Db_Project::IS_ACTIVE] == 1) {
                $form->addAttribute('projectId_', $project[Db_Project::ID], $project[Db_Project::NAME], $project[Db_Project::DESCRIPTION]);
            } else { # readOnly
                $form->addAttribute('projectId_', $project[Db_Project::ID], $project[Db_Project::NAME], $project[Db_Project::DESCRIPTION], true);
            }
        }

        foreach ($roles as $role) {
            $form->addAttribute('roleId_', $role[Db_Role::ID], $role[Db_Role::NAME], $role[Db_Role::DESCRIPTION]);
        }

        return $form;
    }

    public function createUser($formData, $userId)
    {
        $crypt       = new Util_Crypt();
        $user_config = new Util_Config('forms/user.ini', APPLICATION_ENV);
        try {
            $userData                                    = array();
            $userData[Db_User::THEME_ID]                 = $formData['theme'];
            $userData[Db_User::USERNAME]                 = trim($formData['name']);
            $userData[Db_User::EMAIL]                    = trim($formData['email']);
            $userData[Db_User::PASSWORD]                 = trim($formData['password']);
            $userData[Db_User::PASSWORD_EXPIRE_OFF]      = trim($formData['password_expire_off']);
            $userData[Db_User::FIRSTNAME]                = trim($formData['firstname']);
            $userData[Db_User::LASTNAME]                 = trim($formData['lastname']);
            $userData[Db_User::DESCRIPTION]              = trim($formData['description']);
            $userData[Db_User::NOTE]                     = trim($formData['note']);
            $userData[Db_User::LANGUAGE]                 = $formData['language'];
            $userData[Db_User::LAYOUT]                   = $formData['layout'];
            $userData[Db_User::IS_CI_DELETE_ENABLED]     = $formData['ciDelete'];
            $userData[Db_User::IS_RELATION_EDIT_ENABLED] = $formData['relationDelete'];
            $userData[Db_User::IS_LDAP_AUTH]             = $formData['ldapAuth'];
            $userData[Db_User::IS_ACTIVE]                = '1';
            $userData[Db_User::IS_ROOT]                  = $formData['isRoot'];
            $userData[Db_User::USER_ID]                  = $userId;

            $now                                 = new DateTime();
            $userData[Db_User::PASSWORD_CHANGED] = $now->format("Y-m-d H:i:s");

            $userDaoImpl = new Dao_User();
            $userId      = $userDaoImpl->insertUser($userData);
            if (!$userId) {
                throw new Exception();
            } else {
                try {
                    $mapping = $formData;

                    $projectDaoImpl = new Dao_Project();
                    $roleDaoImpl    = new Dao_Role();
                    foreach ($mapping as $id => $value) {
                        if (strpos($id, 'projectId_') === 0) {
                            if ($value)
                                $projectDaoImpl->addProjectMapping($userId, substr($id, strlen('projectId_')));
                        } elseif (strpos($id, 'roleId_') === 0) {
                            if ($value)
                                $roleDaoImpl->addRoleMapping($userId, substr($id, strlen('roleId_')));
                        }
                    }
                } catch (Exception $e) {
                    throw new Exception_User_MappingInsertFailed($e);
                }
            }
        } catch (Exception $e) {
            throw new Exception_User_InsertFailed($e);
        }
    }
}