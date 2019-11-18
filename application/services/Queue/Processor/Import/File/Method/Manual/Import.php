<?php

class Import_File_Method_Manual_Import extends Import_File_Method_Import implements Import_File_Method
{

    public function import(&$logger, $historyId, &$row, $attributeList, $parameter = array())
    {
        $importObject = parent::import($logger, $historyId, $row, $attributeList, $parameter);
        $status       = $importObject->getStatus();

        if (!$status['status']) {
            return $status;
        }

        if ($importObject->isInsert()) {
            $status = self::insert($logger, $importObject, $historyId, $parameter);
        } else {
            $status = self::update($logger, $importObject, $historyId, $parameter);
        }
        return $status;
    }

//insert mit autovalidation => cid kann erst bei g�ltiger Validierung zugewiesen werden 
    private function insert($logger, $importObject, $historyId, $parameter)
    {


        $importDaoImpl = new Dao_Import();
        $attributeList = $importObject->getAttributeList();
        $row           = $importObject->getData();
        $status        = $importObject->getStatus();


        $project = $importObject->getProject();
        $ciType  = $importObject->getCiType();

        $validationId = $parameter['validationIdInsert'];
        $line         = $parameter['line'];
        $userId       = $parameter['user'];

        if (!$userId)
            $userId = 0;

        $validationService = new Service_Validation_Create(null, $logger, 0);
        $validationService->updateValidation($validationId, $ciType, $project);

        foreach ($attributeList as $rowKey => $r) {
            $insert = true;
            if (!is_null($r['value'])) {

                $note = 'manual import ' . time();
                $val  = parent::encode($row[$rowKey]);
                // insert new attribute
                if (is_null($row[$rowKey]) || $row[$rowKey] == '' || $row[$rowKey] == ' ') {
                    // warning is enough
                    $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => value for attribute was empty', Zend_Log::INFO);
                    $insert = false;
                }


                if ($insert) {

                    if (is_null($validationService->addValidationAttribute($validationId, $line, $r['value'], $ciType, $project, $val, $userId, null, $note))) {
                        // failed
                        $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => [ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. adding ci attribute to validation failed ', Zend_Log::ERR);
                        $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_INSERT_FAILED;
                        $status['status']              = false;
                    } else {
                        // success
                        $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => added ci attribute value "' . $row[$rowKey] . '" to validation', Zend_Log::INFO);
                    }
                }

            }
        }

        return $status;
    }


    private function update($logger, $importObject, $historyId, $parameter)
    {

        $importUtil           = new Import_File_Util_Object_Import();
        $attributeList        = $importObject->getAttributeList();
        $currentAttributeList = $importObject->getCurrentAttributeList();
        $data                 = $importObject->getData();
        $status               = $importObject->getStatus();

        $project = $importObject->getProject();
        $ciType  = $importObject->getCiType();

        $validationId  = $parameter['validationIdUpdate'];
        $importDaoImpl = new Dao_Import();

        $ciId = $currentAttributeList[0][Db_CiAttribute::CI_ID];
        $logger->log('line: ' . $parameter['line'] . ' => Handle CI with id ' . $ciId, Zend_Log::INFO);

        foreach ($attributeList as $rowKey => $r) {
            $foundAttribute = false;

            foreach ($currentAttributeList as $curr) {

                if ($curr[Db_CiAttribute::ATTRIBUTE_ID] == $r['value']) {
                    if (is_null($data[$rowKey]) || $data[$rowKey] == '' || $data[$rowKey] == ' ') {
                        $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => attribute is not set. No action required => not added to validation!', Zend_Log::INFO);
                    } else {


                        $comparevalue = $data[$rowKey];

                        // check change
                        if (($r['type'] == Enum_AttributeType::SELECT) || ($r['type'] == Enum_AttributeType::RADIO) || ($r['type'] == Enum_AttributeType::CHECKBOX)) {


                            $defaultvalues = $importUtil->getdefaultValuesbyName($data[$rowKey], $curr[Db_CiAttribute::ATTRIBUTE_ID]);
                            $comparevalue  = $defaultvalues;


                            if (is_null($defaultvalues)) {


                                $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => invalid default value => no update!', Zend_Log::ERR);
                                $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_DEFAULT_VALUE;
                                $status['status']              = false;
                                $update                        = false;


                            }

                        }

                        $comparevalue = parent::encode($comparevalue);

                        if (self::compareAttributeValue($comparevalue, $curr)) {
                            // no update -> equal

                            //$return[$rowKey] = self::SUCCESS_FIELD_NO_UPDATE;
                            $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => attribute has an equal value. No update required => not added to validation!', Zend_Log::INFO);
                        } else {

                            $values = parent::encode($data[$rowKey]);


                            // update if $data[$rowKey] -> delete, else real update
                            $newCi                                                   = array();
                            $newCi[Db_ImportFileValidationAttributes::VALIDATION_ID] = $validationId;
                            $newCi[Db_ImportFileValidationAttributes::UNIQUE_ID]     = $curr[Db_CiAttribute::ID];
                            $newCi[Db_ImportFileValidationAttributes::CI_ID]         = $curr[Db_CiAttribute::CI_ID]; // XXX: NULL wenn keine vorherigen Einträge!! Ersetzen mit besserer Lösung!!
                            $newCi[Db_ImportFileValidationAttributes::ATTRIBUTE_ID]  = $curr[Db_CiAttribute::ATTRIBUTE_ID];
                            $newCi[Db_ImportFileValidationAttributes::VALUE]         = $values;
                            $newCi[Db_ImportFileValidationAttributes::STATUS]        = 'idle';
                            $newCi[Db_ImportFileValidationAttributes::PROJECT_ID]    = $project;
                            $newci[Db_ImportFileValidationAttributes::CI_TYPE_ID]    = $ciType;

                            $ciAttributeId = $importDaoImpl->insertValidationAttribute($newCi);


                            if (!$ciAttributeId) {
                                // failed
                                $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => [ERROR] Code ' . Import_File_Code::ERROR_UPDATE_FAILED . '. Ci Attribute manual validation insert failed', Zend_Log::CRIT);
                                $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_UPDATE_FAILED;
                                $status['status']              = false;
                            } else {

                                $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => CI with ID: ' . $curr[Db_CiAttribute::CI_ID] . ': new value: "' . $values . '" added to validation !', Zend_Log::INFO);


                            }
                        }

                    }
                    $foundAttribute = true;
                    break;
                }
            }


            // TODO: insert
            if (!$foundAttribute) {
                // insert new attribute
                $values = parent::encode($data[$rowKey]);

                if (is_null($data[$rowKey]) || $data[$rowKey] == '' || $data[$rowKey] == ' ') {
                    $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => attribute is not set. No action required => not added to validation!', Zend_Log::INFO);
                } else {
                    //$uniqueId = uniqid(rand(), false);

                    $newCi                                                   = array();
                    $newCi[Db_ImportFileValidationAttributes::VALIDATION_ID] = $validationId;
                    //$newCi[Db_ImportFileValidationAttributes::UNIQUE_ID] = $uniqueId;
                    $newCi[Db_ImportFileValidationAttributes::CI_ID]        = $ciId; // XXX: NULL wenn keine vorherigen Einträge!! Ersetzen mit besserer Lösung!!
                    $newCi[Db_ImportFileValidationAttributes::ATTRIBUTE_ID] = $r['value'];
                    $newCi[Db_ImportFileValidationAttributes::VALUE]        = $values;
                    $newCi[Db_ImportFileValidationAttributes::STATUS]       = 'idle';

                    $ciAttributeId = $importDaoImpl->insertValidationAttribute($newCi);

                    $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => added value(' . $values . ') for CI ' . $ciId . ' to validation', Zend_Log::INFO);


                    if (!$ciAttributeId) {
                        // failed
                        $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_FAILED . '. Ci Attribute Manual Validation insert failed on line ' . $parameter['line'], Zend_Log::INFO);
                        $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_UPDATE_FAILED;
                        $status['status']              = false;
                    }
                }
            }
        }
        return $status;
    }
}