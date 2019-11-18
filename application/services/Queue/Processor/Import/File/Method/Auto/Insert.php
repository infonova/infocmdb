<?php

//deprecated

class Import_File_Method_Auto_Insert extends Import_File_Method_Abstract implements Import_File_Method
{

    public function import(&$logger, $historyId, &$row, $attributeList, $parameter = array())
    {
        $importUtil       = new Import_File_Util_Object_Import();
        $hasError         = false;
        $importDaoImpl    = new Dao_Import();
        $triggerUtil      = new Util_Trigger($logger);
        $status           = array();
        $status['status'] = true;
        $status['errors'] = array();

        // check CI TYPE
        if (!$row[0]) {
            $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_INSERT_INVALID_CI_TYPE . '. CiType is empty on line ' . $parameter['line'], Zend_Log::CRIT);
            $status['errors'][1] = Import_File_Code::ERROR_INSERT_INVALID_CI_TYPE;
            $hasError            = true;
        }
        $currentCiType = $importDaoImpl->getCiType($row[0]);
        $currentCiType = $currentCiType[Db_CiType::ID];

        if (!$currentCiType) {
            $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_INSERT_INVALID_CI_TYPE . '. CiType is invalid on line ' . $parameter['line'], Zend_Log::CRIT);
            $status['errors'][1] = Import_File_Code::ERROR_INSERT_INVALID_CI_TYPE;
            $hasError            = true;
        }

        // check PROJECT
        if (!$row[1]) {
            $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_INSERT_INVALID_PROJECT . '. Project is empty on line ' . $parameter['line'], Zend_Log::CRIT);
            $status['errors'][2] = Import_File_Code::ERROR_INSERT_INVALID_PROJECT;
            $hasError            = true;
        }

        $currentProject = $importDaoImpl->getProject($row[1]);
        $currentProject = $currentProject[Db_Project::ID];

        if (!$currentProject) {
            $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_INSERT_INVALID_PROJECT . '. Project is invalid on line ' . $parameter['line'], Zend_Log::CRIT);
            $status['errors'][2] = Import_File_Code::ERROR_INSERT_INVALID_PROJECT;
            $hasError            = true;
        }

        // if errors, return
        if ($hasError) {
            $status['status'] = false;
            return $status;
        }

        unset($attributeList[0]); // citype
        unset($attributeList[1]); // project

        if (!$historyId) {
            $historizationUtil = new Util_Historization();
            $userId            = $parameter['userId'];
            if (!$userId)
                $userId = '0';
            $historyId           = $historizationUtil->createHistory($userId, Util_Historization::MESSAGE_IMPORT_INSERT);
            $status['historyId'] = $historyId;
        }

        // create new ci
        $data                    = array();
        $data[Db_Ci::CI_TYPE_ID] = $currentCiType;
        $data[Db_Ci::HISTORY_ID] = $historyId;

        try {
            $ciId = $importDaoImpl->insertCi($data);
            $logger->log('created new CI with id ' . $ciId . '  line ' . $parameter['line'], Zend_Log::DEBUG);
        } catch (Exception $e) {
            $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. Creating new CI failed on line ' . $parameter['line'], Zend_Log::CRIT);

            $status['errors'][0] = Import_File_Code::ERROR_INSERT_FAILED;
            $status['status']    = false;
            return $status;
        }


        // create project mapping
        $data                           = array();
        $data[Db_CiProject::CI_ID]      = $ciId;
        $data[Db_CiProject::PROJECT_ID] = $currentProject;
        $data[Db_CiProject::HISTORY_ID] = $historyId;

        try {
            $importDaoImpl->insertCiProject($data);
            $logger->log('added ci project mapping for CI ' . $ciId . '  line ' . $parameter['line'], Zend_Log::DEBUG);
        } catch (Exception $e) {
            $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. adding CI Project mapping failed for CI ' . $ciId . '  line ' . $parameter['line'], Zend_Log::CRIT);
            $status['errors'][0] = Import_File_Code::ERROR_INSERT_FAILED;
            $status['status']    = false;
            return $status;
        }


        foreach ($attributeList as $rowKey => $r) {
            if (!is_null($r['value'])) {
                $class = Util_AttributeType_Factory::get($r['type']);
                $val   = parent::encode($row[$rowKey]);
                $data  = $class->returnFormData(array('value' => $val), $r['value']);

                if (($r['type'] == Enum_AttributeType::CHECKBOX)) {

                    $defaultvalues                    = $importUtil->getdefaultValuesbyName($data[Db_CiAttribute::VALUE_TEXT], $r['value']);
                    $data[Db_CiAttribute::VALUE_TEXT] = $defaultvalues;


                    if (is_null($defaultvalues)) {

                        $logger->log('line: ' . $parameter['line'] . '=> CI with ID ' . $curr[Db_CiAttribute::CI_ID] . ' invalid default value no update!', Zend_Log::ERR);
                        $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_DEFAULT_VALUE;
                        $status['status']              = false;
                        $update                        = false;

                    }


                }

                if (!is_null($data[Db_CiAttribute::VALUE_DEFAULT])) {
                    $ret                                 = $importDaoImpl->getDefaultValueIdByName($r['value'], $data[Db_CiAttribute::VALUE_DEFAULT]);
                    $data[Db_CiAttribute::VALUE_DEFAULT] = $ret[Db_AttributeDefaultValues::ID];
                }

                // insert
                $data[Db_CiAttribute::CI_ID]        = $ciId;
                $data[Db_CiAttribute::ATTRIBUTE_ID] = $r['value'];
                $data[Db_CiAttribute::IS_INITIAL]   = '1';
                $data[Db_CiAttribute::HISTORY_ID]   = $historyId;

                if (is_null($data[Db_CiAttribute::NOTE])) {
                    $data[Db_CiAttribute::NOTE] = 'automatic import ' . time();
                }

                // insert new attribute
                if (is_null($row[$rowKey]) || $row[$rowKey] == '' || $row[$rowKey] == ' ') {
                    // warning is enough
                    $logger->log('value for attribute "' . $r['name'] . '", ciId ' . $ciId . ' was NULL or invalid on line ' . $parameter['line'], Zend_Log::INFO);
                }

                $ciAttributeId = $importDaoImpl->insertCiAttribute($data);

                if (is_null($ciAttributeId)) {
                    // failed
                    $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. adding Ci Attribute to CI ' . $ciId . ' failed on line ' . $parameter['line'], Zend_Log::CRIT);
                    $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_INSERT_FAILED;
                    $status['status']              = false;
                } else {
                    $triggerUtil->createAttribute($ciAttributeId, 0);
                    // success
                    $logger->log('added ci attribute for CI ' . $ciId . ' value (' . $data[Db_CiAttribute::VALUE_CI] . $data[Db_CiAttribute::VALUE_DEFAULT] . $data[Db_CiAttribute::VALUE_TEXT] . $data[Db_CiAttribute::VALUE_DATE] . ')  line ' . $parameter['line'], Zend_Log::DEBUG);
                }
            }
        }

        $triggerUtil->createCi($ciId, 0);

        return $status;
    }

}