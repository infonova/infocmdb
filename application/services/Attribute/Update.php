<?php

/**
 *
 *
 *
 */
class Service_Attribute_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 103, $themeId);
    }

    public function getAllCiTypes()
    {
        $ciTypeDaoImpl = new Dao_CiType();
        return $ciTypeDaoImpl->getCiTypeRowset();
    }

    public function getAllRoles()
    {
        $attributeDaoImpl = new Dao_Attribute();
        return $attributeDaoImpl->getRolesForAttributes();
    }

    public function updateAttribute($attributeId, $formData, $dbData)
    {

        // for comparison purposes
        $attributeServiceGet = new Service_Attribute_Get($this->translator,
            $this->logger, parent::getThemeId());
        $attributeStored     = $attributeServiceGet->getAttibute($attributeId);

        try {
            $dbUpdate = false;

            foreach ($formData as $key => $value) {
                if ($formData[$key] != $dbData[$key])
                    $updateData[$key] = $value;
            }

            $attribute = array();
            if ($updateData['name'] !== null)
                $attribute[Db_Attribute::NAME] = trim($this->xssClean(
                    $updateData['name']));
            if ($updateData['description'] !== null)
                $attribute[Db_Attribute::DESCRIPTION] = trim($this->xssClean(
                    $updateData['description']));
            if ($updateData['note'] !== null)
                $attribute[Db_Attribute::NOTE] = trim($this->xssClean(
                    $updateData['note']));
            if ($updateData['displayType'] !== null)
                $attribute[Db_Attribute::ATTRIBUTE_GROUP_ID] = $updateData['displayType'];
            if ($updateData['sorting'] !== null)
                $attribute[Db_Attribute::ORDER_NUMBER] = $updateData['sorting'];
            if ($updateData['hint'] !== null)
                $attribute[Db_Attribute::HINT] = Form_Decorator_MyTooltip::sanitizeHint($updateData['hint']);
            if ($updateData['column'] !== null)
                $attribute[Db_Attribute::COLUMN] = $updateData['column'];
            if ($updateData['highlightAttribute'] !== null)
                $attribute[Db_Attribute::IS_BOLD] = $updateData['highlightAttribute'];
            if ($updateData['text']['uniqueConstraint'] !== null)
                $attribute[Db_Attribute::IS_UNIQUE] = $updateData['text']['uniqueConstraint'];
            if ($updateData['xml'] !== null)
                $attribute[Db_Attribute::TAG] = $updateData['xml'];
            if ($updateData['text']['uniqueCheck'] !== null)
                $attribute[Db_Attribute::IS_UNIQUE_CHECK] = $updateData['text']['uniqueCheck'];
            if ($updateData['text']['isNumeric'] !== null)
                $attribute[Db_Attribute::IS_NUMERIC] = $updateData['text']['isNumeric'];

            if ($updateData['query']['isLight'] !== null)
                $attribute[Db_Attribute::IS_EVENT] = $updateData['query']['isLight'];

            if ($updateData['workflow_id'] !== null)
                $attribute[Db_Attribute::WORKFLOW_ID] = $updateData['workflow_id'];

            if ($updateData['isevent'] !== null)
                $attribute[Db_Attribute::IS_EVENT] = $updateData['isevent'];

            if ($updateData['query']['displayStyle'] !== null) {
                if ($updateData['query']['displayStyle'] === '') {
                    $attribute[Db_Attribute::DISPLAY_STYLE] = null;
                } else {
                    $attribute[Db_Attribute::DISPLAY_STYLE] = $updateData['query']['displayStyle'];
                }
            }

            if ($updateData['attributeType'] !== null) {
                $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] = $updateData['attributeType'];
                $class                                      = Util_AttributeType_Factory::get(
                    $formData['attributeType']);
            }

            if ($updateData['text']['inputLength'] !== null)
                $attribute[Db_Attribute::INPUT_MAXLENGTH] = $updateData['text']['inputLength'];
            if ($updateData['text']['textfieldWidth'] !== null)
                $attribute[Db_Attribute::TEXTAREA_COLS] = $updateData['text']['textfieldWidth'];
            if ($updateData['citype']['textfieldWidth'] !== null)
                $attribute[Db_Attribute::TEXTAREA_COLS] = $updateData['citype']['textfieldWidth'];
            if ($updateData['query']['textfieldWidth'] !== null)
                $attribute[Db_Attribute::TEXTAREA_COLS] = $updateData['query']['textfieldWidth'];
            if ($updateData['text']['textfieldHeight'] !== null)
                $attribute[Db_Attribute::TEXTAREA_ROWS] = $updateData['text']['textfieldHeight'];
            if ($updateData['text']['regex'] !== null)
                $attribute[Db_Attribute::REGEX] = $updateData['text']['regex'];

            if ($updateData['citype']['ciTypeAutocomplete'] !== null)
                $attribute[Db_Attribute::IS_AUTOCOMPLETE] = $updateData['citype']['ciTypeAutocomplete'];

            if ($updateData['query']['ciTypeAutocomplete'] !== null)
                $attribute[Db_Attribute::IS_AUTOCOMPLETE] = $updateData['query']['ciTypeAutocomplete'];

            if ($updateData['citype']['ProjectRestriction'] !== null)
                $attribute[Db_Attribute::IS_PROJECT_RESTRICTED] = $updateData['citype']['ProjectRestriction'];

            if ($updateData['query']['ProjectRestriction'] !== null)
                $attribute[Db_Attribute::IS_PROJECT_RESTRICTED] = $updateData['query']['ProjectRestriction'];

            if ($updateData['query']['autocomplete'] !== null) {
                switch ($updateData['query']['autocomplete']) {
                    case 'select_one':
                        $attribute[Db_Attribute::IS_AUTOCOMPLETE] = '0';
                        $attribute[Db_Attribute::IS_MULTISELECT]  = '0';
                        break;
                    case 'autocomplete_one':
                        $attribute[Db_Attribute::IS_AUTOCOMPLETE] = '1';
                        $attribute[Db_Attribute::IS_MULTISELECT]  = '0';
                        break;
                    case 'autocomplete_multiple':
                        $attribute[Db_Attribute::IS_AUTOCOMPLETE] = '1';
                        $attribute[Db_Attribute::IS_MULTISELECT]  = '1';
                        break;
                    case 'autocomplete_multiple_with_counter':
                        $attribute[Db_Attribute::IS_AUTOCOMPLETE] = '1';
                        $attribute[Db_Attribute::IS_MULTISELECT]  = '2';
                        break;
                }
            }


            try {
                $oldScriptContentCompare = preg_replace("/[\n\r]/", "", $attributeServiceGet->getScriptContent($attributeStored[Db_Attribute::SCRIPT_NAME]));
                $newScriptContentCompare = preg_replace("/[\n\r]/", "", $formData['script']['script_text']);
                $oldAttributeName        = $attributeStored[Db_Attribute::NAME];

                if (!empty($formData['script']['script_text']) && ($oldScriptContentCompare != $newScriptContentCompare || $oldAttributeName != $formData[Db_Attribute::NAME])) {

                    if ($updateData['name'] != '') {
                        $archiveFolder = $attributeId . '__' . $updateData['name'];
                    } else {
                        $archiveFolder = $attributeId . '__' . $oldAttributeName;
                    }
                    Util_Workflow::archiveScript($attributeStored[Db_Attribute::SCRIPT_NAME], $archiveFolder, 'executable');

                    $docHead = Util_Workflow::getDocHeadPartsForExecutable($formData);
                    $script  = Util_Workflow::saveScriptToFile($formData['name'] . ".pl", $formData['script']['script_text'], $docHead, 'executable');

                    // update scriptname in DB
                    $attribute[Db_Attribute::SCRIPT_NAME] = $script['script'];
                }

                $attributeDaoImpl = new Dao_Attribute();

                if (!empty($attribute)) {

                    $attributeDaoImpl->updateAttribute($attribute, $attributeId);
                    $this->checkCiTypePersistentUpdate($attributeId);

                    $dbUpdate = true;
                }

                if ($updateData['citype']) {
                    $attributeDaoImpl->deleteCiTypeAttributes($attributeId);
                    $attributeDaoImpl->insertCiTypeAttribute(
                        $updateData['citype']['ciType'],
                        $updateData['citype']['ciTypeAttributes'],
                        $attributeId);
                    $dbUpdate = true;
                }

                if ($updateData['query']['query'] || $updateData['query']['listQuery']) {
                    $attributeDaoImpl->deleteDefaultQueryByAttributeId($attributeId);
                    $attributeDaoImpl->insertDefaultQuery($attributeId,
                        $updateData['query']['query'], $updateData['query']['listQuery']);
                    $dbUpdate = true;
                }

                if ($updateData['filter']['filter']) {
                    $attributeDaoImpl->deleteDefaultQueryByAttributeId(
                        $attributeId);

                    $keywords  = array();
                    $statement = $updateData['filter']['filter'];
                    $qId       = $attributeDaoImpl->insertDefaultQuery($attributeId,
                        $statement);
                    preg_match_all("/:([^:]*):/", $statement, $keywords);

                    foreach ($keywords[1] as $val) {
                        $attributeDaoImpl->insertDefaultQueryParameter($qId,
                            $val);
                    }
                    $dbUpdate = true;
                }

                if ($updateData['queryPersist']) {
                    if ($updateData['queryPersist']['usescript']) {
                        $adf = ':script#:' .
                            $updateData['queryPersist']['scriptfilename'];
                    } else {
                        $adf = $updateData['queryPersist']['query'];
                    }
                    $attributeDaoImpl->deleteDefaultQueryByAttributeId(
                        $attributeId);
                    $attributeDaoImpl->insertDefaultQuery($attributeId, $adf);
                    $dbUpdate = true;
                }

                if ($updateData['info']['attributeInfo']) {
                    $attributeDaoImpl->deleteAttributeDefaultValuesByAttributeId($attributeId);
                    $attributeDaoImpl->insertAttributeDefaultValuesById($updateData['info']['attributeInfo'], $attributeId);
                    $dbUpdate = true;
                }

                // Input Textarea Textedit Default Values
                if ($formData['attributeType'] ==
                    Util_AttributeType_Type_Input::ATTRIBUTE_TYPE_ID ||
                    $formData['attributeType'] ==
                    Util_AttributeType_Type_Textarea::ATTRIBUTE_TYPE_ID ||
                    $formData['attributeType'] ==
                    Util_AttributeType_Type_TextEdit::ATTRIBUTE_TYPE_ID) {

                    if ($formData['text']['defaultvalue']) {
                        $isvalue = $attributeDaoImpl->getAttributeDefaultValues(
                            $attributeId);
                        if ($isvalue) {
                            $attributeDaoImpl->updateAttributeDefaultValuesById(
                                $formData['text']['defaultvalue'],
                                $attributeId);
                        } else {
                            $attributeDaoImpl->insertAttributeDefaultValuesById(
                                $formData['text']['defaultvalue'],
                                $attributeId);
                        }
                    } else {
                        $attributeDaoImpl->deleteAttributeDefaultValuesByAttributeId(
                            $attributeId);
                    }
                }

                $ciTypeDaoImpl = new Dao_CiType();

                if (is_array($updateData))
                    foreach ($updateData as $id => $value) {
                        if (strpos($id, 'citypeId_') === 0) {
                            if ($value) {
                                $ciTypeDaoImpl->deleteCiTypeAttribute(
                                    substr($id, strlen('citypeId_')),
                                    $attributeId);
                                $ciTypeDaoImpl->saveCiTypeAttribute(
                                    substr($id, strlen('citypeId_')),
                                    $attributeId,
                                    (($value == '1') ? '1' : '0'));
                                $dbUpdate = true;
                            } else {
                                $ciTypeDaoImpl->deleteCiTypeAttribute(
                                    substr($id, strlen('citypeId_')),
                                    $attributeId);
                                $dbUpdate = true;
                            }
                        } elseif (strpos($id, 'roleId_') === 0) {
                            if ($value == '0') {
                                $read  = '0';
                                $write = '0';
                            } elseif ($value == '1') {
                                $read  = '1';
                                $write = '0';
                            } elseif ($value == '2') {
                                $read  = '1';
                                $write = '1';
                            }

                            $roleId = substr($id, strlen('roleId_'));

                            $updated = $attributeDaoImpl->updateAttributeRole(
                                $attributeId, $roleId, $read, $write);
                            if (!$updated || $updated == 0 || $updated > 1) {
                                $attributeDaoImpl->deleteAttributeRole($roleId,
                                    $attributeId);
                                $attributeDaoImpl->insertRolesByAttributeId(
                                    $attributeId, $roleId, $read, $write);
                            }
                            $dbUpdate = true;
                        }
                    }
            } catch (Exception $e) {
                throw new Exception_Attribute_MappingFailed($e);
            }
        } catch (Exception $e) {
            throw new Exception_Attribute_UpdateFailed($e);
        }
        return $dbUpdate;
    }

    private function checkCiTypePersistentUpdate($attributeId)
    {
        $args                = array();
        $args['type']        = 'multi';
        $args['recursive']   = 'false';
        $args['attributeId'] = $attributeId;

        $message = new Service_Queue_Message();
        $message->setQueueId(5); // TODO: replace me!
        $message->setArgs($args);
        // Service_Queue_Handler::add($message);

        // TODO: reactivate
    }

    public function getUpdateAssignCiTypeForm()
    {
        return new Form_Attribute_AssignCiType($this->translator);
    }

    public function getUpdateAttributeForm($citypes, $roles)
    {
        $createAttributeConfig = new Zend_Config_Ini(
            APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);

        $attributeTypeDaoImpl = new Dao_AttributeType();
        $selectAT             = $attributeTypeDaoImpl->getAttributeTypeRowset();

        $attributeTypeList = array();
        foreach ($selectAT as $row) {
            $attributeTypeList[$row[Db_AttributeType::ID]] = $this->translator->translate(
                'name_' . $row[Db_AttributeType::NAME]);
        }

        $attributeGroupDaoImpl = new Dao_AttributeGroup();
        $selectVT              = $attributeGroupDaoImpl->getAttributeGroupRowset();

        // put the data in useable content
        $attributeGroupList = array();
        foreach ($selectVT as $row) {
            $attributeGroupList[$row[Db_AttributeGroup::ID]] = $row[Db_AttributeGroup::DESCRIPTION];
        }

        $form = new Form_Attribute_Update($this->translator, $attributeTypeList,
            null, $attributeGroupList, $createAttributeConfig);

        foreach ($citypes as $citype) {
            $form->addCiType($citype[Db_CiType::ID], $citype[Db_CiType::NAME], $citype[Db_CiType::DESCRIPTION], $citype[Db_CiType::IS_ACTIVE]);
        }

        foreach ($roles as $role) {
            $form->addRole($role[Db_Role::ID], $role[Db_Role::NAME], $role[Db_Role::DESCRIPTION], $role[Db_Role::IS_ACTIVE]);
        }

        return $form;
    }

    /**
     * activates an inactive attribute by the given attributeId
     *
     * @param $attributeId Attribute
     *                     to activate
     *
     * @throws Exception_Attribute_ActivationFailed if activation failes
     */
    public function activateAttribute($attributeId)
    {
        try {
            $attributeDaoImpl = new Dao_Attribute();
            $attributeDaoImpl->activateAttribute($attributeId);
        } catch (Exception $e) {
            throw new Exception_Attribute_ActivationFailed($e);
        }
    }
}
