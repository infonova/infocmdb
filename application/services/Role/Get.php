<?php

/**
 *
 *
 *
 */
class Service_Role_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2201, $themeId);
    }


    /**
     * retrieves a list of roles by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getRoleList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/role.ini', APPLICATION_ENV);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['role'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $roleDaoImpl = new Dao_Role();
        $select      = $roleDaoImpl->getRolesForPaginator($orderBy, $direction, $filter);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $result              = array();
        $result['paginator'] = $paginator;
        return $result;
    }


    /**
     * @param string $filter
     */
    public function getFilterForm($filter = null)
    {
        $form = new Form_Filter($this->translator);

        if ($filter) {
            $form->populate(array('search' => $filter));
        }
        return $form;
    }

    /**
     * retrieves a single Role
     *
     * @param int $roleId
     */
    public function getRole($roleId)
    {
        try {
            $roleDaoImpl = new Dao_Role();
            $ret         = $roleDaoImpl->getRole($roleId);
            if (!$ret) {
                throw new Exception_Role_NotFound();
            }
            return $ret;
        } catch (Exception $e) {
            if ($e instanceof Exception_Role)
                throw $e;
            throw new Exception_Role_RetrieveFailed($e);
        }

    }

    /**
     * retrieves a single Role
     *
     * @param int $roleId
     */
    public function getRoleData($roleId)
    {
        try {
            $roleDaoImpl = new Dao_Role();
            $role        = $roleDaoImpl->getRole($roleId);

            if (!$role) {
                throw new Exception_Role_NotFound();
            }

            $dbFormData                = array();
            $dbFormData['name']        = trim($role[Db_Role::NAME]);
            $dbFormData['valid']       = $role[Db_Role::IS_ACTIVE];
            $dbFormData['description'] = trim($role[Db_Role::DESCRIPTION]);
            $dbFormData['note']        = trim($role[Db_Role::NOTE]);

            $userDaoImpl = new Dao_User();
            $users       = $userDaoImpl->getUserByRoleId($role[Db_Role::ID]);
            if ($users) {
                foreach ($users as $user) {
                    $dbFormData['userId_' . $user[Db_User::ID]] = 1;
                }
            }

            $attributeDaoImpl = new Dao_Attribute();
            $attributes       = $attributeDaoImpl->getAttributeRolesByRoleId($roleId);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    if ($attribute[Db_AttributeRole::PERMISSION_READ] && $attribute[Db_AttributeRole::PERMISSION_WRITE])
                        $dbFormData['attributeId_' . $attribute[Db_AttributeRole::ATTRIBUTE_ID]] = 2;
                    elseif ($attribute[Db_AttributeRole::PERMISSION_READ])
                        $dbFormData['attributeId_' . $attribute[Db_AttributeRole::ATTRIBUTE_ID]] = 1;
                }
            }

            return $dbFormData;
        } catch (Exception $e) {
            if ($e instanceof Exception_Role)
                throw $e;
            throw new Exception_Role_RetrieveFailed($e);
        }

    }

    public function getUsers($roleId)
    {
        try {
            $userDaoImpl = new Dao_User();
            $ret         = $userDaoImpl->getUserByRoleId($roleId);
            if (!$ret) {
                throw new Exception_Role_NotFound();
            }
            return $ret;
        } catch (Exception $e) {
            if ($e instanceof Exception_Role)
                throw $e;
            throw new Exception_Role_RetrieveFailed($e);
        }
    }

    public function getPermissions($roleId)
    {
        try {
            $attributeDaoImpl = new Dao_Attribute();
            $ret              = $attributeDaoImpl->getAttributeRolesByRoleId($roleId);
            if (!$ret) {
                throw new Exception_Role_NotFound();
            }
            return $ret;
        } catch (Exception $e) {
            if ($e instanceof Exception_Role)
                throw $e;
            throw new Exception_Role_RetrieveFailed($e);
        }
    }
}