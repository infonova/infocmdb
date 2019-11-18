<?php

//deprecated

class Import_File_Method_Auto_Update extends Import_File_Method_Update implements Import_File_Method
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

        $importDaoImpl = new Dao_Import();
        $ciId          = null;

        if (!$historyId) {
            $historizationUtil = new Util_Historization();
            $userId            = $parameter['userId'];
            if (!$userId)
                $userId = '0';
            $historyId           = $historizationUtil->createHistory($userId, Util_Historization::MESSAGE_IMPORT_UPDATE);
            $status['historyId'] = $historyId;
        }

        foreach ($attributeList as $rowKey => $r) {
            $foundAttribute = false;

            $historyId = null;
            foreach ($currentAttributeList as $curr) {
                $ciId = $curr[Db_CiAttribute::CI_ID];
                if ($curr[Db_CiAttribute::ATTRIBUTE_ID] == $r['value']) {
                    // check change
                    if (parent::compareAttributeValue($data[$rowKey], $curr)) {
                        // no update -> equal
                        //$return[$rowKey] = self::SUCCESS_FIELD_NO_UPDATE;
                        $logger->log($curr[Db_CiAttribute::CI_ID] . ': attribute ' . $curr[Db_CiAttribute::ATTRIBUTE_ID] . ' is equal. No action required!', Zend_Log::DEBUG);
                    } else {
                        // update
                        if (is_null($data[$rowKey]) || $data[$rowKey] == '' || $data[$rowKey] == ' ') {
                            // update not needed -> null
                            //$return[$rowKey] = self::SUCCESS_FIELD_NO_UPDATE;
                            $logger->log($curr[Db_CiAttribute::CI_ID] . ': attribute ' . $curr[Db_CiAttribute::ATTRIBUTE_ID] . ' is not set. No action required!', Zend_Log::DEBUG);
                        } else {
                            // update entry
                            $values          = array();
                            $values['value'] = parent::encode($data[$rowKey]);

                            if ($curr['type'] == Enum_AttributeType::SELECT || $curr['type'] == Enum_AttributeType::RADIO) {
                                $ret = $importDaoImpl->getDefaultValueIdByName($r['value'], $data[$rowKey]);

                                if ($ret)
                                    $values['value'] = $ret[Db_AttributeDefaultValues::ID];
                            }

                            $ciUpdate     = new Service_Ci_Update(null, $logger, 0);
                            $curr['type'] = $r['type'];

                            try {
                                $logger->log($curr[Db_CiAttribute::CI_ID] . ': update attribute ' . $curr[Db_CiAttribute::ATTRIBUTE_ID] . ' ', Zend_Log::DEBUG);
                                $ciUpdate->updateSingleAttribute(0, $curr[Db_CiAttribute::CI_ID], $curr, $values, $historyId);
                            } catch (Exception $e) {
                                $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_FAILED . '. Ci Attribute update failed on line ' . $parameter['line'], Zend_Log::CRIT);
                                $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_UPDATE_FAILED;
                                $status['status']              = false;
                            }
                        }
                    }
                    $foundAttribute = true;
                    break;
                }
            }

            if (!$foundAttribute) {
                // insert new attribute
                if (is_null($data[$rowKey]) || $data[$rowKey] == '' || $data[$rowKey] == ' ') {
                    // do nothing!
                    $logger->log($ciId . ': attribute ' . $r['value'] . ' is not set. No action required!', Zend_Log::DEBUG);
                } else {
                    $logger->log($ciId . ': attribute ' . $r['value'] . ' insert new Attribute for update!', Zend_Log::DEBUG);
                    //$uniqueId = uniqid(rand(), false);

                    $newCi                               = array();
                    $newCi[Db_CiAttribute::CI_ID]        = $ciId; // XXX: NULL wenn keine vorherigen Einträge!! Ersetzen mit besserer Lösung!!
                    $newCi[Db_CiAttribute::ATTRIBUTE_ID] = $r['value'];
                    $newCi[Db_CiAttribute::IS_INITIAL]   = 0;
                    $newCi[Db_CiAttribute::HISTORY_ID]   = $historyId;


                    $values          = array();
                    $values['value'] = parent::encode($data[$rowKey]);
                    try {
                        $attributeTypeClass = Util_AttributeType_Factory::get($r['type']);
                        $val                = $attributeTypeClass->returnFormData($values, $r[Db_Attribute::ID]);

                        if (!is_null($val[Db_CiAttribute::VALUE_DEFAULT])) {
                            $ret = $importDaoImpl->getDefaultValueIdByName($r['value'], $val[Db_CiAttribute::VALUE_DEFAULT]);
                            if ($ret)
                                $val[Db_CiAttribute::VALUE_DEFAULT] = $ret[Db_AttributeDefaultValues::ID];
                        }

                        foreach ($val as $k => $v) {
                            $newCi[$k] = $v;
                        }
                        $ciAttributeId = $importDaoImpl->insertCiAttribute($newCi);

                        // handle customization
                        $triggerUtil = new Util_Trigger($logger);
                        $triggerUtil->createAttribute($ciAttributeId, '0');
                    } catch (Exception $e) {
                        $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_UPDATE_FAILED . '. Ci Attribute update (attribute Insert) failed on line ' . $parameter['line'], Zend_Log::CRIT);
                        $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_UPDATE_FAILED;
                        $status['status']              = false;
                    }
                    if (!$ciAttributeId) {
                        // failed
                        $logger->log('[ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. Ci Attribute insert failed on line ' . $parameter['line'], Zend_Log::CRIT);
                        $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_INSERT_FAILED;
                        $status['status']              = false;
                    }
                }
            }
        }
        return $status;
    }

}