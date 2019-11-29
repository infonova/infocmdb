<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class HistoryController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/history_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/history_en.csv', 'en');
            parent::addUserTranslation('history');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    /**
     * show a list of all history entries
     */
    public function itemsperpageAction()
    {
        $itemCountPerPageSession                              = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['history'] = $this->_getParam('rowCount');
        $this->_redirect('history/index');
        exit;
    }

    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);

        $this->setupItemsCountPerPage('history');

        $page      = $this->_getParam('page');
        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $filter = null;
        if ($this->_hasParam('search')) {
            if (!$this->_getParam('search')) {
                $filterString = '';
            } else {
                $filterString = '/filter/' . urlencode($this->_getParam('search')) . '/';
            }
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'history/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        // action body
        try {
            $historyServiceGet = new Service_History_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $history_result    = $historyServiceGet->getHistoryListForIndex($page, $orderBy, $direction, $filter, (parent::getUserInformation()->getRoot() ? null : parent::getUserInformation()->getId()));

            $paginator    = $history_result['paginator'];
            $history_list = $history_result['history_list'];

        } catch (Exception_History $e) {
            $notification['error'] = $this->translator->translate('historyShowFailed');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect('index/index/');
        }

        $this->view->searchForm = $historyServiceGet->getFilterForm($filter);
        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $paginator;
        $this->view->history    = $history_list;


    }


    /**
     *    displays history for a single CI
     *
     *  Filters the history entries:
     *
     *    Filters a date range (AND)
     *
     *  Filters the following kinds of changes: (OR)
     *        - Relations
     *        - Projects
     *      - CiTypes
     *
     *  Attributefilter: (OR)
     *        - ciType Attributes
     *        - additional Attributes
     *
     * @param    string  $ciId
     * @param    string  $historyId
     * @param    string  $page
     *
     *    FILTER PARAMS
     * @param    string  $clicked         Filtern if filterData is not from session
     * @param    string  $relationChanged 1 if set else null
     * @param    string  $projectChanged  1 if set else null
     * @param    string  $ciTypeChanged   1 if set else null
     * @param    array   $attributeOption
     * @param    string  $fromDate
     * @param    string  $to
     *
     * @param    boolean $filterSet       true if one of the filter params is set
     * @param    string  $resetFilter     0 if reset was clicked
     *
     * @author        Martina Reiter
     * @since         August 2016
     * @throws    Exception_InvalidParameter        if there is no ciId
     */
    public function ciAction()
    {

        ### REQUIRED PARAM ###
        $ciId = $this->_getParam('ciid');
        $page = ($this->_getParam('page') == "") ? 1 : $this->_getParam('page');

        if (!$ciId) {
            throw new Exception_InvalidParameter();
        }
        if (!$page) {
            throw new Exception_InvalidParameter();
        }

        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $breadCrumbs     = $ciServiceGet->getCiBreadcrumbs($ciId);
        $this->elementId = $this->view->ciBreadcrumb($breadCrumbs, 10, 'text', true);

        ### REDIRECT POST ###
        if ($this->_request->isPost()) {
            $params = $this->_request->getPost();

            foreach ($params as $k => $v) {
                if ($k == 'attributeOptions') {
                    $selectedAttributeOptions[] = $v;
                }
            }
            $url = 'history/ci/ciid/' . $ciId . '/page/' . $page;
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if ($v == '') continue;
                        $url = $url . '/' . 'attributeOptions/' . $v;
                    }
                } else {
                    if ($value == '') continue;
                    $url = $url . '/' . $key . '/' . $value;
                }
            }
            $this->_redirect($url);
        }

        ### REDIRECT RESET ###
        $resetFilter = $this->_getParam('resetFilter');
        if ($resetFilter == '0') {#unset filter data
            $this->_redirect(APPLICATION_URL . 'history/ci/ciid/' . $ciId);
            exit;
        }

        ### OPTIONAL PARAMS ###
        $historyId = $this->_getParam('historyId');

        $filteredHistoryIds = null;
        if(strlen($historyId) > 0){
            $filteredHistoryIds = explode(',', $historyId);
        }

        ### FILTER PARAMS ###
        $relationChanged = $this->_getParam('relationChange');
        $projectChanged  = $this->_getParam('projectChanged');
        $ciTypeChanged   = $this->_getParam('ciTypeChanged');
        $fromDate        = ($this->_getParam('fromDate') == "") ? null : $this->_getParam('fromDate');
        $toDate          = ($this->_getParam('toDate') == "") ? null : $this->_getParam('toDate');

        ### SELECTED ATTRIBUTE OPTIONS###
        $options                  = ($this->_getParam('attributeOptions') == "default") ? null : $this->_getParam('attributeOptions');
        $selectedAttributeOptions = $options;
        if ($selectedAttributeOptions == null) {
            $selectedAttributeOptions = array();
        } elseif (is_string($selectedAttributeOptions)) {
            $selectedAttributeOptions = array('0' => $selectedAttributeOptions);
        }
        $selectedAttributeOptions[] = 'default';

        ### FILTER SET ###
        $filterSet = (isset($relationChanged) || isset($projectChanged) || isset($ciTypeChanged) || isset($options) || isset($toDate) || isset($fromDate)) ? true : null;

        ### DATE FILTER ###
        $onlyDateFilter = false;
        $resetToDate    = false;
        $resetFromDate  = false;
        if ($fromDate || $toDate) {
            if (!$fromDate) {
                $resetFromDate = true;
                $time          = strtotime("-10 year", time());
                $fromDate      = date("Y-m-d", $time);
            }
            if (!$toDate) {
                $resetToDate = true;
                $today       = date('Y-m-d');
                $toDate      = $today;
            }
            if ($toDate && $fromDate && is_null($options) && is_null($ciTypeChanged) && is_null($relationChanged) && is_null($projectChanged)) {
                $onlyDateFilter = true;
            }
        }

        ### HISTORY SERVICE ###
        $historyServiceGet = new Service_History_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        ### GET HISTORY LIST ###
        $getHistoryListResult = $historyServiceGet->getHistoryList($historyId, $ciId, $page, $fromDate, $toDate, parent::getUserInformation()->getRoot(), parent::getUserInformation()->getId(), $filteredHistoryIds, $selectedAttributeOptions, $relationChanged, $ciTypeChanged, $projectChanged, $options, $filterSet, $onlyDateFilter);
        $historyList          = $getHistoryListResult['historyList'];

        ### getAttributes from History Service ###
        $attributes           = $historyServiceGet->getAttributes($ciId, parent::getUserInformation()->getId());
        $standardAttributes   = $attributes['standardAttributes'];
        $additionalAttributes = $attributes['additionalAttributes'];

        ### Count Attributes with history Entry ###
        $attributeCounter = 0;
        foreach ($standardAttributes as $id => $content) {
            if ($content['hasHistory'] > 0) {
                $attributeCounter++;
            }
        }
        foreach ($additionalAttributes as $id => $content) {
            if ($content['hasHistory'] > 0) {
                $attributeCounter++;
            }
        }

        ### filter $historyList ###
        if ($filterSet && !$onlyDateFilter) {

            $filteredHistoryList = array();

            foreach ($historyList as $historyListIndex => $historyListRow) {

                ### Relation, Project and CiType Filter ###
                if (
                    ($ciTypeChanged == 1 && !empty($historyListRow['citype']['new'])) ||
                    ($ciTypeChanged == 1 && !empty($historyListRow['citype_deleted'])) ||
                    ($ciTypeChanged == 1 && !empty($historyListRow['citype_actual'])) ||
                    ($relationChanged == 1 && !empty($historyListRow['relations'])) ||
                    ($relationChanged == 1 && !empty($historyListRow['relations_deleted'])) ||
                    ($projectChanged == 1 && !empty($historyListRow['projects'])) ||
                    ($projectChanged == 1 && !empty($historyListRow['projects_deleted']))
                ) {
                    $filteredHistoryList[$historyListIndex] = $historyListRow;
                }

                ### Attribute Filter ###
                if ($selectedAttributeOptions != null && $selectedAttributeOptions != 'default') {
                    foreach ($selectedAttributeOptions as $selectedAttributeOptionIndex => $optionId) {
                        foreach ($historyListRow['attributes'] as $historyListRowIndex => $data) {
                            if ($historyListRow['attributes'][$historyListRowIndex]['attribute_id'] == $selectedAttributeOptions[$selectedAttributeOptionIndex]) {
                                $filteredHistoryList[$historyListIndex] = $historyListRow;
                            }
                        }
                    }
                }
            }

            if ($filteredHistoryList == null) {
                $historyList    = null;
                $totalItemCount = 0;

            } else {
                $filteredHistoryIds = "";
                $count              = 1;
                foreach ($filteredHistoryList as $index => $content) {
                    if ($count == (count($filteredHistoryList))) {
                        $filteredHistoryIds = $filteredHistoryIds . '"' . $content['history'] . '"';
                    } else {
                        $filteredHistoryIds = $filteredHistoryIds . '"' . $content['history'] . '",';
                    }
                    $count++;
                }

                if ($filteredHistoryIds == "") {
                    $filteredHistoryIds = null;
                };

                $historyList = $filteredHistoryList;
            }
        }

        if ($resetFromDate) {
            $fromDate = null;
        }
        if ($resetToDate) {
            $toDate = null;
        }

        ### Pagination ###
        if ($filterSet && !$onlyDateFilter) {
            $config            = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/cihistory.ini', APPLICATION_ENV);
            $itemsCountPerPage = $config->pagination->itemsCountPerPage;
            $scrollingStyle    = $config->pagination->scrollingStyle;
            $scrollingControl  = $config->pagination->scrollingControl;
            $totalItemCount    = count($historyList);
            $from              = ($page == 1) ? 0 : ($page - 1) * $itemsCountPerPage;
            $to                = $from + $itemsCountPerPage;
            $historyList       = array_slice($historyList, $from, $to);
            $paginator         = new Zend_Paginator(new Zend_Paginator_Adapter_Null($totalItemCount));
            $paginator->getTotalItemCount();
            $paginator->setItemCountPerPage($itemsCountPerPage);
            $paginator->setCurrentPageNumber($page);
            Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
            Zend_View_Helper_PaginationControl::setDefaultViewPartial($scrollingControl);
        } else {
            $paginator      = $getHistoryListResult['paginator'];
            $totalItemCount = $paginator->getTotalItemCount();
        }

        ### View
        $this->view->paginator                = $paginator;
        $this->view->page                     = $page;
        $this->view->totalItemCount           = $totalItemCount;
        $this->view->historyList              = $historyList;
        $this->view->ciId                     = $ciId;
        $this->view->filterSet                = $filterSet;
        $this->view->selectedAttributeOptions = $selectedAttributeOptions;
        $this->view->optionCount              = $attributeCounter;
        $this->view->relationChanged          = $relationChanged;
        $this->view->projectChanged           = $projectChanged;
        $this->view->ciTypeChanged            = $ciTypeChanged;
        $this->view->fromDate                 = $fromDate;
        $this->view->toDate                   = $toDate;
        $this->view->additionalAttributes     = $additionalAttributes;
        $this->view->standardAttributes       = $standardAttributes;

    }

    public function cidetailAction()
    {
        $ciId = $this->_getParam('ciid');

        /**
         * $restoreId = $this->_getParam('restoreId');
         * if (!empty($restoreId)){
         * $restoreCiRelationId = $this->_getParam('restoreCiRelationId');
         * $this->view->restoreId = $restoreId;
         * $this->view->restoreCiRelationId = $restoreCiRelationId;
         * }
         */


        try {
            $historyId = $this->_getParam('historyId');
            $currentCi = $this->detail($ciId);
            $historyCi = $this->detail($ciId, $historyId);
        } catch (Exception $e) {
            echo $e;
            exit;
        }
        $this->view->currentCi = $currentCi;
        $this->view->historyCi = $historyCi;
        $this->view->ciId      = $ciId;
        $this->view->historyId = $historyId;
    }


    /**
     * this is a workaround to be able to use redirects with additional parameter
     */
    private function detail($ciId, $historyId = null)
    {
        $this->logger->log('ciId was ' . $ciId, Zend_Log::DEBUG);

        if (is_null($ciId)) {
            throw new Exception_InvalidParameter();
        }

        // select ci type
        $historyDaoImpl = new Dao_History();
        $historyDate    = null;

        if ($historyId) {
            $history     = $historyDaoImpl->getHistoryById($historyId);
            $historyDate = $history[Db_History::DATESTAMP];
        }

        $ciType = $historyDaoImpl->getCiTypeByCiId($ciId, $historyDate);

        // select zugewiesene Projekte
        $projectList = $historyDaoImpl->getProjectsByCiId($ciId, $historyDate);

        // select zugewiesene Relations
        $relationList = $historyDaoImpl->getRelationssByCiId($ciId, $historyDate);

        // alle attribute selektieren, in gruppen nach ci typ
        $attributeDao     = new Dao_Attribute();
        $oldAttributeList = $attributeDao->getAttributesByCiId($ciId, parent::getUserInformation()->getId(), $historyDate);

        $attributeList = array();
        foreach ($oldAttributeList as $attribute) {
            $class = Util_AttributeType_Factory::get($attribute['attributeTypeName']);

            $attributeNew = $attribute;
            $attributeNew = $class->setAttributeValue($attribute, $ciId, null);

            array_push($attributeList, $attributeNew);
        }


        // TODO: may be deleted?
        if (!$attributeList || count($attributeList) <= 0) {
            if ($historyId) {
                $notification = array('error' => $this->translator->translate('historyViewNotAllowed'));
                $this->_helper->FlashMessenger($notification);
                $this->_redirect(APPLICATION_URL . 'history/ci/ciid/' . $ciId);
            }
            return array();
        }

        // get view Type list
        $attributeGroups = array();
        foreach ($attributeList as $attribute) {
            $attributeGroups[$attribute[Db_Attribute::ATTRIBUTE_GROUP_ID]] = $attribute[Db_Attribute::ATTRIBUTE_GROUP_ID];
        }

        $list = null;
        foreach ($attributeGroups as $type) {
            if (!$list) {
                $list = '' . $type;
            } else {
                $list .= ',' . $type;
            }
        }

        $attributeGroupList = $attributeDao->getAttributeGroupList($list);

        // make it faster
        $newView = array();
        foreach ($attributeGroupList as $view) {
            $newView[$view[Db_AttributeGroup::ID]] = $view;
        }
        $attributeGroupList = $newView;

        foreach ($attributeList as $attribute) {
            if (!$attributeGroupList[$attribute['attribute_group_id']]['attributes']) {
                $attributeGroupList[$attribute['attribute_group_id']]['attributes'] = array();
            }
            array_push($attributeGroupList[$attribute['attribute_group_id']]['attributes'], $attribute);
        }

        $attributeGroupList = $this->getAttributeGroupHierarchy($attributeDao, $attributeGroupList);

        $returnArray                  = array();
        $returnArray['ciTypeDto']     = $ciTypeDto;
        $returnArray['ciType']        = $ciType;
        $returnArray['projectList']   = $projectList;
        $returnArray['relationList']  = $relationList;
        $returnArray['attributeList'] = $attributeGroupList;
        if ($historyId) {
            $returnArray['historyDate'] = $historyDate;
        }
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
            if (!$array['children']) {
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


    private function getAttributeGroupHierarchy($attributeDaoImpl, $attributeGroupList)
    {
        $returnArray = array();
        //print_r($attributeGroupList); exit;

        foreach ($attributeGroupList as $key => $vi) {
            $vt = $attributeDaoImpl->getAttributeGroup($vi['id']);

            $id                                    = $vt[Db_AttributeGroup::ID];
            $array                                 = array();
            $array[Db_AttributeGroup::ID]          = $vt[Db_AttributeGroup::ID];
            $array[Db_AttributeGroup::NAME]        = $vt[Db_AttributeGroup::NAME];
            $array[Db_AttributeGroup::DESCRIPTION] = $vt[Db_AttributeGroup::DESCRIPTION];
            $array['attributes']                   = $vi['attributes'];

            if ($vt[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID]) {

                // search in return array for matching id

                // else create new hierarchy

                // TODO: handle parents
                $parentHierarchy = $this->handleParent($attributeDaoImpl, $vt[Db_AttributeGroup::PARENT_ATTRIBUTE_GROUP_ID], $array);
                $id              = $parentHierarchy[Db_AttributeGroup::ID];

                $array = $parentHierarchy;
            }


            if (array_key_exists($id, $returnArray)) {
                // we have a conflict

                if ($array['children']) {
                    if ($returnArray[$id]['children'])
                        $array['children'] = array_merge_recursive($array['children'], $returnArray[$id]['children']);

                    if ($returnArray[$id]['attributes']) {

                        if (!$array['attributes']) {
                            $array['attributes'] = array();
                        }
                        $array['attributes'] = array_merge_recursive($array['attributes'], $returnArray[$id]['attributes']);
                    }

                    $newChild = array();
                    foreach ($array['children'] as $val) {
                        $newChild[$val['id']] = $val;
                    }
                    $array['children'] = $newChild;
                    $returnArray[$id]  = $array;
                } else {
                    $returnArray[$id] = array_merge($array, $returnArray[$id]);
                }

            } else {
                $returnArray[$id] = $array;
            }
        }
        return $returnArray;
    }


    // TODO: not yet implemented
    public function restoreAction()
    {
        $ciId      = $this->_getParam('ciid');
        $historyId = $this->_getParam('historyId');


        $notification = array();
        try {
            $serviceHistory = new Service_History_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $serviceHistory->restoreHistoryEntry($historyId, parent::getUserInformation()->getId());
            $notification['success'] = $this->translator->translate('ciRestoreSuccess');
        } catch (Exception_History_UpdateSingleAttributeFailed $e) {
            $this->logger->log('Single Attribute Update with ID "' . $ciId . '" failed!', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('singleAttributeUpdateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect(APPLICATION_URL . 'history/ci/ciid/' . $ciId . '/');


        // THE END


        /*
         $uniqueId = $this->_getParam('uniqueId');

         if(!$historyId) {
            throw new Exception_InvalidParameter();
            }


            // call restore_ci($ciId, null, null);

            $historyDaoImpl = new Dao_History();
            $ciDaoImpl = new Dao_Ci();

            if($uniqueId) {
            // TODO: use call delete_/restore_ci_attribute()

            // first add a new history entry
            $newHistoryId = $historyDaoImpl->createHistory($ciId, parent::getUserInformation()->getId(), $note = "restore SINGLE");
            $historyDaoImpl->historizeSingleCiAttribute($newHistoryId, $ciId, $uniqueId);
            // then update the entry
            $historyDaoImpl->restoreSingleCiAttributes($historyId, $ciId, $uniqueId);
            }
            else {

            // TODO: select restore time
            $datestamp = null;

            // FIXME: restore relations and projectmapping
            $historizationUtil = new Util_Historization();
            $historizationUtil->restoreCi($ciId, parent::getUserInformation()->getId(), $datestamp);


            }

            $this->_redirect('history/ci/ciid/'.$ciId.'/');
            }

            // TODO: not yet implemented
            public function restorerelationAction() {
            $ciId1 = $this->_getParam('ci_id_1');
            $ciId2 = $this->_getParam('ci_id_2');
            $restoreCiRelationId = $this->_getParam('restoreCiRelationId');
            $userId = parent::getUserInformation()->getId();

            //check if relation is already set!
            //$ciRelationDaoImpl = new Dao_CiRelation();
            //$uniqueRelation = $ciRelationDaoImpl->checkCiRelationUnique($ciId1,$ciId2);

            if(!$ciId1 || !$ciId2 || !$restoreCiRelationId || !$userId) {
            throw new Exception_InvalidParameter();
            }
            try {
            $serviceHistory = new Service_History_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $relationUpdate = $serviceHistory->restoreSingleRelation($userId, $ciId1, $ciId2, $restoreCiRelationId);
            } catch(Exception_History_UpdateSingleAttributeFailed $e) {
            $this->logger->log('Single Relation Update for Relation ID "'.$restoreCiRelationId.'" failed!', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('singleRelationUpdateFailed');
            }
            if($notification['error']) {
            $this->_helper->FlashMessenger($notification);
            $this->_redirect(APPLICATION_URL.'history/ci/ciid/'.$ciId1.'/');
            }else{
            $notification['success'] = $this->translator->translate('singleRelationUpdateSuccess');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect(APPLICATION_URL.'history/ci/ciid/'.$ciId1.'/');
            }

            */

    }



    // TODO: not yet implemented
    /*
     * PHP7 Problems
     * This causes warnings to be thrown, so its commented out as it's whether used nor properly implemented
	public function restoreprojectAction() {
		$ciId = $this->_getParam('ciid');
		$projectId = $this->_getParam('projectId');

		if(!$ciId || !$projectId) {
			throw new Exception_InvalidParameter();
		}
		$historyDaoImpl = new Dao_History();

		$newHistoryId = $historyDaoImpl->createHistory($ciId, parent::getUserInformation()->getId(), $note = "restore SINGLE Project");
		$historyDaoImpl->rehistorizeSingleProjectAttribute($newHistoryId, $projectId);
		// then update the entry

		$historyDaoImpl->restoreSingleProject($relationId);

		$this->_redirect('history/ci/ciid/'.$ciId.'/');
	}
         */


    // TODO: not yet implemented
    public function restoreattributeAction()
    {
        $ciId                 = $this->_getParam('ciid');
        $userId               = $this->_getParam('user_id');
        $currentCiAttributeId = $this->_getParam('currentCiAttributeId');
        $restoreCiAttributeId = $this->_getParam('restoreCiAttributeId');

        if (!$ciId || !$userId || !$currentCiAttributeId || !$restoreCiAttributeId) {
            throw new Exception_InvalidParameter();
        }
        try {
            $serviceHistory  = new Service_History_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $attributeUpdate = $serviceHistory->restoreSingleAttribute($userId, $ciId, $currentCiAttributeId, $restoreCiAttributeId);
        } catch (Exception_History_UpdateSingleAttributeFailed $e) {
            $this->logger->log('Single Attribute Update with ID "' . $ciId . '" failed!', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('singleAttributeUpdateFailed');
        }
        if ($notification['error']) {
            $this->_helper->FlashMessenger($notification);
            $this->_redirect(APPLICATION_URL . 'history/ci/ciid/' . $ciId . '/');
        } else {
            $notification['success'] = $this->translator->translate('singleAttributeUpdateSuccess');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect(APPLICATION_URL . 'history/ci/ciid/' . $ciId . '/');
        }
    }

}
