<?php

/**
 *
 *
 *
 */
class Service_Ci_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 301, $themeId);
    }


    /**
     * retrieves a list of cis by the given restrictions
     *
     * attributes + values included
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */


    public function getCiList($typeId, $userId, $themeId, $projectId, $itemsCountPerPage = null, $page = null, $orderBy = null, $filter_set = false, $ciRelationTypeId = null, $sourceCiid = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/ci.ini', APPLICATION_ENV);

        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;
        $listEdit         = $config->pagination->listEdit;
        $defaultOrderBy   = null;

        $isQuery = false;
        if ($page) {
            $limit_from = $itemsCountPerPage * ($page - 1);
        } else {
            $limit_from = 0;
        }

        $ciTypeDao = new Dao_CiType();
        $ciType    = $ciTypeDao->getRawCiType($typeId);


        // retrieve all ci's of the given type and project id(optional)
        $ciDao = new Dao_Ci();

        $breadcrumbs = $ciTypeDao->getBreadcrumbHierarchy($typeId);
        $breadcrumbs = array_reverse($breadcrumbs);

        #handle query ci_types
        if (!empty($ciType[Db_CiType::QUERY])) {
            $result        = $ciDao->getListResultQueryForCiList($ciType[Db_CiType::QUERY], $orderBy, array(':user_id:' => $userId, ':project_id:' => $projectId));
            $numberRows    = count($result);
            $attributeList = array();
            $columnCounter = 1;#add counter for ordering
            foreach ($result[0] as $r_key => $r_value) {
                $attributeList[] = array('name' => $r_key, 'description' => $r_key);
                $columnCounter++;
            }
            $isQuery = true;
        } else {#handle normal ci_types

            $permissionList   = null;
            $extraPermissions = $ciDao->getCiPermissionForUser($userId);

            foreach ($extraPermissions as $perm) {
                if (!$permissionList) {
                    $permissionList = $perm[Db_CiPermission::CI_ID];
                } else {
                    $permissionList .= ", " . $perm[Db_CiPermission::CI_ID];
                }
            }

            if (!$projectId) {
                // get permissionlist
                $projectDao  = new Dao_Project();
                $projectList = $projectDao->getProjectsByUserId($userId);

                foreach ($projectList as $p) {
                    if (!$projectId) {
                        $projectId = $p[Db_Project::ID];
                    } else {
                        $projectId .= ', ' . $p[Db_Project::ID];
                    }
                }
            }

            // get Roles
            $roleDao  = new Dao_Role();
            $roleList = $roleDao->getCurrentRolesForUser($userId);

            $newRoles = array();
            foreach ($roleList as $role) {
                array_push($newRoles, $role[Db_Role::ID]);
            }


            // make joind to ci_type_attribute table
            $attributeDao  = new Dao_Attribute();
            $attributeList = $attributeDao->getAttributesByTypeId($typeId, $themeId, $userId);


            // get search list scrollable
            $scrollbar  = false;
            $searchList = $attributeDao->getScrollable($typeId);

            if ($searchList && $searchList[Db_SearchList::IS_SCROLLABLE]) {
                $scrollbar = true;
            }


            $attributeListIds = array();
            foreach ($attributeList as $attribute) {
                $attributeListIds[] = $attribute[Db_Attribute::ID];
            }


            // if no order defined, use the default ci_type configuration
            if (empty($orderBy)) {
                $defaultSortAttribute = null;
                if ($ciType[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID]) {
                    $defaultSortAttribute = $ciType[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID];
                    $isDefaultSortAsc     = $ciType[Db_CiType::IS_DEFAULT_SORT_ASC];
                } else {
                    // check if one of the parent ci_types has a order defined
                    $parentCiTypeId = $ciType[Db_CiType::PARENT_CI_TYPE_ID];

                    // loop through all parents
                    while (true) {
                        if (!$parentCiTypeId) {
                            break;
                        }

                        $parentCi       = $ciTypeDao->getRawCiType($parentCiTypeId);
                        $parentCiTypeId = $parentCi[Db_CiType::PARENT_CI_TYPE_ID];

                        if ($parentCi[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID]) {
                            $defaultSortAttribute = $parentCi[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID];
                            $isDefaultSortAsc     = $parentCi[Db_CiType::IS_DEFAULT_SORT_ASC];
                            break;
                        }
                    }
                }

                //if default-attribute is set in any of the ci-types
                if ($defaultSortAttribute) {
                    $atr = $attributeDao->getAttribute($defaultSortAttribute);
                    if ($isDefaultSortAsc) {
                        $direction = "ASC";
                    } else {
                        $direction = "DESC";
                    }
                    #only add ordering if attribute is in result
                    if (in_array($defaultSortAttribute, $attributeListIds)) {
                        $orderBy        = array($atr[Db_Attribute::NAME] => $direction);
                        $defaultOrderBy = array($atr[Db_Attribute::NAME] => $direction);
                    }
                }
            }

            $ciList = $ciDao->getCiListForCiIndex($typeId, $projectId, $userId, null, null, null, null, $permissionList, null, $newRoles, $ciRelationTypeId, $sourceCiid);


            $sortedList = array();
            foreach ($ciList as $deleteKey => $ci) {
                array_push($sortedList, $ci);
                #unset($ciList[$deleteKey]);
            }

            $ciList = $sortedList;

            $numberRows = count($ciList);

            $result = $this->getListResultForCiList($attributeList, $ciList);

            if (is_array($result) && isset($orderBy) && count($result) > 0) {

                // add pseudo column to attribute list
                if ($ciRelationTypeId !== null) {
                    $attributeList[] = array(
                        'name'        => 'ciRelationDirection',
                        'description' => 'ciRelationDirection',
                    );
                }
                $result = $this->array_sort($result, $orderBy, $attributeList);
            }

        }

        if (!$filter_set && $itemsCountPerPage !== null) {
            $limit_to = $limit_from + $itemsCountPerPage;
            for ($i = 0; $i < $numberRows; $i++) {
                if ($i < $limit_from || $i >= $limit_to) {
                    unset($result[$i]);
                }
            }
        }


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($numberRows));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );


        $typeName                  = $ciType[Db_CiType::DESCRIPTION];
        $ciCreateButtonDescription = $ciType[Db_CiType::CREATE_BUTTON_DESCRIPTION];
        $ciTypeAttach              = ($ciType[Db_CiType::IS_CI_ATTACH] && $ciType[Db_CiType::IS_ACTIVE]);

        return array(
            'ciList'                  => $result,
            'typeName'                => $typeName,
            'paginator'               => $paginator,
            'createButtonDescription' => $ciCreateButtonDescription,
            'ciTypeAttach'            => $ciTypeAttach,
            'attributeList'           => $attributeList,
            'scrollbar'               => $scrollbar,
            'breadcrumbs'             => $breadcrumbs,
            'listEdit'                => $listEdit,
            'isQuery'                 => $isQuery,
            'defaultOrderBy'          => $defaultOrderBy,
        );
    }

    public function array_sort($array, $orderBy, $attributeList = false)
    {
        if (empty($orderBy) || !is_array($orderBy)) {
            return $array;
        }

        /**
         *    SORT_NATURAL requires PHP 5.4+
         *    Fallback to default SORT_REGULAR
         */
        $sortflag    = SORT_REGULAR;
        $sortnatural = false;
        if (is_numeric(SORT_NATURAL)) {
            $sortnatural = true;
            $sortflag    = (SORT_NATURAL | SORT_FLAG_CASE);
        }

        /**
         * rearrange attributeList so we can get attribute details with their _name_ as _key_
         */
        if ($attributeList !== false) {
            foreach ($attributeList as $attribute) {
                $attributeSort[$attribute['name']] = $attribute;
            }
        }

        $colarr = array();
        $eval   = 'array_multisort(';
        foreach ($orderBy as $col => $order) {
            $colarr[$col] = array();

            // $array von Zeilenweise
            // (1 =('col1' => '1', 'col2' => '2'), 2 =('col1' => 'set1', 'col2' => 'set4'))
            // ==>
            // arr(Col1 => ('1','set1')) ...
            foreach ($array as $k => $row) {
                if ($row[$col] != '') {
                    if (!$sortnatural // ip2long is only required if natural sort is not supported (PHP 5.4+)
                        && $attributeSort[$col]['is_numeric'] == '1'
                        && $longip = sprintf('%u', ip2long($row[$col]))
                    ) {
                        $colarr[$col][$k] = $longip;
                    } else {
                        $colarr[$col][$k] = strtolower($row[$col]);
                    }
                } else {
                    $colarr[$col][$k] = $row[$col];
                }
            }

            if (!$sortnatural // numberic sort is only required if natural sort is not supported (PHP 5.4+)
                && $attributeSort[$col]['is_numeric'] == '1'
            ) {
                $sortflag = SORT_NUMERIC;
            }

            if(!empty($order)) {
                $eval .= '$colarr[\'' . $col . '\']' . ',' .
                    ($order == "DESC" ? SORT_DESC : SORT_ASC) . ',' .
                    $sortflag . ',';
            }
        }
        $eval .= '$array);';

        eval($eval);
        $ret = array_values($array); //recreate array-index
        return $ret;
    }

    public function getPaginator($ciList, $page = 1, $typeId)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/ci.ini', APPLICATION_ENV);


        $numberRows = count($ciList);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['ci'][$typeId];
        $itemsPerPage            = $config->pagination->itemsPerPage;
        $scrollingStyle          = $config->pagination->scrollingStyle;
        $scrollingControl        = $config->pagination->scrollingControl;

        if ($page) {
            $limit_from = $itemsCountPerPage * ($page - 1);
        } else {
            $limit_from = 0;
        }

        $limit_to = $limit_from + $itemsCountPerPage;
        for ($i = 0; $i < $numberRows; $i++) {
            if ($i < $limit_from || $i >= $limit_to) {
                unset($ciList[$i]);
            }
        }


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($numberRows));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);


        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );


        return array(
            'p' => $paginator,
            'c' => $ciList,
        );


    }


    /**
     *
     * returns a displayable list (ci list) of ci's by attributeList
     * and ciList ready for further process.
     *
     * Values (esp. citype attributes) resolved
     *
     * @param array $attributeList list of Attributes to display (searchList!)
     * @param unknown_type $ciList        (List of ci IDs to process)
     * @param unknown_type $orderBy       opt.
     * @param unknown_type $direction     opt.
     */
    public function getListResultForCiList($attributeList, $ciList, $orderBy = null, $direction = null, $isNumeric = false, $projectId_list = null, $history = false)
    {
        $ciDao = new Dao_Ci();

        $ciString      = "";
        $ciListViaCiId = array();
        foreach ($ciList as $ci) {
            $ciString                 .= $ci['id'] . ', ';
            $ciListViaCiId[$ci['id']] = $ci;
        }
        $ciString .= '0';


        if ($history) {

            $result = $ciDao->getCiConfigurationStatementByCiTypeIdHistory($attributeList, $ciString, $orderBy, $direction, $projectId_list);


        } else {

            $result = $ciDao->getCiConfigurationStatementByCiTypeId($attributeList, $ciString, $orderBy, $direction, $projectId_list);


        }

        $isSetColor          = false;
        $isSetCiRelationId   = false;
        $isSetCiRelationData = false;

        if (array_key_exists(0, $ciList)) {
            $firstRow = $ciList[0];
            if (array_key_exists(Db_CiHighlight::COLOR, $firstRow)) {
                $isSetColor = true;
            }

            if (array_key_exists('ciRelationId', $firstRow)) {
                $isSetCiRelationId = true;
            }

            if (array_key_exists('ciRelationTypeDescription', $firstRow)) {
                $isSetCiRelationData = true;
            }
        }

        foreach ($result as $key => $row) {
            $ciId = $row['id'];

            if ($isSetColor) {
                $result[$key][Db_CiHighlight::COLOR] = $ciListViaCiId[$ciId][Db_CiHighlight::COLOR];
            }

            if ($isSetCiRelationId) {
                $result[$key]['ciRelationId'] = $ciListViaCiId[$ciId]['ciRelationId'];
            }

            if ($isSetCiRelationData) { // relation based infos
                $result[$key]['ciRelationDirection']               = $ciListViaCiId[$ciId]['ciRelationDirection'];
                $result[$key]['ciRelationForeignColumn']           = $ciListViaCiId[$ciId]['ciRelationForeignColumn'];
                $result[$key]['ciRelationTypeDescription']         = $ciListViaCiId[$ciId]['ciRelationTypeDescription'];
                $result[$key]['ciRelationTypeDescriptionOptional'] = $ciListViaCiId[$ciId]['ciRelationTypeDescriptionOptional'];
                $result[$key]['ciRelationNote']                    = $ciListViaCiId[$ciId]['ciRelationNote'];
                $result[$key]['ciRelationValidFrom']               = $ciListViaCiId[$ciId]['ciRelationValidFrom'];
            }

        }

        return $this->decodeCiAttributes($result, $attributeList);
    }


    /**
     * decode a list of results. this means that the db result of a list of ci_attributes are reprocessed and put in a displayable format.
     * eg. display of ci_type name instead of id for ci_type attribute type
     *
     * @param unknown_type $result
     * @param unknown_type $attributeList
     */
    public function decodeCiAttributes($result, $attributeList, array $attributesWithoutValueManipulation = array())
    {

        #return $this->decodeCiAttributesOld($result, $attributeList);

        if (count($result) == 1) {
            $context = 'row';
        } else {
            $context = 'list';
        }

        $utilCiType         = new Util_CiType();
        $utilQuery          = new Util_AttributeType_Type_Query();
        $utilSelectQuery    = new Util_AttributeType_Type_SelectQuery();
        $utilCheckbox       = new Util_AttributeType_Type_Checkbox();
        $utilZahlungsmittel = new Util_AttributeType_Type_Zahlungsmittel();

        $attributesWithoutValueManipulationExtra = array(
            Util_AttributeType_Type_TextEdit::ATTRIBUTE_TYPE_ID   => 'exclude',
            Util_AttributeType_Type_Textarea::ATTRIBUTE_TYPE_ID   => 'exclude',
            Util_AttributeType_Type_Select::ATTRIBUTE_TYPE_ID     => 'exclude',
        );
        $attributesWithoutValueManipulation = $attributesWithoutValueManipulation + $attributesWithoutValueManipulationExtra;

        $attributesToManipulate = array();
        foreach ($attributeList as $attribute) {
            if (!isset($attributesWithoutValueManipulation[$attribute[Db_Attribute::ATTRIBUTE_TYPE_ID]])) {
                $attributesToManipulate[] = $attribute;
            }
        }

        foreach ($result as $key => $resRow) {
            foreach ($attributesToManipulate as $attribute) {
                $attributeId   = $attribute[Db_Attribute::ID];
                $attributeName = $attribute[Db_Attribute::NAME];

                if (isset($resRow[$attributeName])) {

                    switch ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID]) {
                        case Util_AttributeType_Type_Attachment::ATTRIBUTE_TYPE_ID:
                            if($result[$key][$attributeName] === '') {
                                break;
                            }
                            $result[$key][$attributeName] = '<a href="'. APPLICATION_URL .'download/ci/ciattributeid/'.$result[$key][$attributeName.'ID'].'/file/'.$result[$key][$attributeName] . '">' . $result[$key][$attributeName] . '</a>';
                            break;
                        case Util_AttributeType_Type_CiType::ATTRIBUTE_TYPE_ID:
                            $result[$key][$attributeName] = $utilCiType->getCiTypeValueToDisplay($attributeId, $resRow[$attributeName], $context);
                            break;

                        case Util_AttributeType_Type_CiTypePersist::ATTRIBUTE_TYPE_ID:
                            if ($resRow[Db_CiAttribute::NOTE]) {
                                $result[$key][$attributeName] = $resRow[$attributeName];
                            } else if ($resRow[Db_Ci::ID]) {
                                $result[$key][$attributeName] = $this->getAttributeCiTypePersistValue($attributeId, $resRow[Db_Ci::ID], $result[$key][$attributeName]);
                            } else {
                                $result[$key][$attributeName] = $this->getAttributeCiTypeValue($attributeId, $resRow[$attributeName]);
                            }
                            break;

                        case Util_AttributeType_Type_SelectPopup::ATTRIBUTE_TYPE_ID:
                            if ($resRow[Db_Ci::ID]) {
                                $result[$key][$attributeName] = $this->getAttributeCiTypePersistValue($attributeId, $resRow[Db_Ci::ID], $result[$key][$attributeName]);
                            } else {
                                $result[$key][$attributeName] = $this->getAttributeCiTypeValue($attributeId, $resRow[$attributeName]);
                            }
                            break;

                        case Util_AttributeType_Type_Query::ATTRIBUTE_TYPE_ID:
                            if ($resRow[Db_Ci::ID]) {
                                $value                        = $utilQuery->setAttributeValue($attribute, $resRow[Db_Ci::ID]);
                                $result[$key][$attributeName] = $value[Db_CiAttribute::VALUE_TEXT];
                            }
                            break;

                        case Util_AttributeType_Type_Link::ATTRIBUTE_TYPE_ID:
                            $result[$key][$attributeName] = '<a href="' . $result[$key][$attributeName] . '">' . $result[$key][$attributeName] . '</a>';
                            break;

                        case Util_AttributeType_Type_Filter::ATTRIBUTE_TYPE_ID:
                            if ($resRow[Db_Ci::ID]) {
                                $result[$key][$attributeName] = $this->getAttributeCiTypePersistValue($attributeId, $resRow[Db_Ci::ID], $result[$key][$attributeName]);
                            }
                            break;

                        case Util_AttributeType_Type_SelectQuery::ATTRIBUTE_TYPE_ID:
                            $value                        = $utilSelectQuery->setAttributeValue(array('id' => $attributeId, Db_CiAttribute::VALUE_CI => $resRow[$attributeName], Db_Attribute::DISPLAY_STYLE => 'ciListExport'), $resRow[Db_Ci::ID], null, true);
                            $result[$key][$attributeName] = $value[Db_CiAttribute::VALUE_TEXT];
                            break;

                        case Util_AttributeType_Type_Checkbox::ATTRIBUTE_TYPE_ID:
                            $value                        = $utilCheckbox->setAttributeValue(array('id' => $attributeId, Db_CiAttribute::VALUE_TEXT => $resRow[$attributeName]), $resRow[Db_Ci::ID]);
                            $result[$key][$attributeName] = $value[Db_CiAttribute::VALUE_TEXT];
                            break;

                        case Util_AttributeType_Type_Zahlungsmittel::ATTRIBUTE_TYPE_ID:
                            $value                        = $utilZahlungsmittel->setAttributeValue(array('id' => $attributeId, Db_CiAttribute::VALUE_TEXT => $resRow[$attributeName]), $resRow[Db_Ci::ID]);
                            $result[$key][$attributeName] = $value[Db_CiAttribute::VALUE_TEXT];
                            break;

                        case Util_AttributeType_Type_Date::ATTRIBUTE_TYPE_ID:
                            $result[$key][$attributeName] = str_replace(' 00:00:00', '', $result[$key][$attributeName]);
                            break;

                        case Util_AttributeType_Type_DateTime::ATTRIBUTE_TYPE_ID:
                            $result[$key][$attributeName] = str_replace(' 00:00:00', '', $result[$key][$attributeName]);
                            break;

                        default:
                            $result[$key][$attributeName] = htmlentities($result[$key][$attributeName]);
                    }

                }

            }
        }

        return $result;
    }


    private function getAttributeCiTypePersistValue($attributeId, $ciId, $value)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $ciAttribute      = $attributeDaoImpl->getCiAttributesByCiId($ciId, $attributeId, $value);


        return $ciAttribute[Db_CiAttribute::NOTE];
    }

    public function getFilterForm($attributes, $filter = null, $isQuery = false)
    {
        $relationDaoImpl = new Dao_CiRelation();

        $directions    = $relationDaoImpl->getDirections();
        $directionList = array();
        foreach ($directions as $dir) {
            $directionList[$dir[Db_CiRelationDirection::ID]] = $dir[Db_CiRelationDirection::DESCRIPTION];
        }

        $columnOptions = array(
            'columnOptions' => array(
                'ciRelationDirection' => array(
                    'element_type'   => 'select',
                    'select_options' => $directionList,
                ),
            ),
        );

        $form = new Form_Filter($this->translator, $attributes, $columnOptions);

        if ($filter) {
            $form->populate($filter);
        }

        return $form;
    }

    /**
     * retrieves a list of ci ids by the given restrictions
     *
     * returns a list of ci IDS only
     * !
     *
     * @param integer $typeId
     * @param integer $userId
     * @param integer $themeId
     * @param integer $projectId
     */
    public function getCiListIds($typeId, $userId, $themeId, $projectId, $limitFrom = null, $limitTo = null, $recursive = false)
    {
        $ciDao = new Dao_Ci();

        if (!$recursive) {
            return $ciDao->getCiListByCiTypeId($typeId, $projectId, $userId, null, null, $limitFrom, $limitTo);
        }
            // select type hierarchy
        $ciTypeDao = new Dao_CiType();

        $ciList = array();
        $ciList = $this->ciListRecursive($ciList, $typeId, $userId, $ciDao, $ciTypeDao);
        return $ciList;
    }

    private function ciListRecursive($list, $typeId, $userId, &$ciDao, &$ciTypeDao)
    {
        $ci = $ciDao->getCiListByCiTypeId($typeId, array(), $projectId, $userId, null, null, $limitFrom, $limitTo);

        $ciList[$typeId] = array();

        if ($ci)
            $ciList[$typeId]['cilist'] = $ci;

        $typeIdList = $ciTypeDao->retrieveCiTypeChildElements($typeId);


        if ($typeIdList) {
            foreach ($typeIdList as $ciType) {
                $ciList[$typeId][$ciType[Db_CiType::NAME]] = $this->ciListRecursive(array(), $ciType[Db_CiType::ID], $userId, $ciDao, $ciTypeDao);
            }
        }

        return $ciList;

    }

    /**
     *
     * gets all information for one specific CI.
     *
     * @param integer $ciId
     * @param integer $userId
     *
     * @throws Exception_Ci_CiIdInvalid
     * @throws Exception_AccessDenied
     * @throws Exception_Ci_RetrieveNotFound
     */

    public function getCiDetail(int $ciId, int $userId)
    {

        if (is_null($ciId)) {
            throw new Exception_Ci_CiIdInvalid();
        }

        if (!$this->checkPermission($ciId, $userId)) {
            throw new Exception_AccessDenied();
        }

        // select ci type
        $ciTypeDao = new Dao_CiType();
        $ci        = $ciTypeDao->getCi($ciId);
        $ciType    = $ciTypeDao->getCiTypeByCiId($ciId);
        if (is_null($ciType) || !$ciType[Db_CiType::ID]) {
            throw new Exception_Ci_RetrieveNotFound();
        }

        // select ci icon
        $ciIcon = $ciTypeDao->getCiIcon($ciId);

        $fileuploadConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $useDefaultPath   = $fileuploadConfig->file->upload->path->default;
        $uploadPath       = ($useDefaultPath) ? $fileuploadConfig->file->upload->path->folder : $fileuploadConfig->file->upload->path->custom;

        $iconFolder   = $fileuploadConfig->file->upload->icon->folder;
        $iconFullPath = APPLICATION_URL . $uploadPath . $iconFolder . '/';

        // define icon to display
        if (!empty($ciIcon)) {
            $icon = $iconFullPath . $ciIcon;
        } elseif (!empty($ciType[Db_CiType::ICON])) {
            $icon = $iconFullPath . $ciType[Db_CiType::ICON];
        } else {
            $icon = APPLICATION_URL . 'images/ci.png';
        }


        $projectDao     = new Dao_Project();
        $projectList    = $projectDao->getProjectsByUserId($userId);
        $projectId_list = '';
        foreach ($projectList as $p) {

            $projectId_list .= $p[Db_Project::ID] . ',';

        }

        $projectId_list = substr($projectId_list, 0, -1);


        $ciRelationDaoImpl = new Dao_CiRelation();
        $relations         = $ciRelationDaoImpl->getCiRelationsByCiIdExcludingInternalGroupById($ciId, $projectId_list, $ciType[Db_CiType::ID]);
        $relations         = $this->array_sort($relations, array('description' => 'ASC'));

        $breadcrumbs = $this->getCiBreadcrumbs($ciId);

        // select last edit from history
        $ciHistoryDao = new Dao_History();
        $ciHistoryDto = $ciHistoryDao->getModificationDatesForCi($ciId);


        // select zugewiesene Projekte
        $projectDao  = new Dao_Project();
        $projectList = $projectDao->getProjectsByCiId($ciId);


        // select aktive tickets
        $configTicket = new Zend_Config_Ini(APPLICATION_PATH . '/configs/ticket.ini', APPLICATION_ENV);
        $tickets      = $ciTypeDao->getCiTickets($ciId);
        $ticketurl    = $configTicket->url;

        Util_AttributeType_Type_Executeable::insertMissingAttributes($ciId, null);

        // alle attribute selektieren, in gruppen nach ci typ
        $attributeDao  = new Dao_Attribute();
        $attributeList = $attributeDao->getAttributesByCiId($ciId, $userId);
        $types         = $ciTypeDao->retrieveCiTypeHierarchy($ciType[Db_CiType::ID]);

        $types_string = "";

        foreach ($types as $type) {

            $types_string .= $type . ',';

        }

        $types_string = substr($types_string, 0, -1);
        // special handling for query attributes - no database row means inheritance needs to be resolved
        $queryAttributes = $attributeDao->getQueryAttributesByCITypes($types_string, $userId);


        $attributeList = array_merge_recursive($attributeList, $queryAttributes);


        $events = $attributeDao->getAttributesByCiId($ciId, $userId, '1');


        $relationsByRelationTypes = array();
        foreach ($relations as $relation) {
            $relationsByRelationTypes[$relation[Db_CiRelationType::ID]][] = $relation;
        }


        $final = array();
        foreach ($relationsByRelationTypes as $relationsByRelationType) {
            $sourceCounter      = 0;
            $destinationCounter = 0;

            foreach ($relationsByRelationType as $entity) {
                if ($entity[Db_CiRelation::CI_ID_1] == $ciId) {
                    $sourceCounter++;
                } else {
                    $destinationCounter++;
                }
            }

            if ($sourceCounter > 0) {
                $t                   = $relationsByRelationType[0];
                $t['relationsCount'] = $sourceCounter;
                $final[]             = $t;
            }

            if ($destinationCounter > 0) {
                $t                   = $relationsByRelationType[0];
                $t['relationsCount'] = $destinationCounter;
                if ($t[Db_CiRelationType::DESCRIPTION_OPTIONAL]) {
                    $t[Db_CiRelationType::DESCRIPTION] = $t[Db_CiRelationType::DESCRIPTION_OPTIONAL];
                }
                $final[] = $t;
            }
        }
        $relations = $final;

        if (!$attributeList || count($attributeList) <= 0) {
            throw new Exception_AccessDenied();
        }

        // remove duplicate values
        $newAttributeList = array();
        $attributeIds = array();
        foreach ($attributeList as $attribute) {
            if (in_array($attribute["id"], $attributeIds) && $attribute["attributeTypeName"] == "query") {
                continue;
            }
            $attributeIds[] = $attribute["id"];
            $newAttributeList[$attribute['ciAttributeId']] = $attribute;
        }

        $attributeList = $newAttributeList;


        foreach ($attributeList as $key => $attribute) {
            $class               = Util_AttributeType_Factory::get($attribute['attributeTypeName']);
            $attributeList[$key] = $class->setAttributeValue($attribute, $ciId, $uploadPath);


        }


        foreach ($attributeList as $key => $attribute) {
            if ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID
                || $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_Query::ATTRIBUTE_TYPE_ID
                || $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_SelectQuery::ATTRIBUTE_TYPE_ID
            ) {
                if (empty($attribute[Db_CiAttribute::VALUE_TEXT]) || $attribute[Db_CiAttribute::VALUE_TEXT] == ' ')
                    unset($attributeList[$key]);
            }

        }
        $attributeGroupList = $this->getAttributeGroupForDetailView($attributeList);


        return array(
            'ci'             => $ci,
            'ciType'         => $ciType,
            'historyCreated' => $ciHistoryDto['created_at'],
            'historyChange'  => $ciHistoryDto['updated_at'],
            'projectList'    => $projectList,
            'attributeList'  => $attributeGroupList,
            'icon'           => $icon,
            'relations'      => $relations,
            'breadcrumbs'    => $breadcrumbs,
            'tickets'        => $tickets,
            'ticketurl'      => $ticketurl,
            'events'         => $events,
        );
    }

    public function getCiBreadcrumbs($ciId)
    {
        $ciDao     = new Dao_Ci();
        $ciTypeDao = new Dao_CiType();

        $ciType           = $ciTypeDao->getCiTypeByCiId($ciId);
        $defaultAttribute = $ciDao->getDefaultAttribute($ciId);

        // if ci already deleted
        if (empty($ciType)) {
            $ciTypes           = $ciTypeDao->getCiTypeHistoryByCiId($ciId);
            $ciType            = end($ciTypes);
            $defaultAttributes = $ciDao->getDefaultAttributeHistory($ciId);
            $defaultAttribute  = end($defaultAttributes);
        }

        if (isset($defaultAttribute[Db_CiAttribute::VALUE_TEXT])) {
            $defaultAttribute = $defaultAttribute[Db_CiAttribute::VALUE_TEXT];
        } else {
            $defaultAttribute = $ciId;
        }

        $breadcrumbs = array();

        // if ci_type not already deleted
        if(!empty($ciType)) {
            $breadcrumbs = $ciTypeDao->getBreadcrumbHierarchy($ciType[Db_CiType::ID]);
            $breadcrumbs = array_reverse($breadcrumbs);
        }

        $breadcrumbs[] = array(
            'crumbType'   => 'ci',
            'description' => $defaultAttribute,
        );

        return $breadcrumbs;
    }


    public function checkPermission($ciId = null, $userId = null, $attributeId = null)
    {
        try {
            $daoCi = new Dao_User();

            $ciPerm   = $daoCi->getUserCiMapping($userId, $ciId);
            $projPerm = $daoCi->getUserProjectCiMapping($userId, $ciId);
            if (!is_null($userId) && !is_null($attributeId)) {
                $attributePerm = $this->checkAttributeRolePermission($ciId, $userId, $attributeId);
                if (($ciPerm || $projPerm) && $attributePerm) {
                    return true;
                } else {
                    return false;
                }
            }

            if ($ciPerm || $projPerm) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            return false;
        }
    }

    private function sortAttributeList($list, $attribute, $column)
    {
        $newList = array();

        foreach ($list as $key => $entry) {
            if ($key < $attribute[Db_Attribute::ORDER_NUMBER]) {
                $newList[$key] = $entry;
            } else {
                // ++
                $newList[$key + 1] = $entry;
            }
        }
        $newList[$attribute[Db_Attribute::ORDER_NUMBER]][$column] = $attribute;

        return $newList;
    }

    private function getAttributeGroupHierarchy($attributeDaoImpl, $attributeGroupList)
    {
        $returnArray = array();

        foreach ($attributeGroupList as $key => $vi) {
            $vt = $attributeDaoImpl->getAttributeGroup($vi['id']);

            $id                                    = $vt[Db_AttributeGroup::ID];
            $array                                 = array();
            $array[Db_AttributeGroup::ID]          = $vt[Db_AttributeGroup::ID];
            $array[Db_AttributeGroup::NAME]        = $vt[Db_AttributeGroup::NAME];
            $array[Db_AttributeGroup::DESCRIPTION] = $vt[Db_AttributeGroup::DESCRIPTION];
            $array['columns']                      = $vi['columns'];
            $array['attributes']                   = $vi['attributes'];
            $array['readCount']                    = (isset($vi['readCount'])) ? $vi['readCount'] : 0;
            $array['writeCount']                   = (isset($vi['writeCount'])) ? $vi['writeCount'] : 0;

            if ($vt[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID]) {
                // search in return array for matching id
                // else create new hierarchy
                $parentHierarchy = $this->handleParent($attributeDaoImpl, $vt[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID], $array);
                $id              = $parentHierarchy[Db_AttributeGroup::ID];

                $array = $parentHierarchy;
            }


            if (array_key_exists($id, $returnArray)) {
                // we have a conflict

                //TODO BUG 3 verschachtelte AttributGruppen

                if ($array['children']) {

                    if (isset($returnArray[$id]['children']))
                        $array['children'] = array_merge_recursive($returnArray[$id]['children'], $array['children']);

                    if ($returnArray[$id]['attributes']) {

                        if (!isset($array['attributes'])) {
                            $array['attributes'] = array();
                        }
                        $array['attributes'] = array_merge_recursive($array['attributes'], $returnArray[$id]['attributes']);
                    }

                    $newChild = array();
                    foreach ($array['children'] as $val) {
                        $newChild[$val['id']] = $val;
                    }
                    //$array['children'] = $newChild;
                    $returnArray[$id] = $array;
                } else {
                    $returnArray[$id] = array_merge($returnArray[$id], $array);
                }
            } else {
                $returnArray[$id] = $array;
            }
        }


        // TODO is_initial Feature
        //$returnArray = $this->sortduplicateGroups($returnArray);


        return $returnArray;
    }


    private function handleParent($attributeDaoImpl, $attributeGroupId, $oldArray = null)
    {

        $attributeGroup = $attributeDaoImpl->getAttributeGroup($attributeGroupId);

        $array                                 = array();
        $array[Db_AttributeGroup::ID]          = $attributeGroup[Db_AttributeGroup::ID];
        $array[Db_AttributeGroup::NAME]        = $attributeGroup[Db_AttributeGroup::NAME];
        $array[Db_AttributeGroup::DESCRIPTION] = $attributeGroup[Db_AttributeGroup::DESCRIPTION];

        if ($oldArray) {
            if (!isset($array['children'])) {
                $array['children'] = array();
            }
            $array['children'][$oldArray[Db_AttributeGroup::ID]] = $oldArray;
        }

        if ($attributeGroup[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID]) {
            $subArray = $array;

            $array = $this->handleParent($attributeDaoImpl, $attributeGroup[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID]);

            if (!$array['children']) {
                $array['children'] = array();
            }
            $array['children'][$subArray[Db_AttributeGroup::ID]] = $subArray;
        }

        return $array;
    }

    //recursive function with reference to $attributeList
    public static function restrictAttributeList(&$origAttributeList, $newAttributeList, $attributeGroupId = null)
    {
        if (is_null($attributeGroupId)) {
            return $origAttributeList;
        }
        if ($newAttributeList['children']) {
            Service_Ci_Get::restrictAttributeList($origAttributeList, $newAttributeList['children'], $attributeGroupId);
        } else {
            foreach ($newAttributeList as $index => $group) {
                if ($group['children']) {
                    Service_Ci_Get::restrictAttributeList($origAttributeList, $group['children'], $index);
                } else {
                    if ($group['readCount'] == 0) {
                        unset($origAttributeList[$attributeGroupId]['children'][$index]);
                    }
                }
            }
        }
        return $origAttributeList;
    }

    public function getAttributeGroupAttributeList($attributeList)
    {
        if (!$attributeList)
            return array();

        $attributeDaoImpl = new Dao_Attribute();

        // get view Type list
        $attributeGroups = array();
        foreach ($attributeList as $attribute) {
            $attributeGroups[$attribute[Db_Attribute::ATTRIBUTE_GROUP_ID]] = $attribute[Db_Attribute::ATTRIBUTE_GROUP_ID];
        }

        $list = implode(',', $attributeGroups);

        $attributeGroupList = $attributeDaoImpl->getAttributeGroupList($list);

        // make it faster
        $newView = array();
        foreach ($attributeGroupList as $view) {
            $newView[$view[Db_AttributeGroup::ID]] = $view;
        }
        $attributeGroupList = $newView;

        foreach ($attributeList as $attribute) {
            if (!$attributeGroupList[$attribute['attribute_group_id']]['attributes']) {
                $attributeGroupList[$attribute['attribute_group_id']]['attributes'] = array();
                $attributeGroupList[$attribute['attribute_group_id']]['readCount']  = 0;
                $attributeGroupList[$attribute['attribute_group_id']]['writeCount'] = 0;
            }
            if ($attribute['permission_read'] == '1') {
                $attributeGroupList[$attribute['attribute_group_id']]['readCount']++;
            }
            if ($attribute['permission_write'] == '1') {
                $attributeGroupList[$attribute['attribute_group_id']]['readCount']++;
                $attributeGroupList[$attribute['attribute_group_id']]['writeCount']++;
            }
            array_push($attributeGroupList[$attribute['attribute_group_id']]['attributes'], $attribute);
        }


        foreach ($attributeGroupList as $key => $attributegroup) {
            if (!$attributeGroupList[$key]['columns']) {
                $attributeGroupList[$key]['columns'] = 1;
            }

            $attributeArray = array();
            $left           = true;

            if ($attributegroup['attributes'])
                foreach ($attributegroup['attributes'] as $attribute) {
                    if (!$attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]]) {
                        $attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]] = array();
                    }

                    switch ($attribute[Db_Attribute::COLUMN]) {
                        case 1:
                            if (is_null($attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][0])) {
                                $attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][0] = $attribute;
                            } else {
                                // double-pack
                                $attributeArray = $this->sortAttributeList($attributeArray, $attribute, 0);
                            }
                            break;
                        case 2:
                            $attributeGroupList[$key]['columns'] = 2;

                            if (is_null($attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][1])) {
                                $attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][1] = $attribute;
                            } else {
                                // double-pack
                                $attributeArray = $this->sortAttributeList($attributeArray, $attribute, 1);
                            }

                            break;
                        default:
                            if (is_null($attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][0])) {
                                $attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][0] = $attribute;
                            } else {
                                // double-pack
                                $attributeArray = $this->sortAttributeList($attributeArray, $attribute, 0);
                            }
                            break;
                    }

                }
            $attributeGroupList[$key]['attributes'] = $attributeArray;
            ksort($attributeGroupList[$key]['attributes']);
        }

        $attributeGroupList = $this->getAttributeGroupHierarchy($attributeDaoImpl, $attributeGroupList);


        foreach ($attributeGroupList as $index => $attributeContent) {
            if ($attributeContent['permission_read'] == '0') {
                unset($attributeGroupList[$index]);
            }
        }

        return $attributeGroupList;
    }

    public function getCiData($ciId)
    {
        $dao_ci = new Dao_Ci();
        return $dao_ci->getCi($ciId);
    }

    public function getCiForPointInTime($ciId, $history_id)
    {
        $dao_ci = new Dao_Ci();
        return $dao_ci->getCiForPointInTime($ciId, $history_id);
    }

    public function getDefaultAttribute($ciId)
    {
        $dao_ci = new Dao_Ci();
        return $dao_ci->getDefaultAttribute($ciId);
    }

    private function sortduplicateGroups($attributeGroupList)
    {

        $duplicate             = array();
        $attributeGroupListnew = array();
        $count                 = array();

        foreach ($attributeGroupList as &$attributeGroup) {

            $d = false;
            if (is_array($attributeGroup['attributes'])) {
                foreach ($attributeGroup['attributes'] as $key => $attribute) {

                    if (!$attribute[0]['initial']) {
                        $count[$attribute[0]['id']]++;

                        $int = $count[$attribute[0]['id']];

                        $duplicates_array = $duplicate[$int];

                        if (!$duplicates_array)
                            $duplicates_array = array();

                        array_push($duplicates_array, $attribute);

                        $duplicate[$int] = $duplicates_array;


                        $d = true;
                        unset($attributeGroup['attributes'][$key]);

                    }
                }
            }

            array_push($attributeGroupListnew, $attributeGroup);

            if (count($duplicate) > 0 && $d) {
                $i = 2;
                foreach ($duplicate as $dupl) {
                    array_push($attributeGroupListnew, array('id' => $attributeGroup['id'], 'name' => $attributeGroup['name'], 'description' => $attributeGroup['description'] . " $i", 'columns' => '1', 'attributes' => $dupl, 'duplicate' => '1'));
                    $d = false;
                    $i++;


                }
            }

        }

        return $attributeGroupListnew;

    }

    public function filterciListAttributes($ciList, $attributeList, $filter)
    {
        $count = 0;
        unset($filter['search']);

        foreach ($filter as $key => $f) {
            if ($f != null)
                $count++;
        }

        if ($count == 0)
            return $ciList;


        $ciList_filter = array();
        $remove        = array();

        // This is intended to prevent infinite loops in-case a ciType
        // doesn't have the filter attribute (session) anymore.
        $seenAttributes = array();
        foreach($attributeList as $attribute) {
            array_push($seenAttributes, $attribute[Db_Attribute::NAME]);
        }

        foreach($filter as $f => $fv) {
            if(!in_array($f, $seenAttributes)) {
                unset($filter[$f]);
            }
        }
        //

        foreach ($ciList as $list) {

            $found = false;

            foreach ($attributeList as $attribute) {

                if ($filter[$attribute[Db_Attribute::NAME]] != null && (in_array($attribute[Db_Attribute::NAME], $remove) || count($remove) == 0)) {
                    if (substr_count(mb_strtolower(html_entity_decode($list[$attribute[Db_Attribute::NAME]]), 'UTF-8'), mb_strtolower(html_entity_decode($filter[$attribute[Db_Attribute::NAME]]), 'UTF-8'))) {
                        $found = true;
                        array_push($remove, $attribute[Db_Attribute::NAME]);
                        break;
                    }
                }
            }

            if ($found)
                array_push($ciList_filter, $list);


        }


        unset($filter[$remove[0]]);


        if ($count > 1) {

            return self::filterciListAttributes($ciList_filter, $attributeList, $filter);

        } else {

            return $ciList_filter;
        }

    }


    public function filterciList($ciList, $attributeList, $filter)
    {
        $ciList_filter = array();

        if ($filter['search'] == null) {
            return $ciList;
        }

        foreach ($ciList as $list) {
            $found = false;

            foreach ($attributeList as $attribute) {
                if (substr_count(mb_strtolower(html_entity_decode($list[$attribute[Db_Attribute::NAME]]), 'UTF-8'), mb_strtolower(html_entity_decode($filter['search']), 'UTF-8'))) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                array_push($ciList_filter, $list);
            }
        }

        return $ciList_filter;
    }

    public static function convertColumnNameString($string, $type = "encode")
    {
        $replace = array(
            "!"  => "___EXCLAMATION_POINT___",
            '"'  => "___DOUBLE_QUOTE___",
            "§"  => "___SECTION___",
            '$'  => "___DOLLAR___",
            "%"  => "___PERCENT___",
            "&"  => "___AMPERSAND___",
            "/"  => "___SLASH___",
            "("  => "___OPENING_PARENTHESIS___",
            ")"  => "___CLOSING_PARENTHESIS___",
            "{"  => "___OPENING_BRACE___",
            "}"  => "___CLOSING_BRACE___",
            "["  => "___OPENING_BRACKET___",
            "]"  => "___CLOSING_BRACKET___",
            "="  => "___SAME___",
            "?"  => "___QUESTION___",
            "\\" => "___BACKSLASH___",
            "*"  => "___ASTERISK___",
            "+"  => "___PLUS___",
            "~"  => "___TILDE___",
            "'"  => "___SINGLE_QUOTE___",
            "#"  => "___HASH___",
            "-"  => "___MINUS___",
            "."  => "___PERIOD___",
            ":"  => "___COLON___",
            ","  => "___COMMA___",
            ";"  => "___SEMICOLON___",
            " "  => "___SPACE___",
        );

        if ($type == "encode") {
            $str = str_replace(array_keys($replace), array_values($replace), $string);
        } else {
            $str = str_replace(array_values($replace), array_keys($replace), $string);
        }

        return $str;
    }

    /**
     * Returns the formatted context information for a ci
     *
     * @param $ciId int of the ci from which the data is collected
     *
     * @return array array ready for json formatting
     */
    public function getContextInfoForCi($ciId)
    {
        $ciDaoImpl = new Dao_Ci();

        $projectDaoImpl   = new Dao_Project();
        $attributeDaoImpl = new Dao_Attribute();
        $ciTypeDaoImpl    = new Dao_CiType();
        $relService       = new Service_Relation_Get($this->translator, $this->logger, parent::getThemeId());

        $ci = $ciDaoImpl->getCi($ciId);

        $projects   = $projectDaoImpl->getProjectsByCiId($ciId);
        $attributes = $attributeDaoImpl->getAttributesByCiId($ciId);
        $ci_type    = $ciTypeDaoImpl->getCiType($ci['ci_type_id']);
        $rel        = $relService->getMinimalRelationInfo($ciId);

        $output              = array();
        $output['relations'] = $rel;
        $output['projects']  = new stdClass();
        foreach ($projects as $project) {
            $projectId                      = $project['id'];
            $output['projects']->$projectId = $project;
        }
        $output['ciTypeId']   = $ci_type['id'];
        $output['ciTypeName'] = $ci_type['name'];
        $output['attributes'] = new stdClass();
        foreach ($attributes as $attribute) {
            $attributeId                        = $attribute['id'];
            $ciAttributeId                      = $attribute['ciAttributeId'];
            $attributeObject                    = new stdClass();
            $attributeObject->$ciAttributeId    = $attribute;
            $output['attributes']->$attributeId = $attributeObject;
        }
        return $output;
    }


    /**
     * Checks if the given user has permission to access the given attribute of the given ci
     *
     * @param $ciId
     * @param $userId
     * @param $attributeId
     */
    private function checkAttributeRolePermission($ciId, $userId, $attributeId)
    {
        if (!is_null($ciId) && !is_null($userId) && !is_null($attributeId)) {
            $attributeDao = new Dao_Attribute();
            $ciDao        = new Dao_Ci();
            $ci           = $ciDao->getCi($ciId);
            $attributes   = $attributeDao->getAttributesForExportAll($ci[Db_Ci::CI_TYPE_ID], $userId);
            if (is_array($attributes)) {
                foreach ($attributes as $attribute) {
                    if ($attribute[Db_Attribute::ID] == $attributeId) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getCiAttributeById($ciAttributeId)
    {
        $dao_attribute = new Dao_Attribute();
        return $dao_attribute->getSingleCiAttributeById($ciAttributeId);
    }

    /**
     * @param $attributeList
     * @param $attributeDao Dao_Attribute
     *
     * @return array
     */
    public function getAttributeGroupForDetailView($attributeList)
    {
// get view Type list
        $attributeDao    = new Dao_Attribute();
        $attributeGroups = array();
        foreach ($attributeList as $attribute) {
            $attributeGroups[$attribute[Db_Attribute::ATTRIBUTE_GROUP_ID]] = $attribute[Db_Attribute::ATTRIBUTE_GROUP_ID];
        }

        $list = null;
        $list = implode(',', $attributeGroups);

        $attributeGroupList = $attributeDao->getAttributeGroupList($list);

        $i    = 0;
        $help = array();
        foreach ($attributeGroupList as $attributeGroupParent) {
            $i = 0;
            foreach ($attributeGroupList as $attributeGroupChild) {
                if ($attributeGroupChild['parent_attribute_group_id'] == $attributeGroupParent['id']) {
                    $oldOrderNumber                      = $attributeGroupChild['order_number'];
                    $attributeGroupChild['order_number'] = (intval($attributeGroupParent['order_number']) + intval($oldOrderNumber));
                    array_push($help, $attributeGroupChild, $i);
                }
                $i++;
            }
        }

        for ($i = 0; $i < count($help); $i += 2) {
            $attributeGroupList[$help[$i + 1]] = $help[$i];
        }

        for ($a = 0; $a < count($attributeGroupList); $a++) {
            for ($b = 0; $b < count($attributeGroupList) - 1; $b++) {
                if ($attributeGroupList[$b + 1]['order_number'] < $attributeGroupList[$b]['order_number']) {
                    $temp                       = $attributeGroupList[$b];
                    $attributeGroupList[$b]     = $attributeGroupList[$b + 1];
                    $attributeGroupList[$b + 1] = $temp;
                }
            }
        }


        // make it faster
        $newView = array();
        foreach ($attributeGroupList as $view) {
            $newView[$view[Db_AttributeGroup::ID]] = $view;
        }
        $attributeGroupList = $newView;
        unset($newView);

        foreach ($attributeList as $attribute) {


            if (!isset($attributeGroupList[$attribute[Db_Attribute::ATTRIBUTE_GROUP_ID]]['attributes'])) {
                $attributeGroupList[$attribute[Db_Attribute::ATTRIBUTE_GROUP_ID]]['attributes'] = array();
            }


            array_push($attributeGroupList[$attribute[Db_Attribute::ATTRIBUTE_GROUP_ID]]['attributes'], $attribute);


        }

        foreach ($attributeGroupList as $key => $attributegroup) {
            if (!isset($attributeGroupList[$key]['columns'])) {
                $attributeGroupList[$key]['columns'] = 1;
            }

            $attributeArray = array();
            $left           = true;

            if ($attributegroup['attributes']) {
                foreach ($attributegroup['attributes'] as $attribute) {
                    if (!isset($attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]])) {
                        $attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]] = array();
                    }

                    switch ($attribute[Db_Attribute::COLUMN]) {
                        case 1:
                            if (!isset($attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][0])) {
                                $attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][0] = $attribute;
                            } else {
                                // double-pack
                                $attributeArray = $this->sortAttributeList($attributeArray, $attribute, 0);
                            }
                            break;
                        case 2:
                            $attributeGroupList[$key]['columns'] = 2;

                            if (is_null($attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][1])) {
                                $attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][1] = $attribute;
                            } else {
                                // double-pack
                                $attributeArray = $this->sortAttributeList($attributeArray, $attribute, 1);
                            }

                            break;
                        default:
                            if (is_null($attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][0])) {
                                $attributeArray[$attribute[Db_Attribute::ORDER_NUMBER]][0] = $attribute;
                            } else {
                                // double-pack
                                $attributeArray = $this->sortAttributeList($attributeArray, $attribute, 0);
                            }
                            break;
                    }

                }
            }
            $attributeGroupList[$key]['attributes'] = $attributeArray;
            ksort($attributeGroupList[$key]['attributes']);
        }
        $attributeGroupList = $this->getAttributeGroupHierarchy($attributeDao, $attributeGroupList);
        return $attributeGroupList;
    }

    public function getAttributesForCi($ciId, $userId = null, $events = '0', $notinattribute_Type = "")
    {
        $dao_attributes = new Dao_Attribute();
        return $dao_attributes->getAttributesByCiId($ciId, $userId, $events, $notinattribute_Type);
    }

    public function getAttributeDataForPointInTime($ciId, $history_id)
    {
        $dao_attribute = new Dao_Attribute();
        return $dao_attribute->getAttributeDataForPointInTime($ciId, $history_id);
    }

    /**
     * Returns the information needed to fill the ci/detail.phtml for a ci at a given point in time.
     *
     * @param $ci_id      int id of the ci to get the detail information
     * @param $history_id id of history row that should be used used for determining point in time
     * @param $user_id    int id of the user, needed for permission checks
     *
     * @return array returns an array containing different information:
     * 'icon' => path to the icon
     * 'breadcrumbs' => breadcrumb information used in detail.phtml
     * 'breadcrumbDepth' => breadcrumb information used in detail.phtml
     * 'ciType' => array containing the ci type information
     * 'projectList' => array of projects
     * 'attributeList' => specially formatted array of attributes (for detail.phtml)
     * 'isAdmin' =>  bool if admin view is activated or not
     */
    public function getHistoricalCiDetail($ci_id, $history_id, $user_id)
    {
        $ci_type_dao = new Dao_CiType();
        $dao_project = new Dao_Project();
        $dao_history = new Dao_History();

        $fileupload_config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $use_default_path  = $fileupload_config->file->upload->path->default;
        $upload_path       = ($use_default_path) ? $fileupload_config->file->upload->path->folder : $fileupload_config->file->upload->path->custom;
        $icon_folder       = $fileupload_config->file->upload->icon->folder;
        $icon_full_path    = APPLICATION_URL . $upload_path . $icon_folder . '/';

        $view_config      = new Util_Config('view.ini', APPLICATION_ENV);
        $breadcrumb_depth = (int)$view_config->getValue('ci.detail.breadcrums.depth', 10);

        /* General Ci Data needed for detail view */

        $history = $dao_history->getHistoryById($history_id);
        $ci      = $this->getCiForPointInTime($ci_id, $history_id);

        if (!$ci) {
            return array('ciDidNotExist' => true);
        }

        $ci_type = $ci_type_dao->getRawCiType($ci[Db_Ci::CI_TYPE_ID]);

        $admin_view = false;
        if (Zend_Registry::get('adminMode') === true) {
            $admin_view = true;
        }


        $default_attribute = $this->getDefaultAttribute($ci_id);
        if (isset($default_attribute[Db_CiAttribute::VALUE_TEXT])) {
            $default_attribute = $default_attribute[Db_CiAttribute::VALUE_TEXT];
        } else {
            $default_attribute = $ci_id;
        }

        $breadcrumbs = $ci_type_dao->getBreadcrumbHierarchy($ci[Db_Ci::CI_TYPE_ID]);
        $breadcrumbs = array_reverse($breadcrumbs);

        $history_addition = sprintf($this->translator->translate("viewingHistoryBreadcrumb"), $history[Db_History::DATESTAMP]);

        $current_ci = array(
            'crumbType'   => 'ci',
            'description' => '<b>' . $default_attribute . ' ' . $history_addition . '</b>',
        );
        array_push($breadcrumbs, $current_ci);


        $icon = $ci_type_dao->getCiIcon($ci_id);

        if (!empty($icon)) {
            $icon = $icon_full_path . $icon;
        } elseif (!empty($ci_type[Db_CiType::ICON])) {
            $icon = $icon_full_path . $ci_type[Db_CiType::ICON];
        } else {
            $icon = APPLICATION_URL . 'images/ci.png';
        }

        /* Fetch attribute values */

        $attributes = $this->getAttributesForCi($ci_id, $user_id, 'all');

        $expired_attributes     = array();
        $attributes_with_id_key = array();
        foreach ($attributes as $attribute) {
            if ($attribute[Db_CiAttribute::HISTORY_ID] > $history_id) {
                array_push($expired_attributes, $attribute['ciAttributeId']);
                continue;
            }

            $key                          = intval($attribute['ciAttributeId']);
            $attributes_with_id_key[$key] = $attribute;
        }

        $attributes_at_point_in_time = $this->getAttributeDataForPointInTime($ci_id, $history_id);

        foreach ($attributes_at_point_in_time as $updated_attributes) {
            $attributes_with_id_key[$updated_attributes['ciAttributeId']] = $updated_attributes;
        }

        foreach ($attributes_with_id_key as $key => $attribute) {
            /** @var Util_AttributeType_Type_Abstract $class */
            $class = Util_AttributeType_Factory::get($attribute['attributeTypeName']);
            $class->setHistoryView(true);
            $attributes_with_id_key[$key] = $class->setAttributeValue($attribute, $ci_id, $upload_path);
        }

        $attribute_list = $this->getAttributeGroupForDetailView($attributes_with_id_key);

        /* Get projects for detail view */

        $projects            = $dao_project->getProjectsByCiId($ci_id);
        $expired_assignments = array();
        $current_projects    = array();
        foreach ($projects as $project) {
            if ($project['ci_project_history_id'] > $history_id) {
                $expired_assignments[$project[Db_Project::ID]] = $project;
                continue;
            }
            $key                    = intval($project[Db_Project::ID]);
            $current_projects[$key] = $project;
        }

        $projects_back_then = $dao_project->getProjectDataForPointInTime($ci_id, $history_id);

        foreach ($projects_back_then as $updated_projects) {
            $current_projects[$updated_projects[Db_Project::ID]] = $updated_projects;
        }
        return array(
            'icon'            => $icon,
            'breadcrumbs'     => $breadcrumbs,
            'breadcrumbDepth' => $breadcrumb_depth,
            'ciType'          => $ci_type,
            'projectList'     => $current_projects,
            'attributeList'   => $attribute_list,
            'isAdmin'         => $admin_view,
        );
    }


}





