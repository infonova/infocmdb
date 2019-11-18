<?php

/**
 *
 *
 *
 */
class Service_User_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2601, $themeId);
    }


    /**
     * retrieves a single User
     *
     * @param int $userId
     */
    public function getUser($userId)
    {
        try {
            $userDaoImpl = new Dao_User();
            $ret         = $userDaoImpl->getUser($userId);
            if (!$ret) {
                throw new Exception_User_RetrieveNotFound();
            }
            return $ret;
        } catch (Exception $e) {
            if ($e instanceof Exception_User)
                throw $e;
            throw new Exception_User_RetrieveFailed();
        }

    }

    /**
     * retrieves all necessary Data for a user
     *
     * @param int $projectId
     */
    public function getUserData($userId)
    {
        try {
            $userDaoImpl = new Dao_User();
            $user        = $userDaoImpl->getUser($userId);

            if (!$user) {
                throw new Exception_User_RetrieveNotFound();
            }

            $dbFormData                        = array();
            $dbFormData['theme']               = $user[Db_User::THEME_ID];
            $dbFormData['name']                = trim($user[Db_User::USERNAME]);
            $dbFormData['email']               = trim($user[Db_User::EMAIL]);
            $dbFormData['password']            = trim($user[Db_User::PASSWORD]);
            $dbFormData['firstname']           = trim($user[Db_User::FIRSTNAME]);
            $dbFormData['lastname']            = trim($user[Db_User::LASTNAME]);
            $dbFormData['description']         = trim($user[Db_User::DESCRIPTION]);
            $dbFormData['note']                = trim($user[Db_User::NOTE]);
            $dbFormData['language']            = $user[Db_User::LANGUAGE];
            $dbFormData['layout']              = $user[Db_User::LAYOUT];
            $dbFormData['ciDelete']            = $user[Db_User::IS_CI_DELETE_ENABLED];
            $dbFormData['relationDelete']      = $user[Db_User::IS_RELATION_EDIT_ENABLED];
            $dbFormData['ldapAuth']            = $user[Db_User::IS_LDAP_AUTH];
            $dbFormData['isRoot']              = $user[Db_User::IS_ROOT];
            $dbFormData['is_two_factor_auth']  = $user[Db_User::IS_TWO_FACTOR_AUTH];
            $dbFormData['secret']              = $user[Db_User::SECRET];
            $dbFormData['password_expire_off'] = $user[Db_User::PASSWORD_EXPIRE_OFF];
            $dbFormData[Db_User::SETTINGS]     = $user[Db_User::SETTINGS];


            $projects = $userDaoImpl->getAllUserProjectMapping($userId);
            if ($projects) {
                foreach ($projects as $project) {
                    $dbFormData['projectId_' . $project[Db_UserProject::PROJECT_ID]] = true;
                }
            }

            $roles = $userDaoImpl->getAllUserRoleMapping($userId);
            if ($roles) {
                foreach ($roles as $role) {
                    $dbFormData['roleId_' . $role[Db_UserRole::ROLE_ID]] = true;
                }
            }

            return $dbFormData;
        } catch (Exception $e) {
            if ($e instanceof Exception_User)
                throw $e;
            throw new Exception_User_RetrieveFailed();
        }
    }


    public function getRoles($userId)
    {
        try {
            $roleDaoImpl = new Dao_Role();
            return $roleDaoImpl->getCurrentRolesForUser($userId);
        } catch (Exception_User $e) {
            throw new Exception_User_RetrieveNotFound($e);
        } catch (Exception $e) {
            if ($e instanceof Exception_User)
                throw $e;
            throw new Exception_User_RetrieveFailed($e);
        }
    }

    public function getProjects($userId)
    {
        try {
            $projectDaoImpl = new Dao_Project();
            return $projectDaoImpl->getProjectsByUserId($userId);
        } catch (Exception_User $e) {
            throw new Exception_User_RetrieveNotFound($e);
        } catch (Exception $e) {
            if ($e instanceof Exception_User)
                throw $e;
            throw new Exception_User_RetrieveFailed($e);
        }
    }


    /**
     * retrieves a list of ci types by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getUserList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $this->logger->log("Service_Citype: getCitypeList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/user.ini', APPLICATION_ENV);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['user'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        $userDaoImpl = new Dao_User();

        $form   = new Form_Filter($this->translator);
        $select = array();
        if ($filter) {
            $select                = $userDaoImpl->getUsersForPaginatorWithFilter($filter, $orderBy, $direction);
            $filterArray           = array();
            $filterArray['search'] = $filter;
            $form->populate($filterArray);
        } else {
            $select = $userDaoImpl->getUsersForPaginator($orderBy, $direction);
        }

        unset($userDaoImpl);


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $result               = array();
        $result['searchForm'] = $form;
        $result['paginator']  = $paginator;
        return $result;
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
        $user_dao = new Dao_User();
        return $user_dao->isPasswordExpired($userId, $user);
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
        $user_dao = new Dao_User();
        return $user_dao->isLoginAllowed($userId, $user);
    }

    public function getUserSettings($userId)
    {
        $userDaoImpl = new Dao_User();
        $settings    = $userDaoImpl->getUserSettings($userId);
        return $settings;
    }
}