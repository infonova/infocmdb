<?php

/**
 *
 *
 *
 */
class Service_Role_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2204, $themeId);
    }

    /**
     * deletes a Theme by the given Role Id
     *
     * @param $roleId the Role to delete
     *
     * @throws Exception_Role_DeleteFailed
     */
    public function deleteRole($roleId)
    {
        try {
            $roleDaoImpl        = new Dao_Role();
            $countUserRole      = $roleDaoImpl->countUserRole($roleId);
            $countAttributeRole = $roleDaoImpl->countAttributeRole($roleId);
            $statusCode         = 0;

            $this->logger->log($countAttributeRole['cnt']);

            if ($countUserRole['cnt'] != 0 || $countAttributeRole['cnt'] != 0) {
                $rows       = $roleDaoImpl->deactivateRole($roleId);
                $statusCode = 2;
            } else {
                $roleDaoImpl->deleteUserRole($roleId);
                $roleDaoImpl->deleteAttributeRole($roleId);
                $rows       = $roleDaoImpl->deleteRole($roleId);
                $statusCode = 1;
            }
            if ($rows != 1) {
                throw new Exception();
                $statusCode = 0;
            }
        } catch (Exception $e) {
            //throw new Exception_Role_DeleteFailed($e);
        }
        return $statusCode;
    }

    public function activateRole($roleId)
    {
        try {
            $roleDaoImpl   = new Dao_Role();
            $countUserRole = $roleDaoImpl->activateRole($roleId);
        } catch (Exception $e) {
            throw new Exception_Role_ActivateFailed($e);
        }
    }
}