<?php

/**
 *
 *
 *
 */
class Service_History_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1003, $themeId);
    }


    public function restoreHistoryEntry($historyId, $userID)
    {


        $historyDaoImpl = new Dao_History();


        //get all data for ci to restore by using history_id_delete

        $ci           = $historyDaoImpl->getCiByhistoryIDdeleted($historyId);
        $ciattributes = $historyDaoImpl->getCiAttributesbyhistoryIDdeleted($historyId);
        $ciproject    = $historyDaoImpl->getCiProjectbyhistoryIDdeleted($historyId);
        $cirelation   = $historyDaoImpl->getCiRelationbyhistoryIDdeleted($historyId);


        $newHistoryID = $this->checkValidate($ci, $ciattributes, $ciproject, $cirelation, $userID);
        $this->restore($newHistoryID, $ci, $ciattributes, $ciproject, $cirelation);
    }


    private function checkValidate($ci, $ciattributes, $ciproject, $cirelation, $userID)
    {

        $historyDaoImpl = new Dao_History();

        if (empty($ci) && empty($ciattributes) && empty($ciproject) && empty($cirelation))
            throw new Exception_AccessDenied();


        $newHistoryID = $historyDaoImpl->createHistory($userID, $note = "ci restore");

        return $newHistoryID;


    }


    private function restore($newHistoryID, $ci, $ciattributes, $ciproject, $cirelation)
    {

        $attributeDao   = new Dao_Attribute();
        $projectDao     = new Dao_Project();
        $relationDao    = new Dao_CiRelation();
        $historyDaoImpl = new Dao_History();


        if (!empty($ci)) {
            $historyDaoImpl->restoreCi($ci, $newHistoryID);
            $historyDaoImpl->restoreCiAttributes($ciattributes, $newHistoryID);
            $historyDaoImpl->restoreCiProjects($ciproject, $newHistoryID);
            $historyDaoImpl->restoreCiRelations($cirelation, $newHistoryID);

        } else {

            //resore ciattributes

            foreach ($ciattributes as $ciattribute) {

                $ciattribute[Db_CiAttribute::VALID_FROM] = date('Y-m-d H:i:s');
                unset($ciattribute['history_id_delete']);
                unset($ciattribute['valid_to']);
                $ciattribute[Db_CiAttribute::HISTORY_ID] = $newHistoryID;

                $attributeDao->updateCiAttribute($ciattribute[Db_CiAttribute::ID], $ciattribute);

            }

            //delete ci projects
            foreach ($ciproject as $project) {
                $projectDao->deleteProject($project[Db_CiProject::ID]);
            }

            //delete ci relations 
            foreach ($cirelation as $relation) {
                $relationDao->deleteCiRelation($relation[Db_CiRelation::ID]);
            }

            $historyDaoImpl->restoreCiProjects($ciproject, $newHistoryID);
            $historyDaoImpl->restoreCiRelations($cirelation, $newHistoryID);


        }

        //  restore logic
    }

    // TODO: replace me
    public function restoreSingleAttribute($userId, $ciId, $currentCiAttributeId, $restoreCiAttributeId)
    {
        $attributeDaoImpl = new Dao_Attribute();

        $historizationUtil = new Util_Historization();
        $historyId         = $historizationUtil->prepareCiHistoryEntry($ciId, $userId, Util_Historization::MESSAGE_CI_ATTRIBUTE_RESOTRE);

        // delete current attribute
        $historizationUtil->handleCiSingleUpdateDelete($ciId, $userId, $currentCiAttributeId, $historyId);

        $del = new Service_Ci_Delete($this->translator, $this->logger, parent::getThemeId());
        $del->deleteSingleCiAttribute($currentCiAttributeId, $historyId);

        $historyDaoImpl = new Dao_History();
        return $historyDaoImpl->restoreSingleCiAttribute($restoreCiAttributeId, $historyId);
    }


    // TODO: replace me
    public function restoreSingleRelation($userId, $ciId1, $ciId2, $restoreCiRelationId)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $historyDaoImpl   = new Dao_History();

        $historizationUtil = new Util_Historization();
        $historyId1        = $historizationUtil->prepareCiHistoryEntry($ciId1, $userId, Util_Historization::MESSAGE_CI_RELATION_RESTORE);
        $historyId2        = $historizationUtil->prepareCiHistoryEntry($ciId2, $userId, Util_Historization::MESSAGE_CI_RELATION_RESTORE);

        $newRelationId = $historyDaoImpl->restoreSingleCiRelation($restoreCiRelationId, $historyId1, $historyId2);

        $historyDaoImpl->updateCiRelationHistoryIdDelete($restoreCiRelationId, $historyId1, $historyId2);
        $del = new Service_Ci_Delete($this->translator, $this->logger, parent::getThemeId());
        $del->deleteSingleCiRelation($restoreCiRelationId);
        return $newRelationId;
    }

}