<?php

/**
 *
 *
 *
 */
class Service_Ci_Create extends Service_Abstract
{

    private static $ciNamespace = 'CiController';

    const SESSION_ID         = 'sessionId';
    const ADDED_ATTRIBUTES   = 'addedAttributes';
    const REMOVED_ATTRIBUTES = 'removedAttributes';
    const ATTACHED_FILES     = 'attachedFiles';
    const SELECTED_OPTIONS   = 'selected_options';

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 302, $themeId);
    }


    /**
     * retreives the basic form of cci creation
     */
    public function getCreateBasicCiForm($sessionID, $values = null)
    {
        $createCiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/ci.ini', APPLICATION_ENV);

        $ciTypeDaoImpl = new Dao_CiType();
        $select        = $ciTypeDaoImpl->getRootCiTypeRowset();

        // put the root ci data in useable content
        $rootCiTypes       = array();
        $rootCiTypes[null] = ' ';
        foreach ($select as $row) {
            $rootCiTypes[$row[Db_CiType::ID]] = $row[Db_CiType::DESCRIPTION] . ' (' . $row[Db_CiType::NAME] . ')';
        }

        unset($select);
        $ciTypesToUse = array();

        $form = new Form_Ci_Create($this->translator, $sessionID, $rootCiTypes, $createCiConfig);
        $form->addHiddenCIID();

        $isCiAttachAllowed     = null;
        $ciTypeAttributeAttach = null;

        if (!is_null($values)) {
            // check if something is selected
            if (!is_null($values['parentCiType']) && $values['parentCiType'] > 0) {
                $isCiAttachAllowed = $values['parentCiType'];
                // handle the first child element
                $this->handleChildElement($values['parentCiType'], $ciTypeDaoImpl, $form, 1);
                array_push($ciTypesToUse, $values['parentCiType']);

                // handle all other child elements
                for ($i = 1; $i < count($values); $i++) {
                    // every step represents another depth
                    $varToCheck = 'child_' . $i;
                    // so, first check if something is selected and if it matches with the previous selected value;
                    if (!is_null($values[$varToCheck]) && $values[$varToCheck] > 0) {
                        // we found a child that is already selected
                        // add child elements!
                        $isCiAttachAllowed = $values[$varToCheck];
                        array_push($ciTypesToUse, $values[$varToCheck]);
                        $this->handleChildElement($values[$varToCheck], $ciTypeDaoImpl, $form, $i + 1);
                    } else {
                        break;
                    }
                }
            } else {
                // check parent ci attach
                $isCiAttachAllowed = $values['parentCiType'];
            }
        }

        $ciTypeId = end($ciTypesToUse);//last element of array is selected ci-type

        $ciTypeDescription = "new";
        try {
            $ciTypeDes               = $ciTypeDaoImpl->getRawCiType($ciTypeId);
            $ciTypeDescription       = $ciTypeDes[Db_CiType::DESCRIPTION];
            $ciTypeButtonDescription = $ciTypeDes[Db_CiType::CREATE_BUTTON_DESCRIPTION];
            $ciTypeAttributeAttach   = $ciTypeDes[Db_CiType::IS_ATTRIBUTE_ATTACH];
            $tabs                    = $ciTypeDes[Db_CiType::IS_TAB_ENABLED];

            if (!$ciTypeButtonDescription)
                $ciTypeButtonDescription = $ciTypeDescription . ' ' . $this->translator->translate('createCiButton');

        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::WARN);
            // useless
        }

        return array(
            'form'                    => $form,
            'ciTypeId'                => $isCiAttachAllowed,
            'ciTypeDescription'       => $ciTypeDescription,
            'ciTypeButtonDescription' => $ciTypeButtonDescription,
            'ciTypesToUse'            => $ciTypesToUse,
            'ciTypeAttributeAttach'   => $ciTypeAttributeAttach,
            'tabs'                    => $tabs,
        );
    }


    /**
     * adds ci-type childs to the given form.
     *
     * @param int          $ciTypeId
     * @param unknown_type $ciTypeDaoImpl
     * @param unknown_type $form
     * @param unknown_type $childCount
     */
    private function handleChildElement($ciTypeId, &$ciTypeDaoImpl, &$form, $childCount, $final = false)
    {
        $children = $ciTypeDaoImpl->retrieveCiTypeChildElements($ciTypeId);
        if (count($children) > 0) {
            $child       = array();
            $child[null] = ' ';

            foreach ($children as $row) {
                $child[$row[Db_CiType::ID]] = $row[Db_CiType::DESCRIPTION] . ' (' . $row[Db_CiType::NAME] . ')';
            }

            $form->addChild($child, $childCount, $final);
        }
    }


    /**
     * retrieves the ci create form
     *
     * @param Form_Ci_Create $form
     * @param array          $formData
     * @param array          $selfFormData
     *
     * @return Form_Ci_Create
     */
    public function getCreateCiForm($userId, $sessionID, $form = null, $formData = null, $selfFormData = null, $isValidate = false, $attributeAttach = null)
    {
        if (!$form) {
            $ret  = $this->getCreateBasicCiForm($sessionID);
            $form = $ret['form'];

        }


        // retrieve all available projects
        $projectDaoImpl = new Dao_Project();
        $projects       = $projectDaoImpl->getProjectRowsetVirtualized($userId, true);


        // put the project data in useable content
        $projectList       = array();
        $projectList[null] = ' ';
        foreach ($projects as $row) {
            $projectList[$row[Db_Project::ID]] = $row[Db_Project::DESCRIPTION];
        }

        // memory friendly
        unset($projects);

        // this function is called on auto submit. (ci type change)
        if ($formData || $selfFormData) {
            if ($selfFormData) {
                $formData = $selfFormData;
            }

            $form->populate($formData);

            if ($attributeAttach) {
                $form->addAttributeImgLink('general', 0, $sessionID);
            }

            // add attribute without attributeGroup limitation
            $attributes = $this->getCreateCiAttributes($sessionID);


            $currentGroupName       = null;
            $curentAttributeGroupId = null;
            // add all attributes to form

            foreach ($attributes as $attribute) {
                // do not add attribute if the user doesn't have the permission to read or write
                if (!$attribute[Db_AttributeRole::PERMISSION_READ] && !$attribute[Db_AttributeRole::PERMISSION_WRITE]) {
                    continue;
                }

                $key     = $attribute['genId'];
                $notNull = false;

                if ($attribute[Db_CiTypeAttribute::IS_MANDATORY] == 1) {
                    $notNull = true;
                }

                if ($attributeAttach) {
                    if (!$currentGroupName) {
                        $currentGroupName       = $attribute['attributeGroupName'];
                        $curentAttributeGroupId = $attribute['attribute_group_id'];

                        $form->addAttributeImgLink($currentGroupName, $curentAttributeGroupId, $sessionID);
                    } else if ($curentAttributeGroupId != $attribute['attribute_group_id']) {
                        // create new group
                        $currentGroupName       = $attribute['attributeGroupName'];
                        $curentAttributeGroupId = $attribute['attribute_group_id'];

                        $form->addAttributeImgLink($currentGroupName, $curentAttributeGroupId, $sessionID);
                        //Zend_Form_Element_Image
                    }

                    if (!$notNull) {
                        $form->addAttributeImgRemoveLink($attribute['genId'], $sessionID);
                    }
                }
                $form->addAttribute($attribute, $key, $isValidate, $userId);
            }
        }
        $form->addHiddenCIID();
        $form->addProjectSelection($projectList);
        $form->addSubmitButton();

        return $form;
    }


    /**
     * retrieve all attributes mapped to the current ci create logic
     */
    public function getCreateCiAttributes($sessionId)
    {
        // now gather all the ci attributes by $ciTypesToUse
        $attributes       = array();
        $attributeDaoImpl = new Dao_Attribute();


        $attributes = $attributeDaoImpl->getAttributesFromTempTable($sessionId);


        return $attributes;
    }


    /**
     *
     * @param unknown_type $ciTypeId
     */
    public function isCiTypeAttachAllowed($ciTypeId)
    {
        $permission = false;
        if ($ciTypeId) {
            $ciTypeDaoImpl = new Dao_CiType();
            $ciType        = $ciTypeDaoImpl->getRawCiType($ciTypeId);
            if ($ciType)
                return $ciType[Db_CiType::IS_CI_ATTACH];
        }

        return $permission;
    }

    public function getFirstHandParentCiType($values)
    {
        return $this->getCiType($values);
    }

    private function getCiType($values)
    {
        $childCounter = 1;
        $ciType       = null;

        while (true) {
            $currentChild = 'child_' . $childCounter;
            if (is_null($values[$currentChild]) || $values[$currentChild] == 0 || $values[$currentChild] == "") {
                // value not found, so previous value was the last selected.
                if ($childCounter == 1) {
                    // it's the parent
                    $ciType = $values['parentCiType'];
                } else {
                    $childCounter--;
                    $currentChild = 'child_' . $childCounter;
                    $ciType       = $values[$currentChild];
                }
                break;
            }
            $childCounter++;
        }
        return $ciType;
    }

    /**
     *
     * @param unknown_type $values
     */
    public function createCi($values, $userId, $sessionID)
    {
        try {
            $this->logger->log('insertNewCiType', Zend_Log::DEBUG);
            // retrieve current ciType

            $ciType = $this->getCiType($values);

            if (is_null($ciType)) {
                throw new Exception_InvalidParameter();
            }

            $ciDaoImpl         = new Dao_Ci();
            $triggerUtil       = new Util_Trigger($this->logger);
            $historizationUtil = new Util_Historization();
            $attributeDaoImpl  = new Dao_Attribute();

            // check if ci attach is allowed
            $ciTypeAttach = $ciDaoImpl->checkCiAttachAllowed($ciType);

            if (!$ciTypeAttach[Db_CiType::IS_CI_ATTACH]) {
                throw new Exception();
            }

            // create history
            $daoHistory = new Dao_History();
            $historyId  = $daoHistory->createHistory($userId, Enum_History::CI_CREATE);

            // check ci icon
            $icon        = $values['ciicon'];
            $newFilename = null;

            // first create new ci and select the generated id
            $ciId = $ciDaoImpl->createCi($ciType, $newFilename, $historyId);

            $this->logger->log('created ci with id ' . $ciId, Zend_Log::DEBUG);

            if ($icon && $icon != '' && $icon != ' ') {

                $date        = date("YmdHms");

                $tmpUploadPath   = Util_FileUpload::getUploadPath('tmp');
                $destinationPath = Util_FileUpload::getUploadPath('icon');

                $fileinfo = finfo_open(FILEINFO_MIME_TYPE);
                $filetype = finfo_file($fileinfo, $tmpUploadPath .'/'. $icon);

                //check icon mime type
                $allowedTypes = [
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg'
                ];

                $extension = $allowedTypes[$filetype];
                $newFilename = $date .'-'. $ciId . '-icon.' . $extension;

                if (!in_array($filetype, array_keys($allowedTypes))) {
                    throw new Exception_Ci_WrongIconType();
                    //throw new Exception_Ci_InsertFailed();
                }
                // rename icon
                if (!rename($tmpUploadPath .'/'. $icon, $destinationPath .'/'. $newFilename)) {
                      throw new Exception_File_RenamingFailed();
                }
                $ciDaoImpl->updateCiIcon($ciId, $newFilename);

            }


            $attributeList = $this->getCreateCiAttributes($sessionID);


            $this->logger->log('session saved attribute data: ', Zend_Log::DEBUG);

            $ciattributeIDs = array();

            $hasAttributes = false;
            // then insert ci_attributes foreach attribute
            foreach ($attributeList as $attribute) {
                $this->logger->log($attribute[Db_Attribute::ID], Zend_Log::DEBUG);

                $attributeType = Util_AttributeType_Factory::get($attribute['type']);
                $ret           = $attributeType->addCi($values, $attribute, $ciId);

                if ($ret || is_array($ret)) {
                    // insert in ci_attributes
                    $ret[Db_CiAttribute::HISTORY_ID] = $historyId;
                    $ciAttributeId                   = $ciDaoImpl->addCiAttributeArray($ciId, $attribute[Db_Attribute::ID], $ret, $attribute['initial']);

                    array_push($ciattributeIDs, $ciAttributeId);


                    $hasAttributes = true;
                }
            }

            //add missing executable attributes
            Util_AttributeType_Type_Executeable::insertMissingAttributes($ciId, $historyId);

            //execute workflows for all attributes

            foreach ($ciattributeIDs as $ciattributeID) {

                $triggerUtil->createAttribute($ciattributeID, $userId);

            }


            if (!$hasAttributes) {
                // delete ci
                $historizationUtil->handleCiDelete($ciId, $userId);
                $this->logger->log('ci create - no Attributes', Zend_Log::ERR);
                // throw exception
                throw new Exception_Ci_InsertFailed();
            }

            $projectId = $values['project'];

            if (!$projectId) {
                $projectId = 0;
            }
            // add project entries
            $ciProjectDaoImpl = new Dao_CiProject();
            $ciProjectDaoImpl->insertCiProject($ciId, $projectId);

            $ciServiceGet = new Service_Ci_Get($this->translator, $this->logger, parent::getThemeId());

            $ci_info        = [];
            $ci_info['new'] = $ciServiceGet->getContextInfoForCi($ciId);

            // handle customization
            $triggerUtil->createCi($ciId, $userId, $ci_info);


            //update query persistent
            $queryp_attribute = $attributeDaoImpl->getAttributesByAttributeTypeCiID($ciId, Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID);
            Util_AttributeType_Type_QueryPersist::execute_query($ciId, $queryp_attribute, $historyId);


            return $ciId;
        } catch (Exception $e) {
            if ($e instanceof Exception_Ci_InsertFailed)
                throw $e;

            if ($e instanceof Exception_File_RenamingFailed)
                throw $e;

            if ($e instanceof Exception_Ci_WrongIconType)
                throw $e;

            if ($e instanceof Exception_InvalidParameter)
                throw $e;

            $this->logger->log($e, Zend_Log::ERR);
            throw new Exception_Ci($e);
        }
    }


    public function createCiFromValidation($validationId, $userId, $ciNr = null)
    {
        try {
            $this->logger->log('insertNewCi', Zend_Log::DEBUG);
            // retrieve current ciType

            $attributeTriggers = array();

            $validationService = new Service_Validation_Get($this->translator, $this->logger, $this->getThemeId());
            $importFile        = $validationService->getImportFile($validationId);
            $validationDaoImpl = new Dao_Validation();

            $newCiData = $validationDaoImpl->getValidationAttributeGroupByCi($ciNr, $validationId);

            $ciType = $newCiData[Db_ImportFileValidationAttributes::CI_TYPE_ID];


            if (is_null($ciType)) {
                throw new Exception_InvalidParameter();
            }

            $ciDaoImpl        = new Dao_Ci();
            $triggerUtil      = new Util_Trigger($this->logger);
            $attributeDaoImpl = new Dao_Attribute();

            // check if ci attach is allowed
            $ciTypeAttach = $ciDaoImpl->checkCiAttachAllowed($ciType);

            if (!$ciTypeAttach[Db_CiType::IS_CI_ATTACH]) {
                throw new Exception();
            }

            //historize
            $historizationUtil = new Util_Historization();
            $historyId         = $historizationUtil->createHistory($userId, Util_Historization::MESSAGE_IMPORT_VALIDATION_INSERT);


            // first create new ci and select the generated id
            $ciId          = $ciDaoImpl->createCi($ciType, null, $historyId);
            $attributeList = $validationService->getImportFileAttributesList($validationId, $ciNr);

            // then insert ci_attributes foreach attribute
            foreach ($attributeList as $validationAttribute) {
                if ($validationAttribute[Db_ImportFileValidationAttributes::STATUS] != 'deleted') {
                    $attributeId   = $validationAttribute[Db_ImportFileValidationAttributes::ATTRIBUTE_ID];
                    $attributeType = Util_AttributeType_Factory::get($validationAttribute[Db_Attribute::ATTRIBUTE_TYPE_ID]);
                    $data          = $attributeType->returnFormData(array('value' => $validationAttribute[Db_ImportFileValidationAttributes::VALUE]));

                    if (!is_null($data[Db_CiAttribute::VALUE_DEFAULT])) {
                        $importDaoImpl                       = new Dao_Import();
                        $ret                                 = $importDaoImpl->getDefaultValueIdByName($attributeId, $data[Db_CiAttribute::VALUE_DEFAULT]);
                        $data[Db_CiAttribute::VALUE_DEFAULT] = $ret[Db_AttributeDefaultValues::ID];
                    }

                    if (is_null($data[Db_CiAttribute::NOTE])) {
                        $data[Db_CiAttribute::NOTE] = $validationAttribute[Db_ImportFileValidationAttributes::NOTE];
                    }

                    $data[Db_CiAttribute::HISTORY_ID] = $historyId;

                    $ciAttributeId = $ciDaoImpl->addCiAttributeArray($ciId, $attributeId, $data, '0');

                    //update query persistent
                    $queryp_attribute = $attributeDaoImpl->getAttributesByAttributeTypeCiID($ciId, Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID);
                    Util_AttributeType_Type_QueryPersist::execute_query($ciId, $queryp_attribute, $historyId);

                    array_push($attributeTriggers, array('attributeId' => $ciAttributeId, 'userId' => $userId));
                }
            }


            //add missing executable attributes
            Util_AttributeType_Type_Executeable::insertMissingAttributes($ciId, $historyId);


            foreach ($attributeTriggers as $value) {

                $triggerUtil->createAttribute($value['attributeId'], $value['userId']);

            }


            $projectId = $newCiData[Db_ImportFileValidationAttributes::PROJECT_ID];

            // add project entries
            $ciProjectDaoImpl = new Dao_CiProject();
            $ciProjectDaoImpl->insertCiProject($ciId, $projectId, $historyId);


            // handle customization
            $triggerUtil->createCi($ciId, $userId);

            return $ciId;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            throw new Exception_Ci($e);
        }
    }


    public function initiaizecreateCISession()
    {
        $crypt = new Util_Crypt();
        return self::$ciNamespace . $crypt->create_uniqid();
    }


    public function createFormAttributeSession($userId, $sessionId, array $typeList)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $toBeDeleted      = array();

        // get current form data
        $attributes = $attributeDaoImpl->getAttributesFromTempTable($sessionId);

        $newAttributes = array();
        if (!empty($typeList) > 0) {
            $newAttributes = $attributeDaoImpl->getInsertElements($typeList, $sessionId, $userId);
        }

        foreach ($attributes as $attribute) {
            $toBeDeleted[$attribute['id']] = $attribute['genId'];
        }
        unset($attributes);

        foreach ($newAttributes as $key => $new) {
            if ($toBeDeleted[$new['id']]) {
                unset($toBeDeleted[$new['id']]);
                unset($newAttributes[$key]);
            }
        }

        // apply to form
        foreach ($toBeDeleted as $delete) {
            $attributeDaoImpl->removeAttributesFromTempTable($sessionId, $delete);
        }

        foreach ($newAttributes as $add) {
            $attributeDaoImpl->addAttributesToTempTable($add['id'], $sessionId, $typeList, $userId);
        }
    }


    /**
     * removes a single Attribute from the form
     *
     * @param unknown_type $attributeId
     */
    public function removeAttributeSession($attributeId, $mark = false, $sessionId)
    {

        $attributeDaoImpl = new Dao_Attribute();
        $attributeDaoImpl->removeAttributesFromTempTable($sessionId, $attributeId, $mark);
    }


    /**
     * removes the stored add/removed attributes for creating a ci
     */
//	public function destroyFormAttributeSession($sessionkey) {		
//		$sess = new Zend_Session_Namespace(self::$ciNamespace.$sessionkey);
//		unset($sess->{self::ADDED_ATTRIBUTES});
//		unset($sess->{self::REMOVED_ATTRIBUTES});
//		unset($sess->{self::ATTACHED_FILES});
//	}


    public function getUserMappingForm($ciId)
    {
        $form = new Form_Ci_UserMapping($this->translator, $ciId);

        // add form elements (select all users)
        $userDaoImpl = new Dao_User();
        $users       = $userDaoImpl->getUsers();

        // add current data (select ci_permission)
        $dbFormData = array();

        $row1 = array();
        $row2 = array();
        $row3 = array();

        $count       = count($users);
        $itemsPerRow = $count / 3;

        $cnt = 0;
        foreach ($users as $user) {
            $form->addUser($user[Db_User::ID], $user[Db_User::USERNAME], $user[Db_User::DESCRIPTION]);

            $userMapping = $userDaoImpl->getUserCiMapping($user[Db_User::ID], $ciId);

            $checked = false;
            if (isset($userMapping[Db_CiPermission::ID])) {
                $checked = true;
            }

            $dbFormData[$user[Db_User::ID]] = $checked;

            if ($cnt < $itemsPerRow) {
                array_push($row1, $user);
            } else if ($cnt < $itemsPerRow * 2) {
                array_push($row2, $user);
            } else {
                array_push($row3, $user);
            }
            $cnt++;
        }

        $form->addSubmitButton();

        return array(
            'form'     => $form,
            'userList' => $users,
            'row1'     => $row1,
            'row2'     => $row2,
            'row3'     => $row3,
            'count'    => $count,
            'formdata' => $dbFormData);
    }


    public function updateCiPermission($ciId, $values, $users)
    {
        try {
            $userDaoImpl = new Dao_User();
            foreach ($users as $user) {
                $userDaoImpl->deleteUserCiMapping($user[Db_User::ID], $ciId);

                if ($values[$user[Db_User::ID]])
                    $userDaoImpl->updateUserCiMapping($user[Db_User::ID], $ciId);
            }
        } catch (Exception $e) {
            throw new Exception_Ci_UpdateUserMappingFailed($e);
        }
    }

    /*
    public function updateSelectionSession($data) {
        $sess = new Zend_Session_Namespace(self::$ciNamespace);
        $sess->{self::SELECTED_OPTIONS} = $data;
        $sessionData = $sess->{self::SELECTED_OPTIONS};
        return $sessionData;
    }

    public function checkSelectionSession() {
        $sess = new Zend_Session_Namespace(self::$ciNamespace);
        if ($sess->{self::SELECTED_OPTIONS} != null){
            $sessionData = $sess->{self::SELECTED_OPTIONS};
        }else{
            $sessionData == null;
        }
        return $sessionData;
    }

    */

}
