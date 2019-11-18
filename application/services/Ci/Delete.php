<?php

/**
 *
 *
 *
 */
class Service_Ci_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 304, $themeId);
    }


    public function deleteCi($userDto, $ciId)
    {
        if (!$userDto->getCiDelete()) {
            throw new Exception_AccessDenied();
        }

        $ciServiceGet = new Service_Ci_Get($this->translator, $this->logger, parent::getThemeId());
        if (!$ciServiceGet->checkPermission($ciId, $userDto->getId())) {
            throw new Exception_AccessDenied();
        }

        $deleteConfig  = new Zend_Config_Ini(APPLICATION_PATH . '/configs/delete.ini', APPLICATION_ENV);
        $deleteAllowed = $deleteConfig->delete->ci->deleterelations;

        $ciRelationDaoImpl = new Dao_CiRelation();
        $relations         = $ciRelationDaoImpl->countCiRelations($ciId);

        if ($relations['cnt'] > 0) {

            if (!$deleteAllowed) {
                throw new Exception_AccessDenied();
            }
        }
        try {
            $ci_info        = [];
            $ci_info['old'] = $ciServiceGet->getContextInfoForCi($ciId);
            $ci_info['new'] = [];

            // customization handling
            $triggerUtil = new Util_Trigger($this->logger);
            $triggerUtil->deleteCi($ciId, $userDto->getId(), $ci_info);

            // historize
            $historizationUtil = new Util_Historization();
            $historizationUtil->handleCiDelete($ciId, $userDto->getId());

            return true;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            throw new Exception_Ci_DeleteFailed($e);
        }

    }


    public function deleteAllCiAttribute($ciId)
    {

    }


    public function deleteSingleCiAttribute($ciAttributeId, $historyId = null)
    {
        $ciDaoImpl = new Dao_Ci();
        return $ciDaoImpl->deleteSingleCiAttributesById($ciAttributeId, $historyId);
    }

    public function deleteSingleCiRelation($ciRelationId)
    {
        $ciDaoImpl = new Dao_Ci();
        return $ciDaoImpl->deleteSingleCiRelationById($ciRelationId);
    }


}