<?php

class Dao_User extends Dao_Abstract
{


    public function getUsersPaginationAdapter()
    {

        $adapter = new Zend_Paginator_Adapter_DbSelect($db->select()->from(Db_User::TABLE_NAME));
        $adapter->setRowCount(
            $db->select()
                ->from(
                    User::TABLE_NAME,
                    array(
                        Zend_Paginator_Adapter_DbSelect::ROW_COUNT_COLUMN => Db_User::ID,
                    )
                )
        );

        return $adapter;
    }

    public function getUsers()
    {
        $select = $this->db
            ->select()
            ->from(Db_User::TABLE_NAME,
                array(Db_User::ID,
                    Db_User::USERNAME,
                    Db_User::LASTNAME,
                )
            )
            ->where(Db_User::TABLE_NAME . '.' . Db_User::USERNAME . '!=' . '"-"')
            ->order(Db_User::TABLE_NAME . '.' . Db_User::LASTNAME);
        $select->where(Db_User::TABLE_NAME . '.' . Db_User::USERNAME . '!=' . '""');

        return $this->db->fetchAll($select);
    }

    public function getActiveUsers()
    {
        $table  = new Db_User();
        $select = $table->select()->where(Db_User::IS_ACTIVE . ' =?', '1');

        return $table->fetchAll($select);
    }

    public function deleteUsers($userId)
    {
        $table = new Db_User();
        $where = $this->db->quoteInto(Db_User::ID . ' = ?', $userId);

        return $table->delete($where);
    }

    public function deactivateUser($userId)
    {
        $table = new Db_User();

        $data = array(
            Db_User::IS_ACTIVE => '0',
        );

        $where = $this->db->quoteInto(Db_User::ID . ' = ?', $userId);
        return $table->update($data, $where);
    }

    public function activateUser($userId)
    {
        $table = new Db_User();

        $data = array(
            Db_User::IS_ACTIVE => '1',
        );

        $where = $this->db->quoteInto(Db_User::ID . ' = ?', $userId);
        return $table->update($data, $where);
    }

    public function getSingleUser($userId)
    {
        $table  = new Db_User();
        $select = $table->select()
            ->where(Db_User::ID . ' =?', $userId);

        return $table->fetchRow($select);
    }

    public function getUserByRoleId($roleId)
    {
        $select = $this->db->select()
            ->from(Db_User::TABLE_NAME)
            ->join(Db_UserRole::TABLE_NAME, Db_UserRole::TABLE_NAME . '.' . Db_UserRole::USER_ID . ' = ' . Db_User::TABLE_NAME . '.' . Db_User::ID, array(Db_UserRole::ROLE_ID))
            ->where(Db_UserRole::TABLE_NAME . '.' . Db_UserRole::ROLE_ID . ' =?', $roleId);

        return $this->db->fetchAll($select);
    }

    public function getUser($userId)
    {
        $select = $this->db->select()
            ->from(Db_User::TABLE_NAME)
            ->where(Db_User::TABLE_NAME . '.' . Db_User::ID . ' =?', $userId);

        return $this->db->fetchRow($select);
    }

    public function getUserByUsername($username)
    {
        $select = $this->db->select()
            ->from(Db_User::TABLE_NAME)
            ->where(Db_User::TABLE_NAME . '.' . Db_User::USERNAME . ' LIKE ?', $username);

        return $this->db->fetchRow($select);
    }

    public function updateUserPassword($userId, $password, $encrypt_password = 1)
    {
        $table  = new Db_User();
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

        if ($config->auth->password->encryption == 1
            && $encrypt_password == 1) {
            $crypt    = new Util_Crypt();
            $password = $crypt->create_hash($password);
        }

        $data = array(
            Db_User::PASSWORD => $password,
        );

        $where = $this->db->quoteInto(Db_User::ID . '=?', $userId);
        return $table->update($data, $where);
    }


    public function getUsersForPaginator($orderBy = null, $direction = null)
    {

        $select = $this->db->select()
            ->from(Db_User::TABLE_NAME)
            ->join(Db_Theme::TABLE_NAME, Db_User::TABLE_NAME . '.' . Db_User::THEME_ID . ' = ' . Db_Theme::TABLE_NAME . '.' . Db_Theme::ID, array('themeName' => Db_Theme::DESCRIPTION));

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else $select->order(Db_User::USERNAME . ' ASC');

        return $select;
    }


    public function getUsersForPaginatorWithFilter($filter, $orderBy = null, $direction = null)
    {
        $filter = $filter . '%';
        $select = $this->db->select()
            ->from(Db_User::TABLE_NAME)
            ->join(Db_Theme::TABLE_NAME, Db_User::TABLE_NAME . '.' . Db_User::THEME_ID . ' = ' . Db_Theme::TABLE_NAME . '.' . Db_Theme::ID, array('themeName' => Db_Theme::DESCRIPTION))
            ->where(Db_User::TABLE_NAME . '.' . Db_User::USERNAME . ' LIKE "%' . $filter . '%"')
            ->orWhere(Db_User::TABLE_NAME . '.' . Db_User::LASTNAME . ' LIKE "%' . $filter . '%"')
            ->orWhere(Db_User::TABLE_NAME . '.' . Db_User::FIRSTNAME . ' LIKE "%' . $filter . '%"')
            ->orWhere(Db_User::TABLE_NAME . '.' . Db_User::DESCRIPTION . ' LIKE "%' . $filter . '%"');

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        }

        return $select;
    }


    public function insertUser($userArray)
    {
        $table  = new Db_User();
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        if (isset($userArray['password']) && $config->auth->password->encryption == 1) {
            $crypt                 = new Util_Crypt();
            $userArray['password'] = $crypt->create_hash($userArray['password']);
        }
        return $table->insert($userArray);
    }

    public function updateUser($userArray, $userId)
    {
        $table  = new Db_User();
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        if (isset($userArray['password']) && $config->auth->password->encryption == 1) {
            $crypt                 = new Util_Crypt();
            $userArray['password'] = $crypt->create_hash($userArray['password']);
        }
        $where = $this->db->quoteInto(Db_User::ID . '=?', $userId);
        return $table->update($userArray, $where);
    }

    public function getUserProjectMapping($userId, $projectId)
    {
        $select = $this->db->select()
            ->from(Db_UserProject::TABLE_NAME)
            ->where(Db_UserProject::USER_ID . ' = ?', $userId)
            ->where(Db_UserProject::PROJECT_ID . ' = ?', $projectId);

        return $this->db->fetchRow($select);
    }

    public function getAllUserProjectMapping($userId)
    {
        $select = $this->db->select()
            ->from(Db_UserProject::TABLE_NAME)
            ->where(Db_UserProject::USER_ID . ' = ?', $userId);

        return $this->db->fetchAll($select);
    }

    public function getAllUserRoleMapping($userId)
    {
        $select = $this->db->select()
            ->from(Db_UserRole::TABLE_NAME)
            ->where(Db_UserRole::USER_ID . ' = ?', $userId);

        return $this->db->fetchAll($select);
    }


    public function getUserRoleMapping($userId, $roleId)
    {
        $select = $this->db->select()
            ->from(Db_UserRole::TABLE_NAME)
            ->where(Db_UserRole::USER_ID . ' = ?', $userId)
            ->where(Db_UserRole::ROLE_ID . ' = ?', $roleId);

        return $this->db->fetchRow($select);
    }


    public function getUserCiMapping($userId, $ciId)
    {
        $select = $this->db->select()
            ->from(Db_CiPermission::TABLE_NAME)
            ->where(Db_CiPermission::USER_ID . ' = ?', $userId)
            ->where(Db_CiPermission::CI_ID . ' = ?', $ciId);

        return $this->db->fetchRow($select);
    }

    public function getUserProjectCiMapping($userId, $ciId)
    {
        $select = $this->db->select()
            ->from(Db_UserProject::TABLE_NAME)
            ->join(Db_CiProject::TABLE_NAME, Db_UserProject::TABLE_NAME . '.' . Db_UserProject::PROJECT_ID . ' = ' . Db_CiProject::TABLE_NAME . '.' . Db_CiProject::PROJECT_ID, array())
            ->where(Db_UserProject::TABLE_NAME . '.' . Db_UserProject::USER_ID . ' = ?', $userId)
            ->where(Db_CiProject::TABLE_NAME . '.' . Db_CiProject::CI_ID . ' = ?', $ciId);
        return $this->db->fetchRow($select);
    }


    public function deleteUserProjectMapping($userId, $projectId)
    {
        $table = new Db_UserProject();
        $where = $this->db->quoteInto(Db_UserProject::USER_ID . ' = ' . $userId . ' AND ' . Db_UserProject::PROJECT_ID . ' =?', $projectId);

        return $table->delete($where);
    }

    public function deleteUserRoleMapping($userId, $roleId)
    {
        $table = new Db_UserRole();
        $where = $this->db->quoteInto(Db_UserRole::USER_ID . ' = ' . $userId . ' AND ' . Db_UserRole::ROLE_ID . ' =?', $roleId);

        return $table->delete($where);
    }

    public function deleteUserCiMapping($userId, $ciId)
    {
        $table = new Db_CiPermission();
        $where = $this->db->quoteInto(Db_CiPermission::USER_ID . ' = ' . $userId . ' AND ' . Db_CiPermission::CI_ID . ' =?', $ciId);

        return $table->delete($where);
    }

    public function updateUserProjectMapping($userId, $projectId)
    {
        $table = new Db_UserProject();

        $data                             = array();
        $data[Db_UserProject::USER_ID]    = $userId;
        $data[Db_UserProject::PROJECT_ID] = $projectId;

        return $table->insert($data);
    }

    public function insertUserProjectMapping($userId, $projectId)
    {
        $table = new Db_UserProject();

        $data                             = array();
        $data[Db_UserProject::USER_ID]    = $userId;
        $data[Db_UserProject::PROJECT_ID] = $projectId;

        return $table->insert($data);
    }

    public function updateUserRoleMapping($userId, $roleId)
    {
        $table = new Db_UserRole();

        $data                       = array();
        $data[Db_UserRole::USER_ID] = $userId;
        $data[Db_UserRole::ROLE_ID] = $roleId;

        return $table->insert($data);
    }

    public function updateUserCiMapping($userId, $ciId)
    {
        $table = new Db_CiPermission();

        $data                           = array();
        $data[Db_CiPermission::USER_ID] = $userId;
        $data[Db_CiPermission::CI_ID]   = $ciId;

        return $table->insert($data);
    }

    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_User::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_User::USERNAME . ' LIKE ?', $value);

        if ($id > 0) {
            $select->where(Db_User::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }

    public function getCountUserByThemeId($themeId)
    {
        $select = $this->db->select()
            ->from(Db_User::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_User::THEME_ID . ' = ?', $themeId);

        return $this->db->fetchRow($select);
    }

    public function getCountUserProjectByUserId($userId)
    {
        $select = $this->db->select()
            ->from(Db_UserProject::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_UserProject::USER_ID . ' = ?', $userId);

        return $this->db->fetchRow($select);
    }

    public function getCountUserRoleByUserId($userId)
    {
        $select = $this->db->select()
            ->from(Db_UserRole::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_UserRole::USER_ID . ' = ?', $userId);

        return $this->db->fetchRow($select);
    }

    /**
     * Helper function to check if the password of the user expired.
     *
     * If the user information is already fetched it can be passed as a parameter
     * to prevent executing another sql query.
     *
     * @param $userId int id of the user to check
     * @param $user   array array with user information to not execute another sql statement if user was already fetched
     *
     * @return bool returns true if the password is still valid false otherwise
     */
    public function isPasswordExpired($userId, $user = null)
    {
        if (is_null($user)) {
            $user = $this->getUser($userId);
        }
        if ($user[Db_User::PASSWORD_EXPIRE_OFF] == 1) {
            return false;
        }
        $now              = new DateTime();
        $password_changed = new DateTime($user[Db_User::PASSWORD_CHANGED]);
        $valid_until      = $this->getPasswordValidUntil($password_changed);
        return $now >= $valid_until;
    }

    /**
     * Helper function to check if the user is principally allowed to log in
     * based on is_active and password expiration.
     * Returns true if the user is active and his password is not expired.
     * If password expiration is not enabled, only is_active is checked.
     *
     * If the user information is already fetched it can be passed as a parameter
     * to prevent executing another sql query.
     *
     * @param $userId int id of the user to check
     * @param $user   array array with user information to not execute another sql statement if user was already fetched
     *
     * @return bool returns true if the password is still valid false otherwise
     */
    public function isLoginAllowed($userId, $user = null)
    {
        if (is_null($user)) {
            $user = $this->getUser($userId);
        }
        $user_config = new Util_Config('forms/user.ini', APPLICATION_ENV);

        $is_active         = $user[Db_User::IS_ACTIVE] == '1';
        $is_password_valid = true;

        $password_expiration_enabled = $user_config->getValue('password.maxage.enabled', false);
        $password_expiration_enabled = $password_expiration_enabled === 'true' ? true : false;
        if ($password_expiration_enabled) {
            $is_password_valid = !$this->isPasswordExpired($userId, $user);
        }
        return $is_active && $is_password_valid;
    }

    /**
     * Checks if the password of the user is about to expire. Sets the referenced parameter &$daysUntilExpiration
     * to the number of days left until the password expires.
     *
     * @param $userId              int id of the user to check
     * @param $daysUntilExpiration &int reference to a variable to store the number of days until password expires
     * @param $user                array user array if already fetched, null if not fetched already
     *
     * @return bool true if password is about to expire, false otherwise.
     *              &$daysUntilExpiration contains the number of days left until the password expires.
     */
    public function isPasswordAboutToExpire($userId, &$daysUntilExpiration, $user = null)
    {
        if (is_null($user)) {
            $user = $this->getUser($userId);
        }
        if ($user[Db_User::PASSWORD_EXPIRE_OFF] == 1) {
            return false;
        }
        $user_config = new Util_Config('forms/user.ini', APPLICATION_ENV);
        $now         = new DateTime();

        $password_changed          = new DateTime($user[Db_User::PASSWORD_CHANGED]);
        $valid_until               = $this->getPasswordValidUntil($password_changed);
        $days_before_expiration    = $user_config->getValue('password.maxage.notify.daysbefore', 18);
        $seconds_before_expiration = $days_before_expiration * 60 * 60 * 24;

        $timestamp_for_notification = $valid_until->getTimestamp();
        $timestamp_for_notification -= $seconds_before_expiration;

        $notification_time = new DateTime();
        $notification_time->setTimestamp($timestamp_for_notification);

        $daysUntilExpiration = $now->diff($valid_until)->days;
        return $now >= $notification_time;
    }

    /**
     * Helper function which calculates the datetime until the password expires
     * based on the last time the password was changed
     *
     * @param DateTime $password_changed
     *
     * @return DateTime
     */
    private function getPasswordValidUntil($password_changed)
    {
        $user_config = new Util_Config('forms/user.ini', APPLICATION_ENV);
        $valid_until = $password_changed;
        $timestamp   = $valid_until->getTimestamp();
        $max_age     = $user_config->getValue('password.maxage.days', 180);
        // seconds * 60 -> minutes * 60 -> hours * 24 -> days
        $timestamp += $max_age * 60 * 60 * 24;
        $valid_until->setTimestamp($timestamp);
        return $valid_until;
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
    public function editUserSettings($userId, $settings)
    {
        if (is_array($settings)) {
            $current_settings_json = $this->getUserSettings($userId);
            $current_settings      = json_decode($current_settings_json, true);
            if (is_null($current_settings)) {
                $current_settings = array();
            }
            foreach ($settings as $setting_key => $setting_value) {
                $current_settings[$setting_key] = $setting_value;
            }
            $current_settings_json = json_encode($current_settings);
            $this->updateUserSettings($userId, $current_settings_json);
            return $current_settings_json;
        } else {
            return null;
        }
    }


    public function getUserSettings($userId)
    {
        $select = $this->db->select()
            ->from(Db_User::TABLE_NAME, Db_User::SETTINGS)
            ->where(Db_User::ID . "= ?", $userId);
        return $this->db->fetchRow($select)[Db_User::SETTINGS];
    }

    public function updateUserSettings($userId, $settings)
    {
        $data                    = array();
        $data[Db_User::SETTINGS] = $settings;
        $where                   = $this->db->quoteInto(Db_User::ID . ' =?', $userId);

        $table = new Db_User();
        $table->update($data, $where);
    }


    /**
     * Get api_secret of user (generates and sets it if not defined)
     *
     * @param integer $userId id of user
     * @param null    $user   array with user-db-row to not execute another sql statement if user was already fetched
     * @return bool|string
     */
    public function provideApiSecret($userId, $user = null)
    {
        if (is_null($user)) {
            $user = $this->getUser($userId);
        }

        $saltLength = 10;
        $secret     = $user[Db_User::API_SECRET];
        if (!is_string($secret) || strlen($secret) !== $saltLength) {
            $crypt  = new Util_Crypt();
            $secret = $crypt->create_salt($saltLength);

            $updateData = array(
                Db_User::API_SECRET => $secret,
            );
            $this->updateUser($updateData, $user[Db_User::ID]);
        }

        return $secret;
    }

    /**
     * Updates a given json string given in $settings with the provided new key value pairs from $new_settings.
     *
     * @param $settings     string json string containing the user settings
     * @param $new_settings array an array with key to value pairs. Can contain multiple settings at once, all will be set
     *
     * @return string returns the new json string, with added new settings and unchanged (if not overwritten by new settings) old settings
     */
    public static function editSettingString($settings, $new_settings)
    {
        $settings_array = json_decode($settings, true);
        if (is_null($settings_array)) {
            $settings_array = array();
        }
        if (is_array($new_settings)) {
            foreach ($new_settings as $key => $value) {
                $settings_array[$key] = $value;
            }
        }
        return json_encode($settings_array);
    }
}