<?php

/**
 *
 *
 *
 */
class Service_User_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2604, $themeId);
    }


    public function deleteUser($userId)
    {
        try {
            $userDaoImpl   = new Dao_User();
            $importDaoImpl = new Dao_Import();

            $countUserProject = $userDaoImpl->getCountUserProjectByUserId($userId);
            $countUserRole    = $userDaoImpl->getCountUserRoleByUserId($userId);
            $countUserImports = $importDaoImpl->getCountImportFileHistoryByUserId($userId);
            $statusCode       = 0;
            if ($countUserProject['cnt'] != 0 || $countUserRole['cnt'] != 0 || $countUserImports['cnt'] != 0) {
                $rows       = $userDaoImpl->deactivateUser($userId);
                $statusCode = 2;
            } else {
                // delete UserProjects
                $projectDaoImpl = new Dao_Project();
                $projectDaoImpl->deleteProjectMappingByUserId($userId);

                // delete UserRoles
                $roleDaoImpl = new Dao_Role();
                $roleDaoImpl->deleteRoleMappingByUserId($userId);

                // delete User			
                $rows       = $userDaoImpl->deleteUsers($userId);
                $statusCode = 1;
            }
            if ($rows != 1) {
                throw new Exception();
                $statusCode = 0;
            }
        } catch (Exception $e) {
            throw new Exception_User_DeleteFailed($e);
        }
        return $statusCode;
    }

    public function deactivateUser($userId)
    {
        try {
            $userDaoImpl    = new Dao_User();
            $deactivateUser = $userDaoImpl->deactivateUser($userId);
        } catch (Exception $e) {
            throw new Exception_User_DeactivateFailed($e);
        }
    }

    public function activateUser($userId)
    {
        try {
            $userDaoImpl  = new Dao_User();
            $activateUser = $userDaoImpl->activateUser($userId);
        } catch (Exception $e) {
            throw new Exception_User_ActivateFailed($e);
        }
    }

}