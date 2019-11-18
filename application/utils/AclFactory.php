<?php

class Util_AclFactory
{

    public function createAcl($resourceId)
    {
        //Lets assume we have a model for the page_privileges with a method like this
        //which would return PagePrivilege objects with the page_id passed as the param.

        $authDaoImpl = new Dao_Authentication();
        $privileges  = $authDaoImpl->findPrivilegesByResourceId($resourceId);

        $acl = new Zend_Acl();
        $acl->addResource(new Zend_Acl_Resource($resourceId));
        //$acl->add(new Zend_Acl_Resource($resourceId));

        foreach ($privileges as $privilege) {
            try {
                $acl->addRole(new Zend_Acl_Role($privilege[Db_ThemePrivilege::THEME_ID]));
                $acl->allow($privilege[Db_ThemePrivilege::THEME_ID], $resourceId);
            } catch (Exception $e) {
                // TODO:
            }
        }

        $acl->addRole(new Zend_Acl_Role(0));
        $acl->allow(0, $resourceId);
        return $acl;
    }
}