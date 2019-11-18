<?php

class Import_File_Method_Auto_Relation extends Import_File_Method_Relation implements Import_File_Method
{

    public function import(&$logger, $historyId, &$row, $attributeList, $parameter = array())
    {
        $importDaoImpl    = new Dao_Import();
        $status           = array();
        $status['status'] = true;
        $status['errors'] = array();


        // first -> check if 

        // check reltype
        $relationTypeId = $row[1];
        if (!is_numeric($relationTypeId)) {
            $relationTypeId = $this->retrieveRelationType($relationTypeId);
        }


        if (!$relationTypeId || strlen(trim($relationTypeId)) <= 0) {
            $status['errors'][2] = Import_File_Code::ERROR_RELATION_INVALID_RELATIONTYPE;
            $status['status']    = false;
            return $status;
        }


        $ciId1 = $row[2];
        if ($attributeList[2] != 'ci_id') {
            $ciId1 = $this->retrieveCiIdByAttribute($ciId1, $attributeList[2], $logger);
        }

        if (!$ciId1 || strlen(trim($ciId1)) <= 0) {
            $status['errors'][3] = Import_File_Code::ERROR_RELATION_INVALID_CI_ID_1;
            $status['status']    = false;
            return $status;
        }

        $ciId2 = $row[3];
        if ($attributeList[3] != 'ci_id') {
            $ciId2 = $this->retrieveCiIdByAttribute($ciId1, $attributeList[3], $logger);
        }

        if (!$ciId2 || strlen(trim($ciId2)) <= 0) {
            $status['errors'][4] = Import_File_Code::ERROR_RELATION_INVALID_CI_ID_2;
            $status['status']    = false;
            return $status;
        }

        if ($row[4]) {
            $note = $row[4];
        }

        if ($row[0] == 'delete') {
            $ret = $this->deleteAction($relationTypeId, $ciId1, $ciId2);

            if (!$ret) {
                $status['errors'][5] = Import_File_Code::ERROR_RELATION_RELATION_DELETE_FAILED;
                $status['status']    = false;
            }
        } else {

            // check if relation exists
            $oldRel = $this->relationExists($relationTypeId, $ciId1, $ciId2);

            if (!$oldRel) {
                $ret = $this->createAction($relationTypeId, $ciId1, $ciId2, $note);
            } else {

                if ($note && $oldRel[Db_CiRelation::NOTE] != $note) {
                    $ret = $this->updateNoteAction($oldRel[Db_CiRelation::ID], $note);
                }
            }


            if (!$ret) {
                $status['errors'][5] = Import_File_Code::ERROR_RELATION_RELATION_CREATE_FAILED;
                $status['status']    = false;
            }
        }


        return $status;
    }

    private function retrieveRelationType($name)
    {
        try {
            $importDaoImpl = new Dao_Import();
            $rt            = $importDaoImpl->getRelationType($name);
            return $rt[Db_CiRelationType::ID];
        } catch (Exception $e) {
            return null;
        }
    }


    private function retrieveCiIdByAttribute($value, $attribute, $logger)
    {
        try {
            $importDaoImpl = new Dao_Import();
            $rt            = $importDaoImpl->getCiIdByAttributeValue($value, $attribute[Db_Attribute::ID]);
            return $rt[Db_CiAttribute::CI_ID];
        } catch (Exception $e) {
            return null;
        }
    }

    private function deleteAction($relationTypeId, $ciId1, $ciId2)
    {
        try {
            $importDaoImpl = new Dao_Import();
            return $importDaoImpl->deleteRelation($relationTypeId, $ciId1, $ciId2);
        } catch (Exception $e) {
            return null;
        }
    }


    private function createAction($relationTypeId, $ciId1, $ciId2, $note)
    {
        try {
            $importDaoImpl = new Dao_Import();
            return $importDaoImpl->createRelation($relationTypeId, $ciId1, $ciId2, $note);
        } catch (Exception $e) {
            return null;
        }
    }


    private function relationExists($relationTypeId, $ciId1, $ciId2)
    {
        try {
            $importDaoImpl = new Dao_Import();
            $ret           = $importDaoImpl->getRelationByParams($relationTypeId, $ciId1, $ciId2);

            if ($ret && $ret[Db_CiRelation::ID])
                return $ret;

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function updateNoteAction($relId, $note)
    {
        $importDaoImpl = new Dao_Import();

    }

}