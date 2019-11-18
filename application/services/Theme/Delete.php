<?php

/**
 *
 *
 *
 */
class Service_Theme_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2504, $themeId);
    }

    /**
     * deletes a Theme by the given Theme Id
     *
     * @param $attributeGroupId the View Type to delete
     *
     * @throws Exception_Theme_DeleteFailed
     */
    public function deleteTheme($themeId)
    {
        try {
            $themeDaoImpl     = new Dao_Theme();
            $userDaoImpl      = new Dao_User();
            $countUserByTheme = $userDaoImpl->getCountUserByThemeId($themeId);
            $statusCode       = 0;
            if ($countUserByTheme['cnt'] != 0) {
                $rows       = $themeDaoImpl->deactivateTheme($themeId);
                $statusCode = 2;
            } else {
                $themeDaoImpl->deleteThemeMenuByThemeId($themeId);
                $themeDaoImpl->deleteThemePrivileges($themeId);
                $rows       = $themeDaoImpl->deleteTheme($themeId);
                $statusCode = 1;
            }
            if ($rows != 1) {
                throw new Exception();
                $statusCode = 0;
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            throw new Exception_Theme_DeleteFailed($e);
        }
        return $statusCode;
    }

    public function activateTheme($themeId)
    {
        try {
            $themeDaoImpl  = new Dao_Theme();
            $activateTheme = $themeDaoImpl->activateTheme($themeId);
        } catch (Exception $e) {
            throw new Exception_Theme_ActivateFailed($e);
        }
    }
}