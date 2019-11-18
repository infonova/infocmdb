<?php

abstract class Import_File_Method_Update extends Import_File_Method_Abstract implements Import_File_Method
{

    public function import($logger, $historyId, $data, $attributeList, $parameter = array())
    {
        $updateObject  = new Import_File_Util_Object_Update();
        $hasError      = false;
        $importDaoImpl = new Dao_Import();

        $status           = array();
        $status['status'] = true;
        $status['errors'] = array();

        $ciToCheck = null;

        if ($attributeList[0] == Import_File_Util_Attribute::CI_ID_KEY) {
            $ciToCheck = $data[0];

        } else {

            $attributeId = $attributeList[0]['value'];
            $isUnique    = $attributeList[0]['is_mandatory'];

            if (!$attributeId) {
                // attribute not found
                $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_MISSING_ATTRIBUTE_ID . '. Unique Identifier is invalid on line ' . $parameter['line'], Zend_Log::CRIT);
                $status['errors'][0] = Import_File_Code::ERROR_UPDATE_MISSING_ATTRIBUTE_ID;
                $hasError            = true;
            }

            if (!$isUnique) {
                // attribute is not unique!
                $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_ATTRIBUTE_NOT_UNIQUE . '. Unique Identifier is not Unique on line ' . $parameter['line'], Zend_Log::CRIT);
                $status['errors'][0] = Import_File_Code::ERROR_UPDATE_ATTRIBUTE_NOT_UNIQUE;
                $hasError            = true;
            }

            // check if value is set
            if (is_null($data[0]) || $data[0] == '' || $data[0] == ' ') {
                $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_INVALID_ATTRIBUTE_VALUE . '. Invalid (null?) value for update on line ' . $parameter['line'], Zend_Log::CRIT);
                $status['errors'][1] = Import_File_Code::ERROR_UPDATE_INVALID_ATTRIBUTE_VALUE;
                $hasError            = true;
            }

            // if errors, return
            if ($hasError) {
                $status['status'] = false;
                $updateObject->setStatus($status);
                return $updateObject;
            }


            // get current db entry
            $res = $importDaoImpl->getCiIdByAttributeValue($data[0], $attributeId);
            if (!$res) {
                // attribute not found
                $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_INVALID_ATTRIBUTE_VALUE . '. Ci not found by given unique Identifier on line ' . $parameter['line'], Zend_Log::CRIT);
                $status['errors'][1] = Import_File_Code::ERROR_UPDATE_INVALID_ATTRIBUTE_VALUE;
                $status['status']    = false;
                $updateObject->setStatus($status);
                return $updateObject;
            } else {
                $logger->log('found ci ' . $res[Db_CiAttribute::CI_ID] . ' by given unique Identifier on line ' . $parameter['line'], Zend_Log::DEBUG);
            }

            $ciToCheck = $res[Db_CiAttribute::CI_ID];

        }

        if (!$ciToCheck) {
            $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_INVALID_ATTRIBUTE_VALUE . '. Ci not found by given unique Identifier on line ' . $parameter['line'], Zend_Log::CRIT);
            $status['errors'][1] = Import_File_Code::ERROR_UPDATE_INVALID_ATTRIBUTE_VALUE;
            $status['status']    = false;
            $updateObject->setStatus($status);
            return $updateObject;
        }

        // does exist and is ready to be inserted
        $currentAttributeList = $importDaoImpl->getCiAttributesByCiId($ciToCheck);

        unset($data[0]); // not needed anymore
        unset($attributeList[0]);

        $updateObject->setAttributeList($attributeList);
        $updateObject->setCurrentAttributeList($currentAttributeList);
        $updateObject->setData($data);
        $updateObject->setStatus($status);
        return $updateObject;
    }


    protected function compareAttributeValue($value, $ciAttribute)
    {
        $class = Util_AttributeType_Factory::get($ciAttribute['type']);
        $val   = utf8_encode($value);
        $value = $class->returnFormData(array('value' => $val), $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID]);

        if (!is_null($value[Db_CiAttribute::VALUE_DEFAULT])) {
            $importDaoImpl = new Dao_Import();
            $ret           = $importDaoImpl->getDefaultValueIdByName($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID], $value[Db_CiAttribute::VALUE_DEFAULT]);

            if ($ret)
                $value[Db_CiAttribute::VALUE_DEFAULT] = $ret[Db_AttributeDefaultValues::ID];

        }


        if ($value[Db_CiAttribute::VALUE_TEXT] && $value[Db_CiAttribute::VALUE_TEXT] == $ciAttribute[Db_CiAttribute::VALUE_TEXT])
            return true;

        if ($value[Db_CiAttribute::VALUE_DEFAULT] && $value[Db_CiAttribute::VALUE_DEFAULT] == $ciAttribute[Db_CiAttribute::VALUE_DEFAULT])
            return true;

        if ($value[Db_CiAttribute::VALUE_DATE] && $value[Db_CiAttribute::VALUE_DATE] == $ciAttribute[Db_CiAttribute::VALUE_DATE])
            return true;

        if ($value[Db_CiAttribute::VALUE_CI] && $value[Db_CiAttribute::VALUE_CI] == $ciAttribute[Db_CiAttribute::VALUE_CI])
            return true;

        return false;
    }
}