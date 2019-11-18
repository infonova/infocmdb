<?php

/**
 *
 *
 *
 */
class Service_Relation_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2004, $themeId);
    }


    public function deleteRelation($userDto, $ciRelationId)
    {
        try {
            if ($userDto->getRelationEdit()) {
                // customization handling
                $triggerUtil = new Util_Trigger($this->logger);
                $triggerUtil->deleteRelation($ciRelationId, $userDto->getId());

                $ciRelationDaoImpl = new Dao_CiRelation();
                $ciRelationDaoImpl->deleteCiRelation($ciRelationId);
                return true;
            } else {
                throw new Exception_AccessDenied();
            }
        } catch (Exception $e) {
            if ($e instanceof Exception_AccessDenied) {
                throw new Exception_AccessDenied($e);
            } else {
                throw new Exception_Relation($e);
            }
        }
    }


    public function deleteVisualizationFile($filename)
    {
        $dir  = APPLICATION_PUBLIC . 'visualization/';
        $file = $dir . $filename;
        unlink($file);

        return true;
    }
}