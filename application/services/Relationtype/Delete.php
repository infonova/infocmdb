<?php

/**
 *
 *
 *
 */
class Service_Relationtype_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2804, $themeId);
    }


    public function deleteRelationType($relationTypeId)
    {
        try {
            $status = 0;

            $ciRelationTypeDaoIml = new Dao_CiRelation();
            $ciRelationsList      = $ciRelationTypeDaoIml->getCiIdsByCiRelationTypeId($relationTypeId);
            if (!empty($ciRelationsList)) {
                $deactivateCiRelationType = $ciRelationTypeDaoIml->deactivateCiRelationType($relationTypeId);
                $status                   = 1;
            } else {
                $deleteCiRelationTypes = $ciRelationTypeDaoIml->deleteCiTypeRelationTypeByRelationTypeId($relationTypeId);
                $deleteRealtionType    = $ciRelationTypeDaoIml->deleteCiRelationTypeByRelationTypeId($relationTypeId);
                $status                = 2;
            }

            return $status;

        } catch (Exception $e) {
            throw new Exception_Relation_DeleteFailed($e);
        }
    }

    /**
     * activates an inactive realtion type by the given relationTypeId
     *
     * @param $relationtypeId Relation Type to activate
     *
     * @throws Exception_Relation_ActivateFailed if activation failes
     */
    public function activateRelationType($relationTypeId)
    {
        try {
            $ciRelationTypeDaoIml = new Dao_CiRelation();
            $ciRelationTypeDaoIml->activateCiRelationType($relationTypeId);
        } catch (Exception $e) {
            throw new Exception_Relation_ActivateFailed($e);
        }
    }

}