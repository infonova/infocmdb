<?php

/**
 *
 *
 *
 */
class Service_History_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1001, $themeId);
    }


    /**
     * Builds a history list of a single ci:
     *
     *    1. items count per page
     *    2 retrieve ci information
     *  3. getCiHistoryForCi
     *  4. make $historyList
     *  5. add project changes to the $historyList
     *  6. add ciType changes to the $historyList
     *  7. add relation changes to the $historyList
     *  8. add attribute changes to the $historyList
     *    9. pagination
     *
     * @param        string  $historyId
     * @param        string  $ciId
     * @param        string  $page
     * @param        string  $fromDate
     * @param        string  $toDate
     * @param        string  $root
     * @param        string  $userId
     * @param        array   $filteredHistoryIds
     * @param        array   $selectedAttributeOptions
     * @param        boolean $relationChanged
     * @param        boolean $ciTypeChanged
     * @param        boolean $projectChanged
     * @param        boolean $options
     * @param        boolean $filterSet
     * @param        boolean $onlyDateFilter
     *
     * @return        array =>
     *                        'historyList' => $historyList,
     *                        'paginator' => $paginator
     *
     * @throws Exception_AttributeType_InvalidClassName
     * @throws Exception_History_NotFound
     *
     * @author        Martina Reiter
     * @since         August 2016
     */
    public function getHistoryList($historyId, $ciId, $page, $fromDate, $toDate, $root, $userId, $filteredHistoryIds, $selectedAttributeOptions, $relationChanged, $ciTypeChanged, $projectChanged, $options, $filterSet, $onlyDateFilter)
    {

        ### ITEMS COUNT PER PAGE ##
        $config            = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/cihistory.ini', APPLICATION_ENV);
        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;

        ### RETRIEVE CI ID BY HISTORY ID ###
        $historyDaoImpl = new Dao_History();
        if ($historyId && !$ciId) {
            $ciId = $historyDaoImpl->retrieveCiIdByHistoryId($historyId);
        }

        ### getCiHistoryForCi ###
        $re              = $historyDaoImpl->getCiHistoryForCi($ciId, $page, $itemsCountPerPage, $fromDate, $toDate, $filteredHistoryIds, $filterSet, $onlyDateFilter);
        $numberRows      = $re['cnt'];
        $filteredResults = $re['all'];
        $historyList     = $re['res'];

        if ($filteredHistoryIds) {
            $numberRows = count($historyList);
        }

        ### MAKE HISTORY LIST ###
        $historyIdList = "";
        foreach ($historyList as $key => $history) {
            $historyIdList                          .= $history[Db_History::ID] . ',';
            $historyList[$key]['citype']            = array();
            $historyList[$key]['citype_deleted']    = array();
            $historyList[$key]['citype_actual']     = array();
            $historyList[$key]['attributes']        = array();
            $historyList[$key]['projects']          = array();
            $historyList[$key]['projects_deleted']  = array();
            $historyList[$key]['relations']         = array();
            $historyList[$key]['relations_deleted'] = array();
        }
        $historyIdList .= '-1';

        ### GET PROJECT HISTORY ###
        if (!$filterSet || ($filterSet && $projectChanged) || $onlyDateFilter) {
            $projects = $historyDaoImpl->getCiProjectByHistoryList($historyIdList, $ciId);
            foreach ($projects as $project) {
                foreach ($historyList as $key => $history) {
                    if ($project[Db_History_CiProject::HISTORY_ID] == $history[Db_History::ID]) {
                        array_push($historyList[$key]['projects'], $project);
                    } else if ($project[Db_History_CiProject::HISTORY_ID_DELETE] == $history[Db_History::ID]) {
                        array_push($historyList[$key]['projects_deleted'], $project);
                    }
                }
            }
        }

        ### GET CITYPE HISTORY ###
        if (!$filterSet || ($filterSet && $ciTypeChanged) || $onlyDateFilter) {
            $citypes = $historyDaoImpl->getCiTypeByHistoryList($historyIdList);
            foreach ($citypes as $citype) {
                if ($citype["id"] == $ciId) {
                    foreach ($historyList as $key => $history) {
                        if (!$historyList[$key]['citype']['new'])
                            $historyList[$key]['citype']['new'] = array();

                        if (!$historyList[$key]['citype']['old'])
                            $historyList[$key]['citype']['old'] = array();

                        if ($citype['history_id'] == $history[Db_History::ID]) {
                            array_push($historyList[$key]['citype']['new'], $citype);
                        } else if ($citype['history_id_delete'] == $history[Db_History::ID]) {
                            array_push($historyList[$key]['citype']['old'], $citype);
                        }
                    }
                }
            }
        }

        ### GET RELATION HISTORY ###
        if (!$filterSet || ($filterSet && $relationChanged) || $onlyDateFilter) {
            $relations       = $historyDaoImpl->getCiRelationByHistoryList($historyIdList);
            $relationsDelete = $historyDaoImpl->getCiRelationByHistoryList($historyIdList, true);
            foreach ($relations as $relation) {
                foreach ($historyList as $key => $history) {
                    if ($relation[Db_CiRelation::HISTORY_ID] == $history[Db_History::ID]) {
                        array_push($historyList[$key]['relations'], $relation);
                    }
                }
            }
            foreach ($relationsDelete as $relation) {
                foreach ($historyList as $key => $history) {
                    if ($relation[Db_History_CiRelation::HISTORY_ID_DELETE] == $history[Db_History::ID]) {
                        array_push($historyList[$key]['relations_deleted'], $relation);
                    }
                }
            }
        }
        ### GET ATTRIBUTE HISTORY ###
        $config         = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $useDefaultPath = $config->file->upload->path->default;
        $attributes     = $historyDaoImpl->getCiAttributeByHistoryList($historyIdList, $ciId, $userId);
        $path           = ($useDefaultPath) ? $config->file->upload->path->folder : $config->file->upload->path->custom;
        $tempAttList    = array();

        ### RESTRICT ATTRIBUTES TO SELECTED ATTRIBUTE OPTIONS ###
        if ($selectedAttributeOptions && $selectedAttributeOptions[0] != 'default') {
            $tempAttributes = array();
            foreach ($selectedAttributeOptions as $selectedAttributeOptionIndex => $selectedAttributeOptionId) {
                foreach ($attributes as $attributeIndex => $attributeData) {
                    if ($attributeData['attribute_id'] == $selectedAttributeOptionId) {
                        $tempAttributes[$attributeIndex] = $attributes[$attributeIndex];
                    }
                }
            }
            $attributes = $tempAttributes;
        }

        ### RESOLVE ATTRIBUTES ###
        foreach ($historyList as $key => $history) {

            foreach ($attributes as $attribute) {
                $attribute['ciAttributeId'] = $attribute[Db_CiAttribute::ID];

                if ($attribute[Db_History_CiAttribute::HISTORY_ID] != $history[Db_History::ID] &&
                    $attribute[Db_History_CiAttribute::HISTORY_ID_DELETE] != $history[Db_History::ID]) {
                    continue;
                }

                $class                          = Util_AttributeType_Factory::get($attribute['attributeType']);
                $attributeNew                   = $attribute;
                $attributeNew[Db_Attribute::ID] = $attribute['attributeId'];
                $attributeNew                   = $class->setAttributeValue($attribute, $attribute[Db_CiAttribute::CI_ID], $path);

                if ($attributeNew['attributeType'] == 'selectQuery') {
                    $a                   = $attribute;
                    $a[Db_Attribute::ID] = $a[Db_CiAttribute::ATTRIBUTE_ID];
                    $attributeNew        = $class->setAttributeValue($a, $attribute[Db_CiAttribute::CI_ID], $path);
                }

                $attributeNew[Db_CiAttribute::ID] = $attribute[Db_CiAttribute::ID];
                $val                              = $attributeNew[Db_CiAttribute::VALUE_TEXT] . $attributeNew[Db_CiAttribute::VALUE_DEFAULT] . $attributeNew[Db_CiAttribute::VALUE_DATE];


                if ($tempAttList[$key][$attribute[Db_CiAttribute::ID]]) {
                    $attributeNew = $tempAttList[$key][$attribute[Db_CiAttribute::ID]];
                } else {
                    $attributeNew['key'] = $key;
                }

                if ($attribute[Db_History_CiAttribute::HISTORY_ID] == $history[Db_History::ID]) {
                    $attributeNew['value'] = $val;
                } else if ($attribute[Db_History_CiAttribute::HISTORY_ID_DELETE] == $history[Db_History::ID]) {
                    $attributeNew['value_old'] = $val;
                }
                $tempAttList[$key][$attribute[Db_CiAttribute::ID]] = $attributeNew;
            }
        }

        ### ADD RESOLVED ATTRIBUTES TO HISTORY LIST ###
        foreach ($tempAttList as $id => $v) {
            foreach ($v as $key => $att) {
                array_push($historyList[$id]['attributes'], $att);
            }
        }

        ### PAGINATION ###
        if ($filterSet && !$onlyDateFilter) {
            $numberRows = count($filteredResults);
        }
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($numberRows));
        $paginator->setItemCountPerPage($itemsCountPerPage);
        $paginator->setCurrentPageNumber($page);
        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial($scrollingControl);

        return array(
            'historyList' => $historyList,
            'paginator'   => $paginator,
        );

    }

    /**
     *
     *    Gets all attributes of a CI
     *
     *    1.    getAttributesFromHistoryCiAttributeAndCiAttribute($ciId,$userId)
     *
     *    2.  make $additionalAttributes array =>  'count' (counts all attribute occurrences in the getCiAttributesByCiIdfromHistoryCiAttribute )
     *                                             'description' (of attribute from getCiAttributesByCiIdfromHistoryCiAttribute)
     *                                             'hasHistory' is set to 1 for all entries
     *
     *    3.    getCiAttributesByCiIdfromCiAttribute($ciId) and store it in array ($allAttributes)
     *
     *
     *  4.  add attributes without history to $attributesWithHistory => count = 0,
     *                                                                    description = '..',
     *                                                                    hasHistory = 0
     *
     *    5.  get list of ciTypes
     *
     *  6.    compare $ciTypeAttributes with $attributesWithHistory
     *        if attribute from  $ciTypeAttributes exists in $attributesWithHistory
     *            - add it to $standardAttributes
     *            - remove it from $attributesWithHistory
     *
     *
     * @param        string $ciId
     * @param        string $userId
     *
     * @return    array        =>    'standardAttributes'
     * 'additionalAttributes'
     *
     * @author        Martina Reiter
     * @since         August 2016
     */
    public function getAttributes($ciId, $userId)
    {

        ### get all attributes ###
        $historyDaoImpl                                 = new Dao_History();
        $attributesFromHistoryCiAttributeAndCiAttribute = $historyDaoImpl->getAttributesFromHistoryCiAttributeAndCiAttribute($ciId, $userId);
        $additionalAttributes                           = array();
        foreach ($attributesFromHistoryCiAttributeAndCiAttribute as $index => $attribute) {
            $additionalAttributes[$attribute['attribute_id']]['description'] = $attribute['description'];
            $additionalAttributes[$attribute['attribute_id']]['hasHistory']  = 1;
            $additionalAttributes[$attribute['attribute_id']]['status']      = $attribute['is_active'];
        }

        ### get list of ciTypes ###
        $ciDaoImpl     = new Dao_Ci();
        $ciTypeDaoImpl = new Dao_CiType();
        $ciRow         = $ciDaoImpl->getCi($ciId);
        $ciTypes       = array();
        if ($ciRow) {
            $ciType      = $ciTypeDaoImpl->getCiType($ciRow['ci_type_id']);
            $typeIdArray = $ciTypeDaoImpl->retrieveCiTypeHierarchy($ciType['id']);
            $ciTypes     = array_merge($ciTypes, $typeIdArray);
        } else {
            $historyCiTypes = $ciTypeDaoImpl->getCiTypeHistoryByCiId($ciId);
            foreach ($historyCiTypes as $historyCiType) {
                $typeIdArray = $ciTypeDaoImpl->retrieveCiTypeHierarchy($historyCiType['id']);
                $ciTypes     = array_merge($ciTypes, $typeIdArray);
            }
        }

        $typeIdList = implode(",", $ciTypes);

        $ciTypeAttributes = $historyDaoImpl->getAttributeDetailsAndCheckPermission($typeIdList, $userId);

        ### compare $standardAttributes with $additionalAttributes and get rid of dublicates ###
        $standardAttributes = array();
        foreach ($ciTypeAttributes as $index => $content) {
            foreach ($content as $key => $value) {
                $id = $content['id'];
                if (!array_key_exists($id, $standardAttributes)) {
                    if (array_key_exists($id, $additionalAttributes)) {
                        $standardAttributes[$id] = $additionalAttributes[$id];
                        unset($additionalAttributes[$id]);
                    } else {
                        $standardAttributes[$id]['description'] = $content['description'];
                        $standardAttributes[$id]['hasHistory']  = 0;
                        $standardAttributes[$id]['status']      = $content['is_active'];
                    }
                }
            }
        }

        return array(
            'standardAttributes'   => $standardAttributes,
            'additionalAttributes' => $additionalAttributes,
        );

    }

    public function getHistoryListForIndex($page = null, $orderBy = null, $direction = null, $filter = null, $userId = null)
    {
        try {
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/history.ini', APPLICATION_ENV);

            $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
            $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['history'];

            $scrollingStyle   = $config->pagination->scrollingStyle;
            $scrollingControl = $config->pagination->scrollingControl;

            if (is_null($page)) {
                $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
                $page = 1;
            }

            if ($page) {
                $limit_from = $itemsCountPerPage * ($page - 1);
            } else {
                $limit_from = 0;
            }

            $historyDaoImpl = new Dao_History();
            $ciDaoImpl      = new Dao_Ci();

            $history = $historyDaoImpl->getCiHistoryForPagination($page, $orderBy, $direction, $filter, $itemsCountPerPage, $limit_from, $userId);

            $history_ci = array();

            foreach ($history as $h) {

                try {

                    $h['ci_id'] = $historyDaoImpl->retrieveCiIdByHistoryId($h[Db_History::ID]);

                    $h['citype'] = $ciDaoImpl->getCiTypeDescriptionForCi($h['ci_id']);
                    if (!isset($h['citype'])) {
                        $h['citype'] = $ciDaoImpl->getCiTypeDescriptionForHistoryCi($h['ci_id']);
                    }
                    $h['default_attribute'] = $ciDaoImpl->getDefaultAttribute($h['ci_id']);

                    if (!is_array($h['default_attribute'])) {
                        $h['default_attribute'] = $ciDaoImpl->getDefaultAttributeHistory($h['ci_id']);
                    }
                    array_push($history_ci, $h);
                } catch (Exception $e) {
                    $this->logger->log($h['id']);
                }
            }

            $history = $history_ci;

            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null(count($history)));
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage($itemsCountPerPage);

            Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
            Zend_View_Helper_PaginationControl::setDefaultViewPartial($scrollingControl);

            return array(
                'paginator'    => $paginator,
                'history_list' => $history,
            );
        } catch (Exception $e) {
            throw new Exception_History_Unknown($e);
        }
    }

    /**
     * @param string $filter
     */
    public function getFilterForm($filter = null)
    {
        $form = new Form_Filter($this->translator);

        if ($filter) {
            $form->populate(array('search' => $filter));
        }
        return $form;
    }

    public function getHistoryById($id)
    {
        $daoHistory = new Dao_History();
        return $daoHistory->getHistoryById($id);
    }

}