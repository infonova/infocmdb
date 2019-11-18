<?php

abstract class Import_File_Method_Import extends Import_File_Method_Abstract implements Import_File_Method
{

    private $attributeslinked;


    public function import(&$logger, $historyId, &$data, $attributeList, $parameter = array())
    {
        $importObject = new Import_File_Util_Object_Import();

        $hasError      = false;
        $importDaoImpl = new Dao_Import();


        $status           = array();
        $status['status'] = true;
        $status['errors'] = array();

        // createErrorCSV() generated CSV file contains lines starting with #ERROR; if current import line does not contain #ERROR -> execute this block
        if (stripos($data[0], "#ERROR") === false) {
            //Import via unique attribute
            if ($attributeList[0] == Import_File_Util_Attribute::PROJECT_KEY || $attributeList[0] == Import_File_Util_Attribute::PROJECT_KEY_2) {
                $project    = $data[0];
                $ciTypeName = $data[1];

                $ciType  = $importDaoImpl->getCiType($ciTypeName);
                $project = $importDaoImpl->getProject($project);

                unset($attributeList[0]);
                unset($attributeList[1]);


                $attributeId = $attributeList[2]['value'];
                $isUnique    = $attributeList[2]['is_mandatory'];

                if (empty($ciType)) {
                    $logger->log('line: ' . $parameter['line'] . '=> given citype does not exist in database', Zend_Log::ERR);
                    $status['errors'][1] = Import_File_Code::ERROR_IMPORT_PROJECT_OR_CITYPE_NOT_IN_DB;
                    $hasError            = true;
                }

                if (empty($project)) {
                    $logger->log('line: ' . $parameter['line'] . '=> given project does not exist in database', Zend_Log::ERR);
                    $status['errors'][1] = Import_File_Code::ERROR_IMPORT_PROJECT_OR_CITYPE_NOT_IN_DB;
                    $hasError            = true;
                }


                if (!$attributeId) {
                    // attribute not found
                    $logger->log('line: ' . $parameter['line'] . '=> [ERROR] Code ' . Import_File_Code::ERROR_UPDATE_MISSING_ATTRIBUTE_ID . '. Unique Identifier is invalid not found in Database', Zend_Log::CRIT);
                    $status['errors'][0] = Import_File_Code::ERROR_UPDATE_MISSING_ATTRIBUTE_ID;
                    $hasError            = true;
                }


                if (!$isUnique) {
                    // attribute is not unique!
                    $logger->log('line: ' . $parameter['line'] . '=> [ERROR] Code ' . Import_File_Code::ERROR_UPDATE_ATTRIBUTE_NOT_UNIQUE . '. Unique Identifier is not Unique', Zend_Log::CRIT);
                    $status['errors'][0] = Import_File_Code::ERROR_UPDATE_ATTRIBUTE_NOT_UNIQUE;
                    $hasError            = true;
                }

            } else { //Import via ciid
                $ciId = $data[0];
                unset($attributeList[0]);

                if (!$importDaoImpl->checkCiExistence($ciId)) {
                    $logger->log('line: ' . $parameter['line'] . '=> given ciid "' . $ciId . '" does not exist in database', Zend_Log::ERR);
                    $status['errors'][1] = Import_File_Code::ERROR_IMPORT_CIID_NOT_IN_DB;
                    $hasError            = true;
                }

            }


            // if errors, return
            if ($hasError) {
                $status['status'] = false;
                $importObject->setStatus($status);
                return $importObject;

            }

            //Import via unique attribute: fetch CIID
            if ($ciId === null) {
                $res = $importDaoImpl->getCiIdByAttributeValue($data[2], $attributeId);
            } else { //Import via ciid
                $res = array(Db_CiAttribute::CI_ID => $ciId);
                unset($attributeList[0]); //exclude ciid from update --> identifier doesn't change
            }

            //only unique-attribute-import
            if (!$res) {

                $importObject->setIsInsert(true);

                $ciTypeID  = $ciType[Db_CiType::ID];
                $projectID = $project[Db_Project::ID];

                $importObject->setProject($projectID);
                $importObject->setCiType($ciTypeID);


            } else { //unique-attribute-import or ciid-import
                $importObject->setIsInsert(false);

                // does exist and is ready to be updated
                $currentAttributeList = $importDaoImpl->getCiAttributesByCiId($res[Db_CiAttribute::CI_ID]);
                $importObject->setCurrentAttributeList($currentAttributeList);
            }

            $importObject->setAttributeList($attributeList);
            $importObject->setData($data);
            $importObject->setStatus($status);
        }
        $importDaoImpl = null;

        return $importObject;
    }


    protected function compareAttributeValue($value, $ciAttribute)
    {

        $value = trim($value);

        //text
        if ($value === trim($ciAttribute[Db_CiAttribute::VALUE_TEXT]))
            return true;

        //value-default
        if ($value === $ciAttribute[Db_CiAttribute::VALUE_DEFAULT])
            return true;

        //date
        if (strtotime($ciAttribute[Db_CiAttribute::VALUE_DATE]) && strtotime($ciAttribute[Db_CiAttribute::VALUE_DATE]) === strtotime($value)) {
            return true;
        }

        //SAP Date-Match
        //convert to date-format from SAP
        if ($ciAttribute[Db_CiAttribute::VALUE_DATE] === '0000-00-00 00:00:00') {
            $date = '00000000';
        } else {
            $date = date("Ymd", strtotime($ciAttribute[Db_CiAttribute::VALUE_DATE]));
        }

        //compare does not affect other date-time-formats cause value will always be different:
        //    2015-01-15 14:36:12 === 20150115     --> false
        //    15.01.2015 14:36:12 === 20150115     --> false
        //    2015-01-15          === 20150115     --> false
        //    20150115            === 20150115     --> true
        if ((string)$value === (string)$date) {
            return true;
        }

        return false;
    }


}