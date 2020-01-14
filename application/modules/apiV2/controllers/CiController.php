<?php

require_once 'V2BaseController.php';

class ApiV2_CiController extends V2BaseController
{
    /**
     * @OA\Put(
     *     path="/ci",
     *     tags={"ci"},
     *     summary="Update ci",
     *     description="Update ci attributes",
     *     operationId="update",
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="ci id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *         )
     *     ),
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  required={},
     *                  @OA\Property(
     *                      type="object",
     *                      property="ci",
     *                      description="ci infos",
     *                      @OA\Property(
     *                          type="array",
     *                          property="attributes",
     *                          description="ci attributes",
     *                          @OA\Items(
     *                              @OA\Property(
     *                                  type="string",
     *                                  property="mode",
     *                                  description="ci attribute mode",
     *                                  enum={"set", "insert", "update", "delete"},
     *                                  example="set",
     *                              ),
     *                              @OA\Property(
     *                                  type="string",
     *                                  property="name",
     *                                  description="name of the attribute",
     *                                  example="emp_firstname",
     *                              ),
     *                              @OA\Property(
     *                                  type="string",
     *                                  property="value",
     *                                  description="new attribute value",
     *                                  example="Max",
     *                              ),
     *                              @OA\Property(
     *                                  type="integer",
     *                                  property="ciAttributeId",
     *                                  description="optionaly address ci attribute by id (only possible if mode supports it)",
     *                                  example=12345,
     *                              ),
     *                              @OA\Property(
     *                                  type="string",
     *                                  property="uploadId",
     *                                  description="hash responded by fileupload api - if provided and attribute type is attachment, file will be attached",
     *                                  example="apiV2_fileupload_0e1f80ad4a1f23db5fd102bb220156edafce4464cf1e6b27d5c0b1fa3e8dc1d5",
     *                              ),
     *                          ),
     *                      ),
     *                  ),
     *              )
     *          ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *          description="CI saved successfully",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="CI saved successfully",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Provided data is in some way invalid - check message for details",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="success",
     *                      type="boolean",
     *                      example=false,
     *                  ),
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="attributes: Mode insert with ciAttributeId not allowed",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=403,
     *          description="Not allowed to edit ci or attribute",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="success",
     *                      type="boolean",
     *                      example=false,
     *                  ),
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Forbidden",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Provided ciAttributeId does not exist",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="success",
     *                      type="boolean",
     *                      example=false,
     *                  ),
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Not Found",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     security={
     *         {"apiV2_auth": {}}
     *     }
     * )
     * @param int $id
     *
     * @throws Exception_AttributeType_InvalidClassName
     * @throws Exception_Ci
     * @throws Zend_Config_Exception
     * @throws Zend_Controller_Response_Exception
     */
    public function update(int $id)
    {
        // fetch request parameter
        $updateData      = $this->getJsonParam('ci', array());
        $updateDataArray = (array)$updateData;

        // helpers
        $user            = parent::getUserInformation();
        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, $user->getThemeId());
        $ciServiceUpdate = new Service_Ci_Update($this->translator, $this->logger, $user->getThemeId());
        $ciServiceDelete = new Service_Ci_Delete($this->translator, $this->logger, $user->getThemeId());
        $attributeDao    = new Dao_Attribute();
        $historyDao      = new Dao_History();
        $triggerUtil     = new Util_Trigger($this->logger);
        $cryptUtil       = new Util_Crypt();


        $isAllowed = $ciServiceGet->checkPermission($id, $user->getId());
        if ($isAllowed === false) {
            $this->outputHttpStatusForbidden("Not allowed to edit CI: " . $id);
            return;
        }

        $historyId = $historyDao->createHistory($user->getId(), Enum_History::CI_UPDATE);

        if (isset($updateDataArray['attributes'])) {

            // validate
            $isValid              = true;
            $validationMessages   = array();
            $attributeRows        = array();
            $sessionID            = Service_Ci_Update::$ciNamespace . $id . $cryptUtil->create_uniqid();
            $mixedCiAttributeRows = $attributeDao->addCiAttributesToTempTable($id, $sessionID, 0);

            foreach ($updateDataArray['attributes'] as $rowNr => $updateRowData) {
                if (!isset($updateRowData) || !isset($updateRowData->name) || empty($updateRowData->name)) {
                    $this->outputError("attributes: No name specified", $updateRowData, 400);
                    return;
                }
                $attributeRows[$updateRowData->name] = $attribute = $attributeDao->getAttributeByNameAll($updateRowData->name);
                if($attribute === false) {
                    $this->outputHttpStatusForbidden("attributes: No attribute with the following name found: " . $updateRowData->name);
                    return;
                }
                $attributeId                         = $attribute[Db_Attribute::ID];
                $utilAttribute                       = Util_AttributeType_Factory::get($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID]);

                $isAttributeAllowed = $attributeDao->checkUserAttributePermission($user->getId(), $attribute[Db_Attribute::ID], 'rw');
                if ($isAttributeAllowed === false) {
                    $this->outputHttpStatusForbidden("attributes: Not allowed to edit attribute: " . $attribute[Db_Attribute::NAME]);
                    return;
                }

                // check mode
                if (!in_array($updateRowData->mode, array('insert', 'update', 'delete', 'set'))) {
                    $this->outputError('attributes: Invalid mode "' . $updateRowData->mode . '"" for attribute: ' . $attribute[Db_Attribute::NAME]);
                    return;
                }

                // fetch and validate ci attribute
                $ciAttributeId = null;
                $ciAttributeIdSetViaParams = false;
                if (isset($updateRowData->ciAttributeId) && !empty($updateRowData->ciAttributeId)) {
                    $ciAttributeId = $updateRowData->ciAttributeId;
                    $ciAttribute   = $attributeDao->getCiAttributeById($ciAttributeId);
                    if ($ciAttribute === false) {
                        $this->outputError(
                            sprintf(
                                'attributes: Could not find row with ci_attribute-id: %s',
                                $ciAttributeId
                            ),
                            $updateRowData,
                            404
                        );
                        return;
                    }
                    $ciAttributeIdSetViaParams = true;
                } else {
                    $ciAttribute = $attributeDao->getCiAttributesByCiIdAttributeID($id, $attributeId, $ciAttributeCounter);

                    if ($ciAttributeCounter > 1 && $updateRowData->mode !== "insert") {
                        $this->outputError('attributes: Multiple ci_attribute rows for attribute: ' . $updateRowData->name, $updateRowData, 400);
                        return;
                    }

                    if ($ciAttribute !== false) {
                        $ciAttributeId                                        = $ciAttribute[Db_CiAttribute::ID];
                        $updateDataArray['attributes'][$rowNr]->ciAttributeId = $ciAttributeId;
                    }
                }

                $mode = $updateRowData->mode;

                // translate set to valid mode
                if ($mode === 'set') {
                    $mode = 'update';
                    if ($ciAttribute === false) {
                        $mode = 'insert';
                    }
                }
                // flag attributes with empty values as deleted
                if (
                    (is_null($updateRowData->value) || $updateRowData->value == "") &&
                    $utilAttribute::ALLOW_EMPTY === false
                ) {
                    $mode                                         = 'delete';
                    $updateDataArray['attributes'][$rowNr]->value = '';
                }

                $updateDataArray['attributes'][$rowNr]->mode = $mode;

                // validate request
                switch ($mode) {
                    case 'insert':
                        if ($ciAttributeIdSetViaParams === true) {
                            $this->outputError("attributes: Mode insert with ciAttributeId not allowed", $updateRowData, 400);
                        }
                        break;
                    case 'update':
                        break;
                    case 'delete':
                        if (!isset($updateRowData->ciAttributeId)) {
                            $this->outputError("attributes: Mode delete without ciAttributeId", $updateRowData, 400);
                        }
                        break;
                    default:
                        // should never happen, but just to be sure
                        $this->outputError('attributes: No mode specified for attribute', $updateRowData, 400);
                        return;

                }

                // get ci attribute data
                $utilAttribute       = Util_AttributeType_Factory::get($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID]);
                $mixedCiAttributeRow = null;

                if (isset($mixedCiAttributeRows[$ciAttributeId])) {
                    $mixedCiAttributeRow = $mixedCiAttributeRows[$ciAttributeId];
                }

                if (empty($mixedCiAttributeRow)) {
                    $mixedCiAttributeRow = $this->findAttributeInTempTable($mixedCiAttributeRows, $attributeId);
                }

                if (empty($mixedCiAttributeRow)) {
                    $mixedCiAttributeRows = $attributeDao->addAttributesToTempTable($attributeId, $sessionID, array(), 0);
                    $mixedCiAttributeRow  = $this->findAttributeInTempTable($mixedCiAttributeRows, $attributeId);
                }

                $formElements = $utilAttribute->getFormElements($mixedCiAttributeRow, $mixedCiAttributeRow['genId'], $id, true, $user->getId());

                /** @var Zend_Form_Element $formElement */
                foreach ($formElements as $formElement) {
                    $formElementName = $formElement->getName();
                    if ($formElementName == $attribute[Db_Attribute::NAME]) {
                        if ($formElement->isValid($updateRowData->value) === false) {
                            $validationMessages[] = sprintf(
                                "%s (%d): %s",
                                $attribute[Db_Attribute::NAME],
                                $ciAttributeId,
                                implode(", ", $formElement->getMessages())
                            );

                            $isValid = false;
                        }
                    }
                }
            }

            $attributeDao->deleteTempTableForCiCreate($sessionID);

            if ($isValid === false) {
                $this->outputValidationError($validationMessages);
                return;
            }

            // update ci
            $ciInfo             = array(
                'before' => $ciServiceGet->getContextInfoForCi($id),
            );
            $fireCiUpdate       = false;
            $fireAttributes     = array(
                'insert' => array(),
                'update' => array(),
            );
            $attributesToDelete = array();

            foreach ($updateDataArray['attributes'] as $updateRowData) {
                $mode          = $updateRowData->mode;
                $ciAttributeId = null;
                $attribute     = $attributeRows[$updateRowData->name];
                $attributeId   = $attribute[Db_Attribute::ID];
                if (isset($updateRowData->ciAttributeId) && !empty($updateRowData->ciAttributeId)) {
                    $ciAttributeId = $updateRowData->ciAttributeId;
                }

                if ($mode === 'insert') {
                    $ciAttribute = $this->createCiAttribute($id, $attributeId, $historyId);
                } else {
                    $ciAttribute = $this->fetchCiAttribute($id, $attributeId, $ciAttributeId);
                }

                $ciAttributeId = $ciAttribute[Db_CiAttribute::ID];

                if ($mode === 'insert' || $mode === 'update') {
                    $formData            = array('value' => $updateRowData->value);
                    $ciAttribute['type'] = $attribute['attribute_type_id'];

                    if ((int)$attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] === Util_AttributeType_Type_Attachment::ATTRIBUTE_TYPE_ID) {
                        $rawFilename = $updateRowData->value;
                        $filename    = Util_FileUpload::processTmpFile($updateRowData->uploadId, $rawFilename, 'attachment', array('ci_id' => $id, 'prefix_date' => true));
                        $formData    = array(
                            $attributeId . 'filename'    => $filename,
                            $attributeId . 'description' => $rawFilename,
                        );
                    }

                    $result = $ciServiceUpdate->updateSingleAttribute($user->getId(), $id, $ciAttribute, $formData, $historyId, false);
                    if ($result === true) {
                        $fireAttributes[$mode][$ciAttributeId] = $ciAttribute;
                        $fireCiUpdate                          = true;
                    } else {
                        $this->outputError('attributes: failed to save attribute', $updateRowData);
                    }
                } elseif ($mode == 'delete') {
                    if (isset($ciAttributeId) && !empty($ciAttributeId)) {
                        // delete later: we need to fire delete triggers before deletion
                        $attributesToDelete[$ciAttributeId] = $ciAttribute;
                    }
                }
            }

            // delete attributes and fire delete trigger
            foreach ($attributesToDelete as $ciAttributeId => $ciAttribute) {
                $triggerUtil->deleteAttribute($ciAttributeId, $user->getId());
            }
            foreach ($attributesToDelete as $ciAttributeId => $ciAttribute) {
                $ciServiceDelete->deleteSingleCiAttribute($ciAttributeId, $historyId);
            }


            // fire insert/update triggers
            foreach ($fireAttributes['insert'] as $ciAttributeId => $ciAttribute) {
                $triggerUtil->createAttribute($ciAttributeId, $user->getId());
            }
            foreach ($fireAttributes['update'] as $ciAttributeId => $ciAttribute) {
                $triggerUtil->updateAttribute($ciAttributeId, $user->getId());
            }

            if ($fireCiUpdate === true) {
                $ciInfo['new'] = $ciServiceGet->getContextInfoForCi($id);
                $triggerUtil->updateCi($id, $user->getId(), $ciInfo);

                //update query persistent
                $queryPersistentAttributes = $attributeDao->getAttributesByAttributeTypeCiID($id, Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID);
                Util_AttributeType_Type_QueryPersist::execute_query($id, $queryPersistentAttributes, $historyId);
            }

            $this->outputContent("CI saved successfully");

        }

    }

    /**
     * @OA\Get(
     *     path="/ci",
     *     tags={"ci"},
     *     summary="get Ci Details",
     *     description="get ci details",
     *     operationId="get",
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="ci id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *         )
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Provided ciAttributeId does not exist",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="success",
     *                      type="boolean",
     *                      example=false,
     *                  ),
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Not Found",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     security={
     *         {"apiV2_auth": {}}
     *     }
     * )
     * @param int $id
     *
     * @throws Zend_Controller_Response_Exception
     */
    public function read(int $id)
    {
        // helpers
        $user            = parent::getUserInformation();
        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, $user->getThemeId());

        $isAllowed = $ciServiceGet->checkPermission($id, $user->getId());
        if ($isAllowed === false) {
            $this->outputHttpStatusForbidden("Not allowed to edit CI: " . $id);
            return;
        }

        try {
            $ciData = $ciServiceGet->getCiDetail($id, $user->getId());
            $this->outputContent("success", array("data" => $ciData), 200);
        } catch (Exception_AccessDenied $e) {
            $this->outputError(sprintf("permission denied for ciid[%d]", $id));
        } catch (Exception_Ci_CiIdInvalid $e) {
            $this->outputError(sprintf("invalid ciid[%d]", $id));
        } catch (Exception_Ci_RetrieveNotFound $e) {
            $this->outputError(sprintf("ciid[%d] not found", $id));
        }
    }


    /**
     * @OA\Get(
     *     path="/ci/index",
     *     tags={"ci"},
     *     summary="get Ci List",
     *     description="get ci list",
     *     operationId="index",
     *     @OA\Parameter(
     *         name="ciTypeId",
     *         in="query",
     *         description="ci Type Id(s)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="ciTypeName",
     *         in="query",
     *         description="ci Type Name(s)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="ProjectId",
     *         in="query",
     *         description="Project Id(s)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="ProjectName",
     *         in="query",
     *         description="Project Name(s)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="AttributeName",
     *         in="query",
     *         description="Attribute Name(s)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Provided ciAttributeId does not exist",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="success",
     *                      type="boolean",
     *                      example=false,
     *                  ),
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Not Found",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     security={
     *         {"apiV2_auth": {}}
     *     }
     * )
     * @throws Zend_Controller_Response_Exception
     */
    public function list()
    {
        $ciTypeIds = preg_split('/\s*,\s*/', $this->getParam('ciTypeId', ''));
        $ciTypeIds = array_filter($ciTypeIds, 'intval');
        $ciTypeIdsList = null;
        if (count($ciTypeIds) === 0) {
            $ciTypeNames = preg_split('/\s*,\s*/', $this->getParam('ciTypeName', ''));
            if (count($ciTypeNames) === 0) {
                $this->outputError('Missing required data: ciTypeId/CiTypeName are required!');
                return;
            }

            $ciTypeIds     = array();
            $ciTypeDaoImpl = new Dao_CiType();
            foreach ($ciTypeNames as $v) {
                if (!$type = $ciTypeDaoImpl->getCiTypeByName($v)) {
                    $this->outputError(sprintf('Could not resolve ciTypeName[%v]', $v));
                    return;
                }
                $ciTypeIds[] = $type[Db_CiType::ID];
            }

        }

        $user = parent::getUserInformation();

        $ciTypeProjectIdsList = null;
        if ($this->getParam('ProjectId', '') !== '' || $this->getParam('ProjectName', '') !== '') {
            $ciTypeProjectIds = preg_split('/\s*,\s*/', $this->getParam('ProjectId', ''));
            $ciTypeProjectIds = array_filter($ciTypeProjectIds, 'intval');

            if (count($ciTypeProjectIds) === 0) {
                $ciTypeProjectNames = $this->getParam('ProjectName', '');
                $ciTypeProjectNames = preg_split('/\s*,\s*/', $ciTypeProjectNames);
                $ciTypeProjectNames = array_filter($ciTypeProjectNames);

                // map the ProjectNames to IDs
                if (count($ciTypeProjectNames) > 0) {
                    $ciTypeProjectNamesCleaned = array_map('strtolower', array_map('trim', $ciTypeProjectNames));
                    $projectDaoImpl            = new Dao_Project();
                    $allProjects               = $projectDaoImpl->getProjects()->toArray();
                    $ciTypeProjectIds          = array();
                    foreach ($allProjects as $p => $project) {
                        $foundId = array_search(strtolower($project["name"]), $ciTypeProjectNamesCleaned);
                        if ($foundId !== false) {
                            array_push($ciTypeProjectIds, $project["id"]);
                            unset($ciTypeProjectNames[$foundId]);
                        }
                    }

                    if (count($ciTypeProjectIds) === 0 || count($ciTypeProjectNames) > 0) {
                        $this->outputError(sprintf('ProjectName not found! (%s)', join(',', $ciTypeProjectNames)));
                        return;
                    }
                }

            }
            $ciTypeProjectIdsList = join(',', $ciTypeProjectIds);
        }

        $attributeDao = new Dao_Attribute();

        $attributeList = array();
        if (trim($this->getParam('AttributeName', '')) !== '') {
            $attributeNames = preg_split('/\s*,\s*/', $this->getParam('AttributeName', ''));
            $attributeNames = array_map('trim', $attributeNames);

            foreach($attributeNames as $attributeName) {
                $att = $attributeDao->getAttributeByName($attributeName);
                if(!$att) {
                    $this->outputError(sprintf('attributes: could not resolve attribute[%s]', $attributeName));
                }

                array_push($attributeList, $att);
            }
        }

        $ciServiceGet = new Service_Ci_Get($this->translator, $this->logger, $user->getThemeId());
        $ciTypeDao    = new Dao_CiType();

        $ciList = array();
        foreach($ciTypeIds as $ciTypeId) {
            $ciType = $ciTypeDao->getRawCiType($ciTypeId);
            $ciIDs  = $ciServiceGet->getCiListIds($ciTypeId, $user->getId(), $user->getThemeId(), $ciTypeProjectIdsList);

            $ciTypeAttributeList = $attributeList;
            if(count($attributeList) === 0) {
                $ciTypeHierarchy     = join(',', $ciTypeDao->retrieveCiTypeHierarchy($ciTypeId));
                $ciTypeAttributeList = $ciTypeDao->getAttributesByCiTypeHierarchy($ciTypeHierarchy);
                $queryAttributes     = $attributeDao->getQueryAttributesByCITypes($ciTypeHierarchy, $user->getId());
                $ciTypeAttributeList = array_merge_recursive($ciTypeAttributeList, $queryAttributes);
            }

            $ciTypeCis = $ciServiceGet->getListResultForCiList($ciTypeAttributeList, $ciIDs, null, null, false, $ciTypeProjectIdsList, false);
            foreach ($ciTypeCis as $ci) {
                $ci["ci_type_id"]   = $ciType[Db_CiType::ID];
                $ci["ci_type_name"] = $ciType[Db_CiType::NAME];

                array_push($ciList, $ci);
            }
        }

        $this->outputContent("success", array("data" => array("ciList" => $ciList)), 200);
    }

    private function createCiAttribute($ciId, $attributeId, $historyId)
    {
        $attributeDao = new Dao_Attribute();

        $ciAttribute = array(
            Db_CiAttribute::ATTRIBUTE_ID => $attributeId,
            Db_CiAttribute::CI_ID        => $ciId,
            Db_CiAttribute::HISTORY_ID   => $historyId,
        );
        $ciaId       = $attributeDao->insertCiAttribute($ciAttribute);

        $ciAttribute = $attributeDao->getCiAttributeById($ciaId);

        return $ciAttribute;
    }

    private function fetchCiAttribute($ciId, $attributeId, $ciAttributeId = null)
    {
        $attributeDao = new Dao_Attribute();

        if (!empty($ciAttributeId)) {
            $ciAttribute = $attributeDao->getCiAttributeById($ciAttributeId);
        } else {
            $ciAttribute = $attributeDao->getCiAttributesByCiIdAttributeID($ciId, $attributeId);
        }

        return $ciAttribute;
    }

    private function findAttributeInTempTable($mixedRows, $attributeId)
    {
        foreach ($mixedRows as $mixedRow) {
            if ($mixedRow[Db_Attribute::ID] == $attributeId) {
                return $mixedRow;
            }
        }

        return false;
    }

}
