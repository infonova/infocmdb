<?php

/**
 *
 *
 *
 */
class Service_Ci_Update extends Service_Abstract
{


    public static $ciNamespace = 'CiController';
    const SESSION_ID         = 'sessionId';
    const ADDED_ATTRIBUTES   = 'addedAttributes';
    const REMOVED_ATTRIBUTES = 'removedAttributes';
    const ATTACHED_FILES     = 'attachedFiles';

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 303, $themeId);
    }


    public function getUpdateSingleAttributeForm($ciId, $ciAttributeId, $type = null, $page = null, $tabIndex = null, $userId = null)
    {
        try {
            $attributeDao = new Dao_Attribute();
            $ciAttribute  = $attributeDao->getCiAttributeById($ciAttributeId);

            $form = new Form_Ci_SingleUpdate($this->translator, $ciId, $ciAttribute, $type, $page, $tabIndex, $userId, $ciId);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
        }

        return array(
            'form'        => $form,
            'ciAttribute' => $ciAttribute,
        );
    }


    public function updateSingleAttribute($userId, $ciId, $ciAttribute, $formdata, &$historyId = null, $fireTrigger = true)
    {
        try {
            $ciAttributeId  = $ciAttribute[Db_CiAttribute::ID];
            $attributeId    = $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID];
            $ci_get_service = new Service_Ci_Get($this->translator, $this->logger, $this->getThemeId());


            if (is_array($formdata['value']))
                $formdata['value'] = implode(',', $formdata['value']);

            $attributeTypeClass = Util_AttributeType_Factory::get($ciAttribute['type']);
            $data               = $attributeTypeClass->returnFormData($formdata, $attributeId);

            // userId will be 0 if called by fileimport or other internal systems
            if((int) $userId > 0) {
                $attributeDao    = new Dao_Attribute();
                $isAttributeAllowed = $attributeDao->checkUserAttributePermission($userId, $attributeId, 'rw');
                if ($isAttributeAllowed === false) {
                    throw new Exception_AccessDenied();
                }
            }

            if ($attributeTypeClass->isEqual($ciAttribute, $data)) {
                return true;
            }

            if (!$historyId) {
                // historize action
                $historyDaoImpl = new Dao_History();
                $historyId      = $historyDaoImpl->createHistory($userId, Enum_History::CI_SINGLE_EDIT);
            }

            $data[Db_CiAttribute::HISTORY_ID] = $historyId;

            // save ci state before and after update
            $ci_info        = [];
            $ci_info['old'] = $ci_get_service->getContextInfoForCi($ciId);

            // update entry
            $attributeDaoImpl = new Dao_Attribute();
            $attributeDaoImpl->updateCiAttribute($ciAttributeId, $data);

            $ci_info['new'] = $ci_get_service->getContextInfoForCi($ciId);

            if ($fireTrigger === true) {
                // customization handling
                $triggerUtil = new Util_Trigger($this->logger);
                $triggerUtil->updateAttribute($ciAttributeId, $userId);
                $triggerUtil->updateCi($ciAttribute[Db_CiAttribute::CI_ID], $userId, $ci_info);

                //update query persistent
                $queryp_attribute = $attributeDaoImpl->getAttributesByAttributeTypeCiID($ciId, Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID);
                Util_AttributeType_Type_QueryPersist::execute_query($ciId, $queryp_attribute, $historyId);
            }

            return true;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            throw new Exception_Ci($e);
        }

        return false;
    }

    public function createValidatedAttribute($validationAttribute, $value = null, $userId = null, $historyId = null)
    {
        try {
            if (!$userId)
                $userId = $validationAttribute[Db_ImportFileValidationAttributes::USER_ID];

            $value            = ($value) ? $value : $validationAttribute[Db_ImportFileValidationAttributes::VALUE];
            $attributeId      = $validationAttribute[Db_ImportFileValidationAttributes::ATTRIBUTE_ID];
            $attributeDaoImpl = new Dao_Attribute();
            $attribute        = $attributeDaoImpl->getSingleAttribute($attributeId);

            $value = $this->validateImportValidationValue($attribute, $value);
            $ciId  = $validationAttribute[Db_ImportFileValidationAttributes::CI_ID];

            $attributeType = Util_AttributeType_Factory::get($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID]);

            $key                                           = '1';
            $attribute['genId']                            = $key;
            $values                                        = array();
            $values[$attribute[Db_Attribute::NAME] . $key] = $value;

            $data = $attributeType->addCi($values, $attribute, $ciId);
            if (!$data[Db_CiAttribute::NOTE])
                $data[Db_CiAttribute::NOTE] = $validationAttribute[Db_ImportFileValidationAttributes::NOTE];

            if (!$historyId) {
                $historizationUtil = new Util_Historization();
                $historyId         = $historizationUtil->createHistory($userId, Util_Historization::MESSAGE_IMPORT_VALIDATION_MATCH);
            }

            $data[Db_CiAttribute::HISTORY_ID] = $historyId;

            // insert in ci_attributes
            $ciDaoImpl     = new Dao_Ci();
            $ciAttributeId = $ciDaoImpl->addCiAttributeArray($ciId, $attributeId, $data, '0');

            $triggerUtil = new Util_Trigger($this->logger);
            $triggerUtil->createAttribute($ciAttributeId, $userId);

            return $historyId;
        } catch (Exception $e) {
            throw new Exception_Ci_MatchValidateAttributeFailed($e);
        }

    }

    public function updateValidatedAttributeValue($validationAttribute, $value = null, $userId = null, $historyId = null)
    {
        try {
            $attributeDao = new Dao_Attribute();
            $ciAttribute  = $attributeDao->getCiAttributeById($validationAttribute['ciAttributeId']);
            $attributeId  = $validationAttribute[Db_ImportFileValidationAttributes::ATTRIBUTE_ID];
            $value        = ($value) ? $value : $validationAttribute[Db_ImportFileValidationAttributes::VALUE];

            $attributeDaoImpl = new Dao_Attribute();
            $attribute        = $attributeDaoImpl->getSingleAttribute($attributeId);
            $value            = $this->validateImportValidationValue($attribute, $value);

            if (!$historyId) {
                $historizationUtil = new Util_Historization();
                if (!$userId)
                    $userId = $validationAttribute[Db_ImportFileValidationAttributes::USER_ID];
                $historyId = $historizationUtil->createHistory($userId, Util_Historization::MESSAGE_IMPORT_VALIDATION_MATCH);
            }

            $this->updateSingleAttribute(
                $validationAttribute[Db_ImportFileValidationAttributes::USER_ID],
                $validationAttribute[Db_ImportFileValidationAttributes::CI_ID],
                $ciAttribute,
                array(
                    'value' => $value,
                ),
                $historyId
            );

            return $historyId;
        } catch (Exception $e) {
            throw new Exception_Ci_MatchValidateAttributeFailed($e);
        }
    }


    private function validateImportValidationValue($attribute, $value)
    {
        try {
            $attributeType = Util_AttributeType_Factory::get($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID]);
            $value         = $attributeType->normalizeValue($attribute[Db_Attribute::ID], $value);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::WARN);
        }

        return $value;
    }


    public function prepareCiEdit($userId, $ciId, $ciTypeId, $removeUnique = false)
    {
        $crypt     = new Util_Crypt();
        $sessionID = self::$ciNamespace . $ciId . $crypt->create_uniqid();

        // is first call and therefore needs special treatment!
        // select all attributes and add them to the temp table!
        $attributeDao = new Dao_Attribute();
        $attributeDao->deleteTempTableForCiCreate($sessionID);
        $attributeDao->addCiAttributesToTempTable($ciId, $sessionID, $ciTypeId, $userId, $removeUnique);


        return $sessionID;

    }


    /**
     * this method is used to create the ci update form.
     */
    public function getUpdateCiForm(int $ciId, bool $isValidate, string $sessionID, string $tabIndex = null, int $attributeAttach = null, int $userId = null)
    {
        $form = new Form_Ci_Update($this->translator, $ciId, $sessionID, null, $tabIndex);

        // add attribute without attributeGroup limitation
        $form->addAttributeImgLink('general', 0, $ciId, $sessionID);
        $attributes = $this->getUpdateCiAttributes($sessionID);
        // add all attributes to form
        $currentGroupName       = null;
        $curentAttributeGroupId = null;
        foreach ($attributes as $attribute) {
            // do not add attribute if the user doesn't have the permission to read or write
            if (!$attribute[Db_AttributeRole::PERMISSION_READ] && !$attribute[Db_AttributeRole::PERMISSION_WRITE]) {
                continue;
            }

            $notNull = false;

            if ($attribute[Db_CiTypeAttribute::IS_MANDATORY] == 1) {
                $notNull = true;
            }

            if ($attribute[Db_AttributeRole::PERMISSION_READ] && !$attribute[Db_AttributeRole::PERMISSION_WRITE]) {

                $attribute[Db_CiTypeAttribute::IS_MANDATORY] = 0;

            }

            if($attributeAttach == 1){
                if (!$currentGroupName || $curentAttributeGroupId != $attribute['attribute_group_id']) {
                    $currentGroupName       = $attribute['attributeGroupName'];
                    $curentAttributeGroupId = $attribute['attribute_group_id'];

                    $form->addAttributeImgLink($currentGroupName, $curentAttributeGroupId, $ciId, $sessionID);
                }

                if (
                    !$notNull && $attribute[Db_AttributeRole::PERMISSION_WRITE]
                    && ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] != Util_AttributeType_Type_Query::ATTRIBUTE_TYPE_ID)
                    && ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] != Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID)
                ) {
                    $form->addAttributeImgRemoveLink($attribute['genId'], $sessionID, $ciId);
                }
            }

            $form->addAttribute($attribute, $attribute['genId'], $ciId, $isValidate, $userId);
        }

        $form->addSubmitButton($ciId);

        return $form;
    }


    /**
     *
     * @param integer $ciId
     * @param array $attributes
     *
     * @return array
     */
    public function getUpdateCiFormData($ciId, $attributes)
    {
        //select all form data
        $ciDaoImpl   = new Dao_Ci();
        $values      = $ciDaoImpl->getCiConfigurationStatementByCiId($ciId, $attributes);
        $motherArray = array();

        $formData = array();
        $v2delete = $values;
        foreach ($attributes as $attribute) {
            $count     = 0;
            $storedIDs = array();

            foreach ($v2delete as $skey => $val) {
                if ($val['ciAttributeId'] == $attribute['ciAttributeId']) {
                    $count++;
                    array_push($storedIDs, $skey);
                    unset($v2delete[$skey]);
                    break;
                }
            }

            $attribute['ci_id'] = $ciId;
            if ($count == 1) {
                $attributeType = Util_AttributeType_Factory::get($attribute['type']);
                $formData      = $attributeType->addFormData($formData, $attribute, $values, $storedIDs);

            } else if ($count > 1) {
                if (!$motherArray[$attribute[Db_Attribute::ID]]) {
                    $attributeType = Util_AttributeType_Factory::get($attribute['type']);
                    $formData      = $attributeType->addFormData($formData, $attribute, $values, $storedIDs);

                    $dataToStore                               = array();
                    $dataToStore[$storedIDs[0]]                = true;
                    $motherArray[$attribute[Db_Attribute::ID]] = $dataToStore;
                } else {
                    $dataToStore = $motherArray[$attribute[Db_Attribute::ID]];
                    foreach ($storedIDs as $id) {
                        if (!array_key_exists($id, $dataToStore)) {
                            $dataToStore[$id]                          = true;
                            $motherArray[$attribute[Db_Attribute::ID]] = $dataToStore;

                            $attributeType = Util_AttributeType_Factory::get($attribute['type']);
                            $formData      = $attributeType->addFormData($formData, $attribute, $values, $storedIDs);
                        }
                    }
                }
            }
        }

        return $formData;
    }


    /**
     * updates a ci
     */
    public function updateCi($userId, $ciId, $values, $attributeList, $sessionID)
    {
        try {

            $ciDaoImpl        = new Dao_Ci();
            $attributeDaoImpl = new Dao_Attribute();
            $historyDao       = new Dao_History();
            $triggerUtil      = new Util_Trigger($this->logger);
            $del              = new Service_Ci_Delete($this->translator, $this->logger, $this->getThemeId());
            $ci_get_service   = new Service_Ci_Get($this->translator, $this->logger, $this->getThemeId());

            $attributesFromSession = $this->getUpdateCiAttributes($sessionID);
            $historyId             = $historyDao->createHistory($userId, Enum_History::CI_UPDATE);
            $currentDatabaseValues = $ciDaoImpl->getCiConfigurationStatementByCiId($ciId, $attributesFromSession);

            // save ci state before and after update
            $ciInfo        = array();
            $ciInfo['old'] = $ci_get_service->getContextInfoForCi($ciId);

            // restructure array of database values --> ci-attribute-ID as index of array
            $currentDatabaseValList = array();
            foreach ($currentDatabaseValues as $val) {
                $currentDatabaseValList[$val['ciAttributeId']] = $val; //ciAttributeId
            }
            unset($currentDatabaseValues); // free mem

            //used for triggering workflows after saving data
            $ciChanged              = false;
            $triggerUpdateAttribute = array();
            $triggerCreateAttribute = array();

            foreach ($attributeList as $attribute) {

                $attributeTypeClass     = Util_AttributeType_Factory::get($attribute['type']); // get class of attribute-type via factory
                $genericId              = $attribute['genId']; // generic ID is used to separate multiple values of the same attribute
                $attributeObjectOptions = $attributeTypeClass->getCurrentAttributeValue($values, $attribute, $genericId, $ciId); // get options like "allowEmpty" or "skipUpdate" from attribute-type-class
                $ciAttributeId          = $attribute['ciAttributeId'];
                $currentValue           = $attributeObjectOptions['value']; // value via attribute-type-class

                // remove attribute from $currentDatabaseValList if the user has not the permission to write --> prevent deleting of ci attribute
                // at the end of this method all remaining values in $currentDatabaseValList will be deleted --> not handled via logic means no value was given via form --> delete
                if ($attribute['permission_write'] == 0) {
                    unset($currentDatabaseValList[$ciAttributeId]);
                }

                // skip update for specific attribute types where the user should not be allowed to edit the attribute
                // defined via Util_AttributeType_Type_XY->getCurrentAttributeValue
                if ($attributeObjectOptions['skipUpdate']) {
                    if (isset($currentDatabaseValList[$ciAttributeId])) {
                        unset($currentDatabaseValList[$ciAttributeId]);
                    }
                    continue;
                }

                // handles new or update ci_attributes
                if ((isset($currentValue) && $currentValue != "") || $attributeObjectOptions['allowEmpty']) {

                    /*
                     * getCiEditData returns an array of columns which should be updated. (should only be the the value column of the specific attribute type)
                     *
                     * array( 'value_text' => 'mytext' )
                     * OR
                     * array( 'value_default' => 1234 )
                     * ...
                     */
                    $data = $attributeTypeClass->getCiEditData($values, $attribute, $genericId, $currentValue, $ciId);

                    // if there is an existing attribute value --> UPDATE
                    if (isset($currentDatabaseValList[$ciAttributeId])) {

                        //check if value has changed
                        if (!$attributeTypeClass->isEqual($currentDatabaseValList[$ciAttributeId], $data)) {

                            $data[Db_CiAttribute::HISTORY_ID] = $historyId; // add history_id to the list of columns we want to update

                            // update ci-attribute-row with the id we received from database and update columns which are defined in $data
                            $attributeDaoImpl->updateCiAttribute($ciAttributeId, $data);
                            $ciChanged = true;

                            // handle trigger
                            $triggerUpdateAttribute[$ciAttributeId] = $userId;
                        }
                        unset($currentDatabaseValList[$ciAttributeId]);
                    } else {
                        // ELSE new value -> INSERT
                        if ($attribute['attribute_type_id'] != Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID) {

                            $data[Db_CiAttribute::HISTORY_ID] = $historyId; // add history_id to the list of columns we want to update

                            // insert ci-attribute-row with the columns which is defined in $data
                            // the method will handle additional columns via params given
                            $ciAttributeId = $ciDaoImpl->addCiAttributeArray($ciId, $attribute[Db_Attribute::ID], $data, $attribute['initial']);
                            $ciChanged     = true;

                            // handle trigger
                            $triggerCreateAttribute[$ciAttributeId] = $userId;
                        }

                    }
                }
            }

            // add attributes with specific type to CI if they are missing (also if the user has NOT the permission to "write")
            Util_AttributeType_Type_Executeable::insertMissingAttributes($ciId, $historyId);
            Util_AttributeType_Type_Info::insertMissingAttributes($ciId, $historyId);


            // iterate through remaining ci_attributes of the given ci and delete all attributes that are no longer in the form. (usually due to deletion)
            // TODO: enhance ci update to make 100% sure not to delete entries if form submit fails.

            // fire trigger before delete to don't loose data
            foreach ($currentDatabaseValList as $genericId => $toDelete) {
                // fire trigger
                $triggerUtil->deleteAttribute($toDelete['ciAttributeId'], $userId);
            }

            // after firing trigger create history and delete
            foreach ($currentDatabaseValList as $genericId => $toDelete) {
                // delete entry
                $del->deleteSingleCiAttribute($toDelete['ciAttributeId'], $historyId);
                $ciChanged = true;

            }
            // update ci icon
            $iconChanged = false;
            if ($values['ciicon_delete'] == 1) { // remove icon
                $newFilename = '';
                $iconChanged = true;
            } elseif (!empty($values['ciicon'])) { // new icon
                $icon        = $values['ciicon'];
                $date        = date("YmdHms");

                $tmpUploadPath   = Util_FileUpload::getUploadPath('tmp');
                $destinationPath = Util_FileUpload::getUploadPath('icon');

                $fileinfo = finfo_open(FILEINFO_MIME_TYPE);
                $filetype = finfo_file($fileinfo, $tmpUploadPath .'/'. $icon);

                //check icon mime type
                $allowedTypes = [
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    'image/gif' => 'gif'
                ];

                $extension = $allowedTypes[$filetype];
                $newFilename = htmlspecialchars($date .'-'. $ciId . '-icon.' . $extension);

                if (!in_array($filetype, array_keys($allowedTypes))) {
                    throw new Exception_Ci_WrongIconType();
                }

                // rename icon
                if (!rename($tmpUploadPath .'/'. $icon, $destinationPath .'/'. $newFilename)) {
                    throw new Exception_File_RenamingFailed();
                }

                $iconChanged = true;
            }

            if ($iconChanged === true) {
                $ciDaoImpl->updateCiIcon($ciId, $newFilename);
                $ciChanged = true;
            }


            //Execute Triggers
            foreach ($triggerUpdateAttribute as $genericId => $value) {
                $triggerUtil->updateAttribute($genericId, $value);
            }
            foreach ($triggerCreateAttribute as $genericId => $value) {
                $triggerUtil->createAttribute($genericId, $value);
            }

            $ciInfo['new'] = $ci_get_service->getContextInfoForCi($ciId);

            if ($ciChanged === true) {
                $triggerUtil->updateCi($ciId, $userId, $ciInfo);
            }

            // update query persistent
            // This is called after trigger, because values can be changed by workflows. If we call this after triggering workflows, we can be sure all values are correct.
            $queryPersistentAttributes = $attributeDaoImpl->getAttributesByAttributeTypeCiID($ciId, Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID);
            Util_AttributeType_Type_QueryPersist::execute_query($ciId, $queryPersistentAttributes, $historyId);

        } catch (Exception $e) {
            if ($e instanceof Exception_Ci_WrongIconType)
                throw $e;

            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Ci_Unknown($e);
        }
    }

    /**
     * updates a ci
     *
     * @param int $userId
     * @param int $ciId
     * @param int $newCiTypeId
     *
     * @throws Exception_Ci_Unknown
     */
    public function updateCiType(int $userId, int $ciId, int $newCiTypeId)
    {
        try {
            $historyDao = new Dao_History();
            $historyId  = $historyDao->createHistory($userId, Enum_History::CI_TYPE_CHANGE);

            $ciDaoImpl = new Dao_Ci();
            $ciDaoImpl->updateCiType($ciId, $newCiTypeId, $historyId);

            $triggerUtil = new Util_Trigger($this->logger);
            $triggerUtil->handleCiTypeChange($ciId, $userId, 'update');//will be triggered from and to ci_type
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Ci_Unknown($e);
        }
    }


    /**
     * retrieve all attributes mapped to the current ci update logic
     */
    public function getUpdateCiAttributes($sessionId)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $attributes       = $attributeDaoImpl->getAttributesFromTempTable($sessionId);
        foreach ($attributes as $index => $attributeContent) {
            if ($attributeContent['permission_read'] == '0') {
                unset($attributes[$index]);
            }
        }
        return $attributes;
    }
}
