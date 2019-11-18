<?php

class Import_File_Method_Manual_Insert extends Import_File_Method_Abstract implements Import_File_Method
{

    public function import(&$logger, $historyId, &$row, $attributeList, $parameter = array())
    {
        $validationId = $parameter['validationId'];
        $line         = $parameter['line'];

        $hasError         = false;
        $importDaoImpl    = new Dao_Import();
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

        $validationService = new Service_Validation_Create(null, $logger, 0);
        $validationService->updateValidation($validationId, $currentCiType, $currentProject);

        foreach ($attributeList as $rowKey => $r) {
            if (!is_null($r['value'])) {
                $note   = 'automatic import ' . time();
                $values = parent::encode($row[$rowKey]);

                // insert new attribute
                if (is_null($row[$rowKey]) || $row[$rowKey] == '' || $row[$rowKey] == ' ') {
                    // warning is enough
                    $logger->log('value for attribute "' . $r['name'] . '", ciId ' . $ciId . ' was NULL or invalid on line ' . $parameter['line'], Zend_Log::INFO);
                } else if (is_null($validationService->addValidationAttribute($validationId, $line, $currentCiType, $currentProject, $r['value'], $values, $userId, null, $note))) {
                    // failed
                    $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. adding Ci Attribute to CI ' . $ciId . ' failed on line ' . $parameter['line'], Zend_Log::CRIT);
                    $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_INSERT_FAILED;
                    $status['status']              = false;
                } else {
                    // success
                    $logger->log('added ci attribute for CI ' . $ciId . ' value (' . $row[$rowKey] . ')  line ' . $parameter['line'], Zend_Log::DEBUG);
                }

            }
        }

        return $status;
    }

}
