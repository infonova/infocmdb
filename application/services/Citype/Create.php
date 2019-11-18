<?php

/**
 *
 *
 *
 */
class Service_Citype_Create extends Service_Abstract
{

    private static $ciTypeNamespace = 'CiTypeController';

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 402, $themeId);
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
     * @param unknown_type $isPost
     * @param unknown_type $values
     */
    public function getCreateCiTypeForm($attributes, $relations, $maxElements = null)
    {
        $createCiTypeConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/citype.ini', APPLICATION_ENV);

        $ciTypeDaoImpl = new Dao_CiType();
        $select        = $ciTypeDaoImpl->getRootCiTypeRowset();

        // put the root ci data in useable content
        $rootCiTypes    = array();
        $rootCiTypes[0] = ' ';
        foreach ($select as $row) {
            if ($row[Db_CiType::ID] != $updateCiId)
                $rootCiTypes[$row[Db_CiType::ID]] = $row[Db_CiType::NAME] . ' (' . $row[Db_CiType::DESCRIPTION] . ')';
        }
        // memory friendly
        unset($select);

        $projectDaoImpl = new Dao_Project();
        $projects       = $projectDaoImpl->getProjectRowset(true);
        $projectList    = array();
        $projectList[0] = ' ';
        foreach ($projects as $project)
            $projectList[$project[Db_Project::ID]] = $project[Db_Project::DESCRIPTION];

        // now create the form
        $form = new Form_Citype_Create($this->translator, $rootCiTypes);

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


    public function getChildElementFormField($ciTypeId, $parentCiTypeId, $childCount, $childvalue = null)
    {
        $ciTypeDaoImpl = new Dao_CiType();
        $children      = $ciTypeDaoImpl->retrieveCiTypeChildElements($parentCiTypeId);

        if (count($children) > 0) {
            $child       = array();
            $child[null] = '';

            foreach ($children as $row) {
                if ($row[Db_CiType::ID] != $ciTypeId)
                    $child[$row[Db_CiType::ID]] = $row[Db_CiType::NAME] . ' (' . $row[Db_CiType::DESCRIPTION] . ')';
            }

            return Form_Citype_Create::getChild($child, $childCount, $childvalue);
        }
    }


    public function createCitype($formData)
    {
        try {


            $ciType[Db_CiType::PARENT_CI_TYPE_ID]         = $formData['parentCiType'];
            $ciType[Db_CiType::DEFAULT_PROJECT_ID]        = $formData['defaultProject'];
            $ciType[Db_CiType::DEFAULT_ATTRIBUTE_ID]      = $formData['defaultAttribute'];
            $ciType[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID] = $formData['defaultSortAttribute'];
            $ciType[Db_CiType::NAME]                      = trim($formData['name']);
            $ciType[Db_CiType::DESCRIPTION]               = trim($formData['description']);
            $ciType[Db_CiType::NOTE]                      = trim($formData['note']);
            $ciType[Db_CiType::IS_CI_ATTACH]              = $formData['allowCiAttach'];
            $ciType[Db_CiType::IS_ATTRIBUTE_ATTACH]       = $formData['allowAttributeAttach'];
            $ciType[Db_CiType::CREATE_BUTTON_DESCRIPTION] = trim($formData['createButtonDescription']);
            $ciType[Db_CiType::IS_TAB_ENABLED]            = $formData['ticketEnabled'];
            $ciType[Db_CiType::IS_EVENT_ENABLED]          = $formData['eventEnabled'];
            $ciType[Db_CiType::ORDER_NUMBER]              = $formData['orderNumber'];
            $ciType[Db_CiType::IS_ACTIVE]                 = '1';
            $ciType[Db_CiType::IS_DEFAULT_SORT_ASC]       = $formData['isDefaultSortAsc'];
            $ciType[Db_CiType::TAG]                       = trim($formData['xml']);
            $ciType[Db_CiType::QUERY]                     = $formData['query'];


            if ($formData['icon']) {
                $ciType[Db_CiType::ICON] = $this->handleIconUpload($formData['icon']);
            } elseif ($formData['storedIcon']) {
                $ciType[Db_CiType::ICON] = $formData['storedIcon'];
            }

            if ($formData['child_1']) {
//				var_dump($formData['child_1']);exit;
                $doaUtlCiTypes   = new Util_AttributeType_Type_CiType();
                $selectedCiTypes = $doaUtlCiTypes->match_regex('[child_.]', $formData);
                if ($selectedCiTypes == null) {
                    $ciType[Db_CiType::PARENT_CI_TYPE_ID] = $ciTypeData['parentCiType'];
                } else {
                    $ciType[Db_CiType::PARENT_CI_TYPE_ID] = array_pop($selectedCiTypes);
                }
            }

            $ciTypeDaoImpl = new Dao_CiType();
            $typeId        = $ciTypeDaoImpl->insertCiType($ciType);
            if (!$typeId) {
                $this->logger->log('insert CiType failed! No CiType added', Zend_Log::CRIT);
                throw new Exception();
            } else {
                try {


                    $mapping = $formData;


                    $ciRelationDaoImpl = new Dao_CiRelation();
                    foreach ($mapping as $id => $value) {
                        if (strpos($id, 'relationId_') === 0 && !strpos($id, '_limit') && !strpos($id, '_order')) {
                            if ($value)
                                $ciRelationDaoImpl->saveCiTypeRelation($typeId, substr($id, strlen('relationId_')), $mapping[$id . '_limit'], $mapping[$id . '_order']);
                        }
                    }

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

                    return $typeId;
                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::CRIT);
                    throw new Exception_Citype_InsertFailed($e);
                }
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Citype_InsertFailed($e);
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
}