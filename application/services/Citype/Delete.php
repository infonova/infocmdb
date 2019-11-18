<?php

/**
 *
 *
 *
 */
class Service_Citype_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 404, $themeId);
    }


    /**
     * deletes or deactivates a Ci Type by the given Ci Type Id
     *
     * @param $typeId the Ci Type to delete
     */
    public function deleteCiType($typeId)
    {
        $ciTypeDaoIml  = new Dao_CiType();
        $ciList        = $ciTypeDaoIml->getCiByCiTypeId($typeId);
        $ciChildsfound = $ciTypeDaoIml->retrieveCiTypeChildElementsforDelete($typeId);

        $status       = 0;
        $notification = array();
        if (!empty($ciList) || !empty($ciChildsfound)) {
            //deactivate
            $ciTypeDaoIml->deactivateCiType($typeId);
            $status = 2;
        } else {
            // delete
            $ciTypeDaoIml->deleteCiType($typeId);
            $status = 1;
        }
        return $status;
    }


    /**
     * activates an inactive ci type by the given citypeId
     *
     * @param $typeId Attribute to activate
     *
     * @throws Exception_Citype_ActivationFailed if activation failes
     */
    public function activateCiType($typeId)
    {
        try {
            $ciTypeDaoIml = new Dao_CiType();
            $ciTypeDaoIml->activateCiType($typeId);
        } catch (Exception $e) {
            throw new Exception_Citype_ActivationFailed($e);
        }
    }
}