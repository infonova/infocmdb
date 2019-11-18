<?php

class Import_File_Method_Auto_Import extends Import_File_Method_Import implements Import_File_Method
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

        $importObject = null;

        return $status;
    }

    private function insert(&$logger, &$importObject, $historyId, $parameter)
    {

        $importUtil       = new Import_File_Util_Object_Import();
        $importDaoImpl    = new Dao_Import(false);
        $attributeDaoImpl = new Dao_Attribute(false);
        $triggerUtil      = new Util_Trigger($logger);
        $attributeList    = $importObject->getAttributeList();
        $row              = $importObject->getData();
        $status           = $importObject->getStatus();

        $project = $importObject->getProject();
        $ciType  = $importObject->getCiType();

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
        $data[Db_Ci::CI_TYPE_ID] = $ciType;
        $data[Db_Ci::HISTORY_ID] = $historyId;

        try {
            $ciId = $importDaoImpl->insertCi($data);
            $logger->log('line: ' . $parameter['line'] . '=> created new CI with id ' . $ciId, Zend_Log::INFO);
        } catch (Exception $e) {
            $logger->log('line: ' . $parameter['line'] . '=> [ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. Creating new CI failed', Zend_Log::CRIT);
            $status['errors'][0] = Import_File_Code::ERROR_INSERT_FAILED;
            $status['status']    = false;
            return $status;
        }


        // create project mapping
        $data                           = array();
        $data[Db_CiProject::CI_ID]      = $ciId;
        $data[Db_CiProject::PROJECT_ID] = $project;
        $data[Db_CiProject::HISTORY_ID] = $historyId;

        try {
            $importDaoImpl->insertCiProject($data);
            $logger->log('line: ' . $parameter['line'] . '=> added ci project mapping for CI ' . $ciId, Zend_Log::INFO);
        } catch (Exception $e) {
            $logger->log('line: ' . $parameter['line'] . '=> [ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. adding CI Project mapping failed for CI ' . $ciId, Zend_Log::CRIT);
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

                        $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => invalid default value => no update!', Zend_Log::ERR);
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
                $data[Db_CiAttribute::NOTE]         = 'automatic import ' . time();
                $data[Db_CiAttribute::HISTORY_ID]   = $historyId;

                // insert new attribute
                if (is_null($row[$rowKey]) || $row[$rowKey] == '' || $row[$rowKey] == ' ') {
                    // warning is enough
                    $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => value for attribute was empty', Zend_Log::INFO);
                }

                $ciAttributeId = $importDaoImpl->insertCiAttribute($data);

                if (is_null($ciAttributeId)) {
                    // failed
                    $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => [ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. adding ci attribute to validation failed ', Zend_Log::ERR);
                    $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_INSERT_FAILED;
                    $status['status']              = false;

                } else {
                    $triggerUtil->createAttribute($ciAttributeId, 0);
                    // success
                    $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => added ci attribute value "' . $row[$rowKey] . '" to validation', Zend_Log::INFO);
                }
            }
        }
        //update query persistent
        $queryp_attribute = $attributeDaoImpl->getAttributesByAttributeTypeCiID($ciId, Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID);
        Util_AttributeType_Type_QueryPersist::execute_query($ciId, $queryp_attribute, $historyId);

        //add missing executable attributes
        Util_AttributeType_Type_Executeable::insertMissingAttributes($ciId, $historyId);

        //CI trigger
        $triggerUtil->createCi($ciId, 0);

        $importUtil        = null;
        $importDaoImpl     = null;
        $attributeDaoImpl  = null;
        $triggerUtil       = null;
        $attributeList     = null;
        $row               = null;
        $project           = null;
        $ciType            = null;
        $historyId         = null;
        $userId            = null;
        $parameter         = null;
        $data              = null;
        $historizationUtil = null;
        $attributeList     = null;
        $rowKey            = null;
        $r                 = null;
        $class             = null;
        $val               = null;
        $defaultvalues     = null;
        $update            = null;
        $ciAttributeId     = null;
        $queryp_attribute  = null;

        return $status;
    }

    private function update(&$logger, &$importObject, $historyId, $parameter)
    {
        $update               = true;
        $importUtil           = new Import_File_Util_Object_Import();
        $importDaoImpl        = new Dao_Import(false);
        $attributeDaoImpl     = new Dao_Attribute(false);
        $attributeList        = $importObject->getAttributeList();
        $currentAttributeList = $importObject->getCurrentAttributeList();
        $data                 = $importObject->getData();
        $status               = $importObject->getStatus();
        $ciUpdate             = new Service_Ci_Update(null, $logger, 0);
        $triggerUtil          = new Util_Trigger($logger);
        $ciChanged            = false;

        if (!$historyId) {
            $historizationUtil = new Util_Historization();
            $userId            = $parameter['userId'];
            if (!$userId)
                $userId = '0';
            $historyId           = $historizationUtil->createHistory($userId, Util_Historization::MESSAGE_IMPORT_UPDATE);
            $status['historyId'] = $historyId;
        }

        $ciId = $currentAttributeList[0][Db_CiAttribute::CI_ID];
        $logger->log('line: ' . $parameter['line'] . ' => Handle CI with id ' . $ciId, Zend_Log::INFO);


        foreach ($attributeList as $rowKey => $r) {

            $foundAttribute = false;

            foreach ($currentAttributeList as $curr) {
                if ($curr[Db_CiAttribute::ATTRIBUTE_ID] == $r['value']) {
                    if (is_null($data[$rowKey]) || $data[$rowKey] == '' || $data[$rowKey] == ' ') {
                        // update not needed -> value not set
                        $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => attribute is not set. No action required => not updated!', Zend_Log::INFO);
                    } else {

                        if (($curr['type'] == Enum_AttributeType::SELECT) || ($curr['type'] == Enum_AttributeType::RADIO) || ($curr['type'] == Enum_AttributeType::CHECKBOX)) {

                            $defaultvalues = $importUtil->getdefaultValuesbyName($data[$rowKey], $curr[Db_CiAttribute::ATTRIBUTE_ID]);
                            $data[$rowKey] = $defaultvalues;

                            if (is_null($defaultvalues)) {
                                $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => invalid default value => not updated!', Zend_Log::ERR);
                                $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_DEFAULT_VALUE;
                                $status['status']              = false;
                                $update                        = false;
                            }

                        }

                        // check change
                        if (parent::compareAttributeValue($data[$rowKey], $curr)) {
                            // no update -> equal
                            $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => attribute has an equal value. not updated!', Zend_Log::INFO);
                        } else {

                            // update entry
                            $values          = array();
                            $values['value'] = parent::encode($data[$rowKey]);


                            $curr['type'] = $r['type'];

                            try {
                                if ($update) {
                                    $ciUpdate->updateSingleAttribute(0, $curr[Db_CiAttribute::CI_ID], $curr, $values, $historyId);
                                    $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => value_old = "' . $curr[Db_CiAttribute::VALUE_TEXT] . $curr[Db_CiAttribute::VALUE_DATE] . $curr[Db_CiAttribute::VALUE_DEFAULT] . '" value_new = "' . $data[$rowKey] . '" => updated!', Zend_Log::INFO);
                                    $ciChanged = true;
                                }
                            } catch (Exception $e) {
                                $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => [ERROR] Code ' . Import_File_Code::ERROR_UPDATE_FAILED . '. Ci Attribute update failed', Zend_Log::CRIT);
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
                    $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => attribute is not set. No action required => not updated!', Zend_Log::INFO);
                } else {

                    //$uniqueId = uniqid(rand(), false);

                    $newCi                               = array();
                    $newCi[Db_CiAttribute::CI_ID]        = $ciId; // XXX: NULL wenn keine vorherigen Einträge!! Ersetzen mit besserer Lösung!!
                    $newCi[Db_CiAttribute::ATTRIBUTE_ID] = $r['value'];
                    $newCi[Db_CiAttribute::IS_INITIAL]   = '1';
                    $newCi[Db_CiAttribute::HISTORY_ID]   = $historyId;


                    $values          = array();
                    $values['value'] = $data[$rowKey];

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
                    $ciChanged     = true;

                    // handle customization
                    $triggerUtil->createAttribute($ciAttributeId, '0');

                    if (!$ciAttributeId) {
                        // failed
                        $logger->log('line: ' . $parameter['line'] . ', attribute: ' . $r['name'] . ' => [ERROR] Code ' . Import_File_Code::ERROR_INSERT_FAILED . '. Ci Attribute insert failed', Zend_Log::CRIT);
                        $status['errors'][$rowKey + 1] = Import_File_Code::ERROR_INSERT_FAILED;
                        $status['status']              = false;
                    }
                }
            }
        }
        //update query persistent
        $queryp_attribute = $attributeDaoImpl->getAttributesByAttributeTypeCiID($ciId, Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID);
        Util_AttributeType_Type_QueryPersist::execute_query($ciId, $queryp_attribute, $historyId);


        if ($ciChanged === true) {
            //CI trigger
            $triggerUtil->updateCi($ciId, 0);
        }

        $update               = null;
        $importUtil           = null;
        $importDaoImpl        = null;
        $attributeDaoImpl     = null;
        $attributeList        = null;
        $currentAttributeList = null;
        $data                 = null;
        $ciUpdate             = null;
        $triggerUtil          = null;
        $parameter            = null;
        $userId               = null;
        $historyId            = null;
        $ciId                 = null;
        $ciAttributeId        = null;
        $curr                 = null;
        $defaultvalues        = null;
        $foundAttribute       = null;
        $rowKey               = null;
        $r                    = null;
        $ret                  = null;
        $update               = null;
        $values               = null;
        $v                    = null;
        $newCi                = null;
        $attributeTypeClass   = null;
        $historizationUtil    = null;

        return $status;
    }

}