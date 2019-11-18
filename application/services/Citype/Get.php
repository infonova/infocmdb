<?php

/**
 *
 *
 *
 */
class Service_Citype_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 401, $themeId);
    }


    /**
     * retrieves a list of ci types by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getCitypeList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $this->logger->log("Service_Citype: getCitypeList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/citype.ini', APPLICATION_ENV);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['citype'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $ciTypeDaoImpl = new Dao_CiType();

        $select = array();
        if ($filter) {
            $select = $ciTypeDaoImpl->getCiTypesForPaginationWithFilter($filter, $orderBy, $direction);
        } else {
            $select = $ciTypeDaoImpl->getCiTypesForPagination($orderBy, $direction);
        }

        unset($ciTypeDaoImpl);


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $result              = array();
        $result['paginator'] = $paginator;
        return $result;
    }

    public function getFilterForm($filter = null)
    {
        $form = new Form_Filter($this->translator);
        if ($filter) {
            $form->populate(array('search' => $filter));
        }
        return $form;
    }

    public function getStoredIcons()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $link   = '/' . $config->file->upload->path->folder . $config->file->upload->icon->folder . '/';
        $images = array();

        $supportedImageExtensions = array(
            'gif',
            'jpg',
            'jpeg',
            'png',
            'bmp',
        );

        $dir = scandir('../public' . $link);
        $i   = 0;
        foreach ($dir as $item) {
            $filePath  = APPLICATION_PUBLIC . $link . $item;
            $fileInfo  = pathinfo($filePath);
            $extension = strtolower($fileInfo['extension']);

            if (is_file($filePath) && in_array($extension, $supportedImageExtensions)) {
                array_push($images, $item);
                $i++;
            }
        }

        return array("images" => $images, "path" => $link);
    }


    /**
     * retrieves a single Ci Type
     *
     * @param int $typeId
     */
    public function getCiType($typeId)
    {
        try {
            $ciTypeDaoImpl = new Dao_CiType();
            $ciType        = $ciTypeDaoImpl->getCiType($typeId);
            if (!$ciType) {
                throw new Exception_Citype_RetrieveNotFound();
            }
            return $ciType;
        } catch (Exception $e) {
            if ($e instanceof Exception_Citype)
                throw $e;

            throw new Exception_Citype_RetrieveFailed($e);
        }
    }

    /**
     * retrieves all necessary Data for a Ci Type
     *
     * @param int $typeId
     */
    public function getCiTypeData($typeId)
    {
        try {
            $ciTypeDaoImpl     = new Dao_CiType();
            $ciType            = $ciTypeDaoImpl->getCiType($typeId);
            $searchListDaoImpl = new Dao_SearchList();
            $searchLists       = $searchListDaoImpl->getSearchListAttributesByCiTypeId($typeId);

            if (!$ciType) {
                throw new Exception_Citype_RetrieveNotFound();
            }

            $formData                            = array();
            $formData['defaultProject']          = $ciType[Db_CiType::DEFAULT_PROJECT_ID];
            $formData['defaultAttribute']        = $ciType[Db_CiType::DEFAULT_ATTRIBUTE_ID];
            $formData['defaultSortAttribute']    = $ciType[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID];
            $formData['isDefaultSortAsc']        = $ciType[Db_CiType::IS_DEFAULT_SORT_ASC];
            $formData['name']                    = trim($ciType[Db_CiType::NAME]);
            $formData['description']             = trim($ciType[Db_CiType::DESCRIPTION]);
            $formData['note']                    = trim($ciType[Db_CiType::NOTE]);
            $formData['allowCiAttach']           = $ciType[Db_CiType::IS_CI_ATTACH];
            $formData['allowAttributeAttach']    = $ciType[Db_CiType::IS_ATTRIBUTE_ATTACH];
            $formData['createButtonDescription'] = trim($ciType[Db_CiType::CREATE_BUTTON_DESCRIPTION]);
            $formData['xml']                     = trim($ciType[Db_CiType::TAG]);
            $formData['ticketEnabled']           = $ciType[Db_CiType::IS_TAB_ENABLED];
            $formData['eventEnabled']            = $ciType[Db_CiType::IS_EVENT_ENABLED];
            $formData['orderNumber']             = $ciType[Db_CiType::ORDER_NUMBER];
            $formData['icon']                    = $ciType[Db_CiType::ICON];
            $formData['query']                   = $ciType[Db_CiType::QUERY];

            $ciTypeList     = array();
            $ciTypeParentId = null;

            $ciIdToCheck = $ciType[Db_CiType::PARENT_CI_TYPE_ID];
            $loop        = true;
            if ($ciIdToCheck == "0") {
                $ciTypeParentId = 0;
                $loop           = false;
            }

            while ($loop) {
                $select      = $this->getCiType($ciIdToCheck);
                $ciIdToCheck = $select[Db_CiType::PARENT_CI_TYPE_ID];

                if ($select[Db_CiType::PARENT_CI_TYPE_ID] == '0') {
                    $ciTypeParentId = $select[Db_CiType::ID];
                    $loop           = false;
                    break;
                } else {
                    if ($select[Db_CiType::ID] != $currentCiType[Db_CiType::ID])
                        array_push($ciTypeList, $select[Db_CiType::ID]);
                    else
                        break;
                }
            }

            // now we have to create formdata.
            $formData['parentCiType'][] = $ciTypeParentId;

            // fake the children
            $currentChild = 1;

            $ciTypesCounter = count($ciTypeList);
            for ($i = $ciTypesCounter - 1; $i >= 0; $i--) {
                $childName                  = 'child_' . $currentChild;
                $formData['parentCiType'][] = $ciTypeList[$i];
                $currentChild++;
            }


            $attributes = $this->getAttributes($typeId);
            if ($attributes) {
                $i = 0;

                foreach ($attributes as $attribute) {

                    $formData['addAttribute_' . $i] = $attribute[Db_Attribute::ID];
                    $formData['ismandatory_' . $i]  = $attribute[Db_CiTypeAttribute::IS_MANDATORY];

                    $i++;
                }


            }

            $relations = $this->getRelations($typeId);
            if ($relations) {
                foreach ($relations as $relation) {
                    $formData['relationId_' . $relation[Db_CiTypeRelationType::CI_RELATION_TYPE_ID]]            = true;
                    $formData['relationId_' . $relation[Db_CiTypeRelationType::CI_RELATION_TYPE_ID] . '_limit'] = $relation[Db_CiTypeRelationType::MAX_AMOUNT];
                    $formData['relationId_' . $relation[Db_CiTypeRelationType::CI_RELATION_TYPE_ID] . '_order'] = $relation[Db_CiTypeRelationType::ORDER_NUMBER];
                }
            }


            foreach ($searchLists as $list) {
                $formData['scrollable']                                            = $list[Db_SearchList::IS_SCROLLABLE];
                $formData['create_' . $list[Db_SearchListAttribute::ORDER_NUMBER]] = $list[Db_SearchListAttribute::ATTRIBUT_ID];
                $formData['width_' . $list[Db_SearchListAttribute::ORDER_NUMBER]]  = $list[Db_SearchListAttribute::COLUMN_WIDTH];
            }


            return $formData;
        } catch (Exception $e) {
            if ($e instanceof Exception_Citype)
                throw $e;

            throw new Exception_Citype_RetrieveFailed($e);
        }
    }

    public function getProject($projectId)
    {
        try {
            $projectDaoImpl = new Dao_Project();
            return $projectDaoImpl->getProject($projectId);
        } catch (Exception $e) {
            throw new Exception_Citype_RetrieveNotFound($e);
        }
    }

    public function getAttributes($ciTypeId)
    {
        try {
            $ciTypeDaoImpl = new Dao_CiType();
            return $ciTypeDaoImpl->getAttributesByCiTypeId($ciTypeId);
        } catch (Exception $e) {
            throw new Exception_Citype_RetrieveNotFound($e);
        }
    }

    public function getRelations($ciTypeId)
    {
        try {
            $ciTypeDaoImpl = new Dao_CiType();
            return $ciTypeDaoImpl->getRelationsByCiTypeId($ciTypeId);
        } catch (Exception $e) {
            throw new Exception_Citype_RetrieveNotFound($e);
        }
    }

    public function getAttribute($attributeId)
    {
        try {
            $attributeDaoImpl = new Dao_Attribute();
            return $attributeDaoImpl->getAttribute($attributeId);
        } catch (Exception $e) {
            throw new Exception_Citype_RetrieveNotFound($e);
        }
    }

}