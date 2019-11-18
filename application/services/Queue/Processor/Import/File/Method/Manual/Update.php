<?php

class Import_File_Method_Manual_Update extends Import_File_Method_Update implements Import_File_Method
{

    public function import(&$logger, $historyId, &$row, $attributeList, $parameter = array())
    {
        $updateObject = parent::import($logger, $historyId, $row, $attributeList, $parameter);
        $status       = $updateObject->getStatus();

        if (!$status['status']) {
            return $status;
        }

        $attributeList        = $updateObject->getAttributeList();
        $currentAttributeList = $updateObject->getCurrentAttributeList();
        $data                 = $updateObject->getData();

        $validationId  = $parameter['validationId'];
        $importDaoImpl = new Dao_Import();
        $ciId          = null;

        foreach ($attributeList as $rowKey => $r) {
            $foundAttribute = false;

            foreach ($currentAttributeList as $curr) {
                $ciId = $curr[Db_CiAttribute::CI_ID];
                if (is_null($data[$rowKey]) || $data[$rowKey] == '' || $data[$rowKey] == ' ') {
                    $logger->log($ciId . ': attribute ' . $r['value'] . ' is not set. No action required!', Zend_Log::DEBUG);
                } else if ($curr[Db_CiAttribute::ATTRIBUTE_ID] == $r['value']) {
                    // check change
                    if (self::compareAttributeValue($data[$rowKey], $curr)) {
                        // no update -> equal
                        //$return[$rowKey] = self::SUCCESS_FIELD_NO_UPDATE;
                        $logger->log($curr[Db_CiAttribute::CI_ID] . ': attribute ' . $curr[Db_CiAttribute::ATTRIBUTE_ID] . ' is equal. No action required!', Zend_Log::INFO);
                    } else {

                        // update if $data[$rowKey] -> delete, else real update
                        $values = parent::encode($data[$rowKey]);

                        if ($curr['type'] == Enum_AttributeType::DATE || $curr['type'] == Enum_AttributeType::DATE_TIME) {
                            $values = $date = date('Y-m-d H:i:s', strtotime($data[$rowKey]));
                        }


                        $newCi                                                   = array();
                        $newCi[Db_ImportFileValidationAttributes::VALIDATION_ID] = $validationId;
                        $newCi[Db_ImportFileValidationAttributes::UNIQUE_ID]     = $curr[Db_CiAttribute::ID];
                        $newCi[Db_ImportFileValidationAttributes::CI_ID]         = $curr[Db_CiAttribute::CI_ID]; // XXX: NULL wenn keine vorherigen Einträge!! Ersetzen mit besserer Lösung!!
                        $newCi[Db_ImportFileValidationAttributes::ATTRIBUTE_ID]  = $curr[Db_CiAttribute::ATTRIBUTE_ID];
                        $newCi[Db_ImportFileValidationAttributes::VALUE]         = $values;
                        $newCi[Db_ImportFileValidationAttributes::STATUS]        = 'idle';

                        $ciAttributeId = $importDaoImpl->insertValidationAttribute($newCi);

                        if (!$ciAttributeId) {
                            // failed
                            $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_FAILED . '. Ci Attribute Manual Validation insert failed on line ' . $parameter['line'], Zend_Log::CRIT);
                            $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_UPDATE_FAILED;
                            $status['status']              = false;
                        }
                    }
                    $foundAttribute = true;
                    break;
                }
            }


            // TODO: insert
            if (!$foundAttribute) {
                // insert new attribute
                if (is_null($data[$rowKey]) || $data[$rowKey] == '' || $data[$rowKey] == ' ') {
                    $logger->log($ciId . ': attribute ' . $r['value'] . ' is not set. No action required!', Zend_Log::DEBUG);
                } else {
                    //$uniqueId = uniqid(rand(), false);

                    $values = parent::encode($data[$rowKey]);

                    if ($curr['type'] == Enum_AttributeType::DATE || $curr['type'] == Enum_AttributeType::DATE_TIME) {
                        $values = $date = date('Y-m-d H:i:s', strtotime($data[$rowKey]));
                    }

                    $newCi                                                   = array();
                    $newCi[Db_ImportFileValidationAttributes::VALIDATION_ID] = $validationId;
                    //$newCi[Db_ImportFileValidationAttributes::UNIQUE_ID] = $uniqueId;
                    $newCi[Db_ImportFileValidationAttributes::CI_ID]        = $ciId; // XXX: NULL wenn keine vorherigen Einträge!! Ersetzen mit besserer Lösung!!
                    $newCi[Db_ImportFileValidationAttributes::ATTRIBUTE_ID] = $r['value'];
                    $newCi[Db_ImportFileValidationAttributes::VALUE]        = $values;
                    $newCi[Db_ImportFileValidationAttributes::STATUS]       = 'idle';

                    $ciAttributeId = $importDaoImpl->insertValidationAttribute($newCi);

                    if (!$ciAttributeId) {
                        // failed
                        $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_FAILED . '. Ci Attribute Manual Validation insert failed on line ' . $parameter['line'], Zend_Log::CRIT);
                        $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_UPDATE_FAILED;
                        $status['status']              = false;
                    }
                }
            }
        }
        return $status;
    }
}