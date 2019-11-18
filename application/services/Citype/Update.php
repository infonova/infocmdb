<?php

/**
 *
 *
 *
 */
class Service_Citype_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 403, $themeId);
    }


    public function getAttributes()
    {
        $attributeDaoImpl = new Dao_Attribute();
        return $attributeDaoImpl->getAttributeRowsetOrderName();
    }


    public function getRelations()
    {
        $ciRelationDaoImpl = new Dao_CiRelation();
        return $ciRelationDaoImpl->getRelations();
    }


    /**
     *
     * @param unknown_type $ciTypeId
     * @param unknown_type $isPost
     * @param unknown_type $values
     */
    public function getUpdateCiTypeForm($ciTypeId, $attributes, $relations, $projectId, $maxElements = null)
    {
        $createCiTypeConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/citype.ini', APPLICATION_ENV);

        $ciTypeDaoImpl = new Dao_CiType();
        $select        = $ciTypeDaoImpl->getRootCiTypeRowset();

        // put the root ci data in useable content
        $rootCiTypes    = array();
        $rootCiTypes[0] = ' ';
        foreach ($select as $row) {
            if ($row[Db_CiType::ID] != $ciTypeId)
                $rootCiTypes[$row[Db_CiType::ID]] = $row[Db_CiType::DESCRIPTION];
        }

        // memory friendly
        unset($select);

        $projectDaoImpl = new Dao_Project();
        $projects       = $projectDaoImpl->getProjectRowset(true, $projectId);
        $projectList    = array();
        $projectList[0] = ' ';
        foreach ($projects as $project)
            $projectList[$project[Db_Project::ID]] = $project[Db_Project::DESCRIPTION];


        // now create the form
        $form = new Form_Citype_Update($this->translator, $rootCiTypes);

        // adds submit button and static fields
        $fileUploadConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $form->finalizeForm($createCiTypeConfig, $fileUploadConfig, $projectList, $maxElements);

        /*foreach($attributes as $attribute) {
            $form->addAttribute($attribute[Db_Attribute::ID], $attribute[Db_Attribute::NAME], $attribute[Db_Attribute::DESCRIPTION]);
        }*/

        foreach ($relations as $relation) {
            $form->addRelations($relation[Db_CiRelation::ID], $relation[Db_CiRelation::NAME], $relation[Db_CiRelation::NOTE]);
        }

        return $form;
    }


    /**
     * updates a Ci Type by the given Ci Type Id and values
     *
     * @param int   $typeId
     * @param array $values
     *
     * @throws Exception_Citype_UpdateItemNotFound if no items are updated
     * @throws Exception_Citype_MappingFailed if mapping items weren't updated
     * @throws Exception_Citype_UpdateFailed on all other errors
     */
    public function updateCiType($typeId, $formData, $dbData)
    {
        try {
            $dbUpdate = false;

            foreach ($formData as $key => $value) {
                if ($formData[$key] != $dbData[$key])
                    $updateData[$key] = $value;
            }


            $ciType = array();
            if ($updateData['defaultProject'] !== null)
                $ciType[Db_CiType::DEFAULT_PROJECT_ID] = $updateData['defaultProject'];
            if ($updateData['defaultAttribute'] !== null)
                $ciType[Db_CiType::DEFAULT_ATTRIBUTE_ID] = $updateData['defaultAttribute'];
            if ($updateData['defaultSortAttribute'] !== null)
                $ciType[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID] = $updateData['defaultSortAttribute'];
            if ($updateData['name'] !== null)
                $ciType[Db_CiType::NAME] = trim($updateData['name']);
            if ($updateData['description'] !== null)
                $ciType[Db_CiType::DESCRIPTION] = trim($updateData['description']);
            if ($updateData['note'] !== null)
                $ciType[Db_CiType::NOTE] = trim($updateData['note']);
            if ($updateData['allowCiAttach'] !== null)
                $ciType[Db_CiType::IS_CI_ATTACH] = $updateData['allowCiAttach'];
            if ($updateData['allowAttributeAttach'] !== null)
                $ciType[Db_CiType::IS_ATTRIBUTE_ATTACH] = $updateData['allowAttributeAttach'];
            if ($updateData['createButtonDescription'] !== null)
                $ciType[Db_CiType::CREATE_BUTTON_DESCRIPTION] = trim($updateData['createButtonDescription']);
            if ($updateData['xml'] !== null)
                $ciType[Db_CiType::TAG] = trim($updateData['xml']);
            if ($updateData['ticketEnabled'] !== null)
                $ciType[Db_CiType::IS_TAB_ENABLED] = $updateData['ticketEnabled'];
            if ($updateData['eventEnabled'] !== null)
                $ciType[Db_CiType::IS_EVENT_ENABLED] = $updateData['eventEnabled'];
            if ($updateData['orderNumber'] !== null)
                $ciType[Db_CiType::ORDER_NUMBER] = $updateData['orderNumber'];

            if ($updateData['isDefaultSortAsc'] !== null)
                $ciType[Db_CiType::IS_DEFAULT_SORT_ASC] = $updateData['isDefaultSortAsc'];

            if ($updateData['query'] !== null)
                $ciType[Db_CiType::QUERY] = $updateData['query'];

            if ($updateData['parentCiType'] !== null) {
                $parentCitypeId = null;

                // retrieve current ciType
                $childCounter = 1;
                while (true) {
                    $currentChild = 'child_' . $childCounter;

                    if (is_null($updateData[$currentChild]) || $updateData[$currentChild] == 0) {
                        // value not found, so previous value was the last selected.
                        if ($childCounter == 1) {
                            // it's the parent
                            $parentCitypeId = $updateData['parentCiType'];
                        } else {
                            $childCounter--;
                            $currentChild   = 'child_' . $childCounter;
                            $parentCitypeId = $updateData[$currentChild];
                        }
                        break;
                    }
                    $childCounter++;
                }
                $ciType[Db_CiType::PARENT_CI_TYPE_ID] = $parentCitypeId;
            }


            if ($updateData['icon'] !== null) {
                $ciType[Db_CiType::ICON] = $this->handleIconUpload($formData['icon']);
            } elseif ($updateData['storedIcon']) {
                $ciType[Db_CiType::ICON] = $updateData['storedIcon'];
            }

            $ciTypeDaoImpl = new Dao_CiType();
            if (!empty($ciType)) {
                $rows     = $ciTypeDaoImpl->updateCiType($ciType, $typeId);
                $dbUpdate = true;
            }

            try {
                $mapping = $formData;

                foreach ($mapping as $id => $value) {
                    if (strpos($id, 'relationId_') === 0 && !strpos($id, '_limit') && !strpos($id, '_order')) {
                        if ($value === '1' && ($mapping[$id . '_limit'] !== $dbData[$id][Db_CiTypeRelationType::MAX_AMOUNT] || $mapping[$id . '_order'] !== $dbData[$id][Db_CiTypeRelationType::ORDER_NUMBER])) {
                            $ciTypeDaoImpl->deleteCiTypeRelationType($typeId, substr($id, strlen('relationId_')));
                            $ciTypeDaoImpl->saveCiTypeRelation($typeId, substr($id, strlen('relationId_')), $mapping[$id . '_limit'], $mapping[$id . '_order']);
                            $dbUpdate = true;


                        } elseif (strpos($id, 'relationId_') === 0 && $dbData[$id]) {
                            $ciTypeDaoImpl->deleteCiTypeRelationType($typeId, substr($id, strlen('relationId_')));
                            $dbUpdate = true;
                        }
                    }
                }

                $ciTypeDaoImpl->deleteCiTypeAttributeByTypeId($typeId);

                for ($i = 0; $i < 240; $i++) {

                    if ($formData['addAttribute_' . $i])
                        $ciTypeDaoImpl->saveCiTypeAttribute($typeId, $formData['addAttribute_' . $i], $formData['ismandatory_' . $i]);

                }


                //insert searchlist

                // check if exists
                $searchListDaoImpl = new Dao_SearchList();

                $child        = $searchListDaoImpl->getSearchListByCiTypeId($typeId);
                $searchListId = $child[Db_SearchList::ID];

                $data                               = array();
                $data[Db_SearchList::IS_SCROLLABLE] = $formData['scrollable'];;
                $data[Db_SearchList::IS_ACTIVE]  = '1';
                $data[Db_SearchList::CI_TYPE_ID] = $typeId;


                if (!$searchListId) {
                    // create new
                    $searchListId = $searchListDaoImpl->insertSearchList($data);
                } else {
                    // delete configured search list attributes
                    $searchListDaoImpl->updateSearchList($searchListId, $data);
                    $searchListDaoImpl->deleteSearchListAttributes($searchListId);
                }

                $maxElements = 20;
                // insert new attributes in search list attributes table
                for ($i = 1; $i <= $maxElements; $i++) {
                    if ($formData['create_' . $i]) {
                        $searchListDaoImpl->insertSearchListAttributes($searchListId, $formData['create_' . $i], $i, $formData['width_' . $i]);
                    }
                }

            } catch (Exception $e) {
                throw new Exception_Citype_MappingFailed($e);
            }

            return $dbUpdate;
        } catch (Exception_Citype $e) {
            throw new Exception_Citype_UpdateItemNotFound($e);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            if ($e instanceof Exception_Citype)
                throw $e;

            throw new Exception_Citype_UpdateFailed($e);
        }
    }


    public function handleIconUpload($iconData)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);

        $finaldestination = APPLICATION_PUBLIC . $config->file->upload->path->folder . $config->file->upload->icon->folder;
        $date             = date("YmdHms\_");
        $newFile          = $date . $iconData;
        if (!rename($finaldestination .'/'. $iconData, $finaldestination .'/'. $newFile)) {
            throw new Exception_File_RenamingFailed();
        }
        return $newFile;
    }


    public function removeImage($typeId)
    {
        try {
            $ciTypeDaoIml = new Dao_CiType();
            $ciTypeDaoIml->removeCiTypeImage($typeId);
        } catch (Exception $e) {
            throw new Exception_Citype_DeleteImageFailed();
        }
    }


}