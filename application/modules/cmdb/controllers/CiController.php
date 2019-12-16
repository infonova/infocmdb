<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class CiController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/ci_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/ci_en.csv', 'en');
            parent::addUserTranslation('ci');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function itemsperpageAction()
    {
        $typeId                                                         = $this->_getParam('typeId');
        $rowCount                                                       = $this->_getParam('rowCount');
        $itemCountPerPageSession                                        = $this->setupItemsCountPerPage('ci', $typeId);
        $itemCountPerPageSession->itemCountPerPage['ci'][$typeId]       = $rowCount;
        $itemCountPerPageSession->itemCountPerPage['options']['typeId'] = $typeId;
        $this->_redirect('ci/index');
        exit;
    }

    public function indexAction()
    {

        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);

        $this->view->headLink()->appendStylesheet(APPLICATION_URL . 'css/font-awesome.min.css?');

        // params
        $typeId           = $this->_getParam('typeid');
        $page             = $this->_getParam('page');
        $orderBy          = $this->_getParam('orderBy');
        $sessionID        = $this->_getParam('sessionID');
        $ciRelationTypeId = $this->_getParam('ciRelationTypeId');
        $sourceCiid       = $this->_getParam('sourceCiid');

        // config
        $viewConfig      = new Util_Config('view.ini', APPLICATION_ENV);
        $breadcrumbDepth = $viewConfig->getValue('ci.list.breadcrums.depth', 10);

        // check required parameters
        if ($typeId === null) {
            throw new Exception_InvalidParameter();
        }

        // session data
        $userId                     = parent::getUserInformation()->getId();
        $themeId                    = parent::getUserInformation()->getThemeId();
        $currentProjectId           = parent::getCurrentProjectId();
        $isDeleteAllowed            = parent::getUserInformation()->getCiDelete();
        $isRelationEditAllowed      = parent::getUserInformation()->getRelationEdit();
        $pageSession                = new Zend_Session_Namespace('page');
        $searchSession              = new Zend_Session_Namespace('search');
        $currentProjectIdForSession = (is_null($currentProjectId)) ? 'ALL' : $currentProjectId;

        // Object Init
        $serviceCiGet       = new Service_Ci_Get($this->translator, $this->logger, $themeId);
        $serviceRelationGet = new Service_Relation_Get($this->translator, $this->logger, $themeId);
        $daoCiRelation      = new Dao_CiRelation();


        // if relation view
        if ($ciRelationTypeId !== null) {
            $currentProjectId = null; // we want to see CI's of all projects the user is member of
            $viewType         = 'ciRelationList';
            $sessionKey       =
                'ciType_' . $typeId . '__' .
                'ciRelationTypeId_' . $ciRelationTypeId . '__' .
                'sourceCiid_' . $sourceCiid;
            $sessionKeyPage   = $sessionKey; // no special handling for projects

            $relationType          = $daoCiRelation->getRelation($ciRelationTypeId);
            $defaultAttributeValue = $serviceRelationGet->getDefaultAttributeName($sourceCiid);


            // add translation files
            $this->translator->addTranslation($this->languagePath . '/de/relation_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/relation_en.csv', 'en');
            parent::setTranslatorLocal();
        } else { // view without relations --> standard case
            $viewType       = 'ciList';
            $sessionKey     = 'ciType_' . $typeId;
            $sessionKeyPage = 'project_' . $currentProjectIdForSession . '__' . $sessionKey;
            $relationType   = null;
        }

        //cleanup session if parameter given
        if ($sessionID !== null) {
            $this->destroyCiSession($sessionID);
        }


        // ITEMS PER PAGE OPTION LIST
        $itemCountPerPageSession                                        = $this->setupItemsCountPerPage('ci', $sessionKey);
        $itemCountPerPageSession->itemCountPerPage['options']['typeId'] = $sessionKey;


        // CURRENT PAGE
        if ($this->_request->isPost()) { // set page to 1 if new filter is set
            $page = 1;
        } elseif ($page === null && isset($pageSession->ciPage[$sessionKeyPage])) { // get current page from session if not given
            $page = $pageSession->ciPage[$sessionKeyPage];
        }

        // if no page setting found --> navigate to 1st page
        if ($page === null) {
            $page = 1;
        }
        // write page to session
        $pageSession->ciPage[$sessionKeyPage] = $page;

        // CONVERT ORDER BY STRING INTO ARRAY
        $orderBy = unserialize(rawurldecode($orderBy));
        if (!$orderBy) {
            $orderBy = array();
        }


        // GET FILTER
        if ($this->_request->isPost()) {
            // get full POST data
            $formdata = $this->_request->getPost();

            // reset / remove unwanted data
            unset($formdata['filterButton']);

            if ($formdata['search'] == 'Filter') {
                $formdata['search'] = null;
            }

            $filter = $formdata;

        } elseif (isset($searchSession->$sessionKey)) {
            $filter = $searchSession->$sessionKey;
        } else {
            $filter = null;
        }

        $filterSet          = false;
        $attributeFilterSet = false;

        if ($filter !== null) {
            // write filter to session
            $searchSession->$sessionKey = $filter;

            $attributeFilters = $filter;
            unset($attributeFilters['search']);

            if (implode('', $attributeFilters) != '') {
                $attributeFilterSet = true;
            }

            if (($filter['search'] !== null && $filter['search'] !== '') || $attributeFilterSet === true) {
                $filterSet = true;
            }
        }


        // GET LIST RESULT
        $ciResult        = $serviceCiGet->getCiList($typeId, $userId, $themeId, $currentProjectId, $itemCountPerPageSession->itemCountPerPage['ci'][$sessionKey], $page, $orderBy, $filterSet, $ciRelationTypeId, $sourceCiid);
        $this->elementId = $this->view->ciBreadcrumb($ciResult['breadcrumbs'], 10, 'text', true);

        $ciTypeName     = $ciResult['typeName'];
        $listEdit       = $ciResult['listEdit'];
        $ciTypeAttach   = $ciResult['ciTypeAttach'];
        $ciList         = $ciResult['ciList'];
        $paginator      = $ciResult['paginator'];
        $defaultOrderBy = $ciResult['defaultOrderBy'];

        $ciSearchColumns = array();
        foreach ($ciResult['attributeList'] as $listItem) {
            array_push($ciSearchColumns, $listItem['name']);
        }

        $isQuery = $ciResult['isQuery'];

        // SPECIAL HANDLING FOR QUERY CI TYPES
        if ($viewType === 'ciRelationList') {
            $ciTypeAttach = false; // do not allow adding cis in ciRelationList-view
        }

        if ($isQuery === true) {
            // virtual ci-type --> no modification possible
            $listEdit              = false;
            $isDeleteAllowed       = false;
            $isRelationEditAllowed = false;
            $ciTypeAttach          = false;

            // replace special signs for smooth processing of mysql and javascript
            $encodedAttributeList = array();
            foreach ($ciResult['attributeList'] as $elem) {
                $encodedAttributeList[] = Service_Ci_Get::convertColumnNameString($elem['name'], "encode");

            }

            // use encoded attributes for filter form
            $searchForm = $serviceCiGet->getFilterForm($encodedAttributeList, $filter, $isQuery);

            // move filter values to encoded attribute names
            if(is_array($filter)) {
                foreach ($filter as $key => $value) {
                    unset($filter[$key]);
                    $filter[Service_Ci_Get::convertColumnNameString($key, 'decode')] = $value;
                }
            }
        } else {
            // standard filter form
            $searchForm = $serviceCiGet->getFilterForm($ciSearchColumns, $filter, $isQuery);
        }

        // APPLY FILTER TO RESULT
        if ($filterSet === true) {

            // filter over all attributes in list
            $ciList = $serviceCiGet->filterciList($ciResult['ciList'], $ciResult['attributeList'], $filter);

            // filter specific attributes
            $ciList = $serviceCiGet->filterciListAttributes($ciList, $ciResult['attributeList'], $filter);

            // get result for current page
            $result    = $serviceCiGet->getPaginator($ciList, $page, $sessionKey);
            $paginator = $result['p'];
            $ciList    = $result['c'];

        }

        $this->view->breadcrumbDepth         = $breadcrumbDepth;
        $this->view->listEdit                = $listEdit;
        $this->view->isDeleteAllowed         = $isDeleteAllowed;
        $this->view->isRelationEditAllowed   = $isRelationEditAllowed;
        $this->view->searchForm              = $searchForm;
        $this->view->filter                  = $filter;
        $this->view->typeId                  = $typeId;
        $this->view->paginator               = $paginator;
        $this->view->ciList                  = $ciList;
        $this->view->totalItemCount          = $paginator->getTotalItemCount();
        $this->view->typeName                = $ciTypeName;
        $this->view->ciTypeAttach            = $ciTypeAttach;
        $this->view->createButtonDescription = $ciResult['createButtonDescription'];
        $this->view->attributeList           = $ciResult['attributeList'];
        $this->view->page                    = $page;
        $this->view->orderBy                 = $orderBy;
        $this->view->scrollbar               = $ciResult['scrollbar'];
        $this->view->language                = $this->translator->getLocale();
        $this->view->isQuery                 = $isQuery;
        $this->view->breadcrumbs             = $ciResult['breadcrumbs'];
        $this->view->display                 = $attributeFilterSet;
        $this->view->ciRelationTypeId        = $ciRelationTypeId;
        $this->view->ciRelationType          = $relationType;
        $this->view->sourceCiid              = $sourceCiid;
        $this->view->defaultAttributeValue   = $defaultAttributeValue ?? '';
        $this->view->viewType                = $viewType;
        if (empty($this->view->orderBy)) {
            if (!empty($defaultOrderBy)) {
                $this->view->orderBy = $defaultOrderBy;
            } else {
                $this->view->orderBy = array(Db_Attribute::ID => "DESC");
            }
        }
    }


    /**
     * displays the detail page of a given CI
     */
    public function detailAction()
    {
        // config
        $viewConfig      = new Util_Config('view.ini', APPLICATION_ENV);
        $breadcrumbDepth = (int)$viewConfig->getValue('ci.detail.breadcrums.depth', 10);

        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce.js', 'text/javascript');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce_init_' . $this->translator->getLocale() . '.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet(APPLICATION_URL . 'css/dropzone.css');
        $this->view->headLink()->appendStylesheet(APPLICATION_URL . 'css/font-awesome.min.css?');

        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/dropzone/dropzone.js');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/dropzone/returnDropzone.js', 'text/javascript');

        $this->view->inlineScript()->appendFile(
            APPLICATION_URL . 'js/tooltip/wz_tooltip.js',
            'text/javascript'
        );

        $this->logger->log('detailAction', Zend_Log::DEBUG);
        $ciId     = $this->_getParam('ciid');
        $uniqueId = $this->_getParam('uniqueId');
        $tabIndex = $this->_getParam('tab_index');


        $adminView = false;
        if (Zend_Registry::get('adminMode') === true) {
            $adminView = true;
        }


        $ciServiceGet = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ciResult     = $ciServiceGet->getCiDetail($ciId, parent::getUserInformation()->getId());
        $lock         = Util_Locking::getByLockTypeAndResourceId(Util_Locking::CI_LOCK, $ciId);

        $this->elementId = $this->view->ciBreadcrumb($ciResult['breadcrumbs'], 10, 'text', true);

        $type = $this->_getParam('type');
        $page = $this->_getParam('page');

        //cleanup session
        $sessionID = $this->_getParam('sessionID');

        if (isset($sessionID)) {
            $this->destroyCiSession($sessionID);
        }


        if ($this->_request->isPost()) {
            $canceled = $this->_request->getPost('cancel');
            if (isset($canceled)) {
                $isHeldBy = $lock->isHeldBy(parent::getUserInformation()->getId());
                if ($isHeldBy) {
                    $lock->release();
                }
                $this->_redirect('ci/detail/ciid/' . $ciId . '/tab_index/' . $tabIndex);
            }
        }
        // unique id is set if single edit form is submitted and value is updated
        if ($uniqueId) {
            $ciServiceUpdate = new Service_Ci_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $singleRet       = $ciServiceUpdate->getUpdateSingleAttributeForm($ciId, $uniqueId);


            $form        = $singleRet['form'];
            $ciAttribute = $singleRet['ciAttribute'];


            if ($this->_request->isPost()) {
                $formdata   = $this->_request->getPost();
                $lockIsHeld = $lock->isHeldBy(parent::getUserInformation()->getId());
                if (!$lockIsHeld) {
                    $notifications['error'] = $this->translator->translate('lockExpired');
                    $this->_helper->FlashMessenger($notifications);
                    $this->_redirect('ci/detail/ciid/' . $ciId);
                }
                $lock->acquireForUser(parent::getUserInformation()->getId());

                if ($form->isValid($formdata, array('ciid' => $ciId))) {
                    $notification = array();
                    try {
                        $ciServiceUpdate->updateSingleAttribute(parent::getUserInformation()->getId(), $ciId, $ciAttribute, $formdata);
                        $notification['success'] = $this->translator->translate('ciUpdateSuccess');
                    } catch (Exception_Ci_Unknown $e) {
                        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while updating Ci "' . $ciId . '" ', Zend_Log::ERR);
                        $notification['error'] = $this->translator->translate('ciUpdateFailed');

                    } catch (Exception_Ci_UpdateFailed $e) {
                        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Ci "' . $ciId . '" ', Zend_Log::ERR);
                        $notification['error'] = $this->translator->translate('ciUpdateFailed');

                    } catch (Exception_Ci_UpdateItemNotFound $e) {
                        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Ci "' . $ciId . '". No items were updated!', Zend_Log::ERR);
                        $notification['error'] = $this->translator->translate('ciUpdateFailed');
                    }

                    $lock->release();
                    $this->_helper->FlashMessenger($notification);

                    if (isset($type)) {
                        $this->_redirect('ci/index/typeid/' . $type . '/page/' . $page);
                    } else {
                        $this->_redirect('ci/detail/ciid/' . $ciId . '/tab_index/' . $tabIndex);
                    }
                } else {
                    $form->populate($formdata);
                    $lock->refresh();
                }
            }
        }


        $this->view->ciId = $ciId;


        $this->view->icon      = $ciResult['icon'];
        $this->view->relations = $ciResult['relations'];

        $this->view->historyCreated = $ciResult['historyCreated'];
        $this->view->historyChanged = $ciResult['historyChange'];

        $this->view->breadcrumbs     = $ciResult['breadcrumbs'];
        $this->view->breadcrumbDepth = $breadcrumbDepth;
        $this->view->events          = $ciResult['events'];
        $this->view->tickets         = $ciResult['tickets'];
        $this->view->ticketurl       = $ciResult['ticketurl'];;
        $this->view->ciType = $ciResult['ciType'];

        $this->view->projectList   = $ciResult['projectList'];
        $this->view->attributeList = $ciResult['attributeList'];
        $this->view->language      = $this->translator->getLocale();
        $this->view->tabIndex      = $tabIndex;
        $this->view->isAdmin       = $adminView;
        $this->view->form          = (isset($form) ? $form : null);
        $this->view->ciAttributeId = (isset($ciAttribute[Db_CiAttribute::ID]) ? $ciAttribute[Db_CiAttribute::ID] : null);
    }

    public function historydetailAction()
    {
        $ci_id      = $this->_getParam('ciid');
        $history_id = $this->_getParam('historyid');
        $user_id    = $this->getUserInformation()->getId();

        $ci_get      = new Service_Ci_Get($this->translator, $this->logger, $this->getUserInformation()->getThemeId());
        $history_get = new Service_History_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        $this->_helper->viewRenderer('detail');
        $this->view->headLink()->appendStylesheet(APPLICATION_URL . 'css/font-awesome.min.css?');
        $this->view->inlineScript()->appendFile(
            APPLICATION_URL . 'js/tooltip/wz_tooltip.js',
            'text/javascript'
        );

        $ci_result     = $ci_get->getHistoricalCiDetail($ci_id, $history_id, $user_id);
        $history       = $history_get->getHistoryById($history_id);
        $point_in_time = '';
        if (!empty($history)) {
            $point_in_time = $history[Db_History::DATESTAMP];
        }

        if (isset($ci_result['ciDidNotExist'])) {
            $path_to_detail         = APPLICATION_URL . "ci/detail/ciid/" . $ci_id;
            $button_translation     = $this->translator->translate("viewingHistoryWarningButton");
            $button                 = '<a href="' . $path_to_detail . '">' . $button_translation . '</a>';
            $notifications          = array();
            $notifications['error'] = sprintf($this->translator->translate("viewingHistoryCiExpiredWarning"), $point_in_time, $button);
            $this->_helper->FlashMessenger($notifications);
            $this->view->ciId          = $ci_id;
            $this->view->language      = $this->translator->getLocale();
            $this->view->historyView   = true;
            $this->view->pointInTime   = $history_id;
            $this->view->ciDidNotExist = true;
            return;
        }

        /* Set flash message */
        $path_to_detail         = APPLICATION_URL . "ci/detail/ciid/" . $ci_id;
        $button_translation     = $this->translator->translate("viewingHistoryWarningButton");
        $button                 = '<a href="' . $path_to_detail . '">' . $button_translation . '</a>';
        $notifications          = array();
        $notifications['error'] = sprintf($this->translator->translate("viewingHistoryWarning"), $point_in_time, $button);
        $this->_helper->FlashMessenger($notifications);

        /* Set view parameters */

        $this->view->ciId = $ci_id;

        $this->view->icon            = $ci_result['icon'];
        $this->view->breadcrumbs     = $ci_result['breadcrumbs'];
        $this->view->breadcrumbDepth = $ci_result['breadcrumbDepth'];
        $this->view->ciType          = $ci_result['ciType'];
        $this->view->projectList     = $ci_result['projectList'];
        $this->view->attributeList   = $ci_result['attributeList'];
        $this->view->isAdmin         = $ci_result['isAdmin'];

        $this->view->language    = $this->translator->getLocale();
        $this->view->historyView = true;
        $this->view->historyId   = $history_id;
    }


    // AJAX!
    // returns json
    public function singleeditAction()
    {

        $ciId                = $this->_getParam('ciid');
        $forceLock           = $this->_getParam("forcelock");
        $userId              = parent::getUserInformation()->getId();
        $response            = array();
        $response['success'] = true;

        $lock = Util_Locking::getByLockTypeAndResourceId(Util_Locking::CI_LOCK, $ciId);
        $lock->acquireForUser($userId);
        $heldBy = $lock->getHeldBy();
        if ($forceLock && $heldBy != $userId) {
            $lock->handOverToUser($userId);
        } elseif (!$lock->isHeldBy($userId)) {
            $forceEditUrl = sprintf("%sci/edit/ciid/%s/forcelock/true", APPLICATION_URL, $lock->getResourceId());
            $this->lockErrorMessage($lock, $forceEditUrl);
            $response['success'] = false;
            echo json_encode($response);
            exit;
        }

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $this->_helper->layout->setLayout('print', false);


        $ciAttributeId = $this->_getParam('ciAttributeId');
        $type          = $this->_getParam('type');
        $page          = $this->_getParam('page');
        $tabIndex      = $this->_getParam('tab_index');


        $view = new Zend_View();
        $view->setEscape('htmlentities');
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/ci/');
        $view->setEncoding('UTF-8');
        $view->headMeta()->appendName('charset', "UTF-8");
        $view->doctype("HTML5");

        $ciServiceUpdate = new Service_Ci_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $singleRet       = $ciServiceUpdate->getUpdateSingleAttributeForm($ciId, $ciAttributeId, $type, $page, $tabIndex, parent::getUserInformation()->getId());

        $view->ciid      = $ciId;
        $view->form      = $singleRet['form'];
        $view->attribute = $singleRet['ciAttribute'];
        $view->tabIndex  = $tabIndex;
        $view->lockId    = $lock->getId();

        if (isset($type)) {
            $html = $view->render('_singleEditIndex.phtml');
        } else {
            $html = $view->render('_singleEdit.phtml');
        }

        $response['msg'] = $html;
        echo json_encode($response);
        exit;
    }


    public function printAction()
    {
        $this->_helper->layout->setLayout('print', false);

        $ciId = $this->_getParam('ciid');

        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ciResult        = $ciServiceGet->getCiDetail($ciId, parent::getUserInformation()->getId());
        $this->elementId = $this->view->ciBreadcrumb($ciResult['breadcrumbs'], 10, 'text', true);

        $adminView = false;
        if (Zend_Registry::get('adminMode') === true) {
            $adminView = true;
        }

        $this->view->isAdmin        = $adminView;
        $this->view->ciId           = $ciId;
        $this->view->icon           = $ciResult['icon'];
        $this->view->relations      = $ciResult['relations'];
        $this->view->created        = $ciResult['created'];
        $this->view->user           = $ciResult['user'];
        $this->view->breadcrumbs    = $ciResult['breadcrumbs'];
        $this->view->historyCreated = $ciResult['historyCreated'];
        $this->view->historyChanged = $ciResult['historyChange'];
        $this->view->ciType         = $ciResult['ciType'];
        $this->view->projectList    = $ciResult['projectList'];
        $this->view->attributeList  = $ciResult['attributeList'];

    }

    public function historyprintAction()
    {
        $this->_helper->layout->setLayout('print', false);

        $this->_helper->viewRenderer('print');

        $ci_id      = $this->_getParam('ciid');
        $history_id = $this->_getParam('historyid');
        $user_id    = $this->getUserInformation()->getId();

        $ci_get    = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ci_result = $ci_get->getHistoricalCiDetail($ci_id, $history_id, $user_id);

        $this->view->ciId = $ci_id;

        $this->view->icon            = $ci_result['icon'];
        $this->view->breadcrumbs     = $ci_result['breadcrumbs'];
        $this->view->breadcrumbDepth = $ci_result['breadcrumbDepth'];
        $this->view->ciType          = $ci_result['ciType'];
        $this->view->projectList     = $ci_result['projectList'];
        $this->view->attributeList   = $ci_result['attributeList'];
        $this->view->isAdmin         = $ci_result['isAdmin'];

        $this->view->language    = $this->translator->getLocale();
        $this->view->historyView = true;
    }

    public function createAction()
    {
        Zend_Registry::set('headScripts', array());
        Zend_Registry::set('jsScripts', array());

        $this->view->inlineScript()->appendFile(APPLICATION_URL . 'js/tooltip/wz_tooltip.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet(APPLICATION_URL . 'css/dropzone.css');

        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/dropzone/dropzone.js');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/dropzone/returnDropzone.js', 'text/javascript');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/dropzone/dropzone_single_upload.js', 'text/javascript');


        $formFinished   = $this->_getParam('formFinished');
        $isRefresh      = $this->_getParam('isRefresh');
        $ciTypeFromUrl  = $this->_getParam('citype');
        $duplicate      = $this->_getParam('duplicate');
        $ciId           = $this->_getParam('ciid');
        $showCiTypeElem = $this->_getParam('showCiTypeElem');
        $sessionID      = $this->_getParam('sessionID');
        $formData       = $this->getAllParams();

        $ciTypeDaoImpl   = new Dao_CiType();
        $ciServiceCreate = new Service_Ci_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ciServiceUpdate = new Service_Ci_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        $ret        = null;
        $ciTypeList = array();
        $ciType     = $ciTypeFromUrl;

        if (!is_null($ciTypeFromUrl)) {
            $isRefresh      = 1;
            $showCiTypeElem = 0;
        }

        if (is_null($showCiTypeElem)) {
            $showCiTypeElem = 1;
        }

        //session will be destroyed on every unload of the document via ajax
        //only if an attribute will be removed, the session will be kept
        if (!isset($sessionID) || empty($sessionID)) {
            $sessionID = $ciServiceCreate->initiaizecreateCISession();
            $this->destroyCiSession($sessionID);
            $enableFormValidation = false;
        }

        // if duplicate mode --> override formData with data from ci
        if ($duplicate && $ciId) {

            $ciRet     = $ciServiceGet->getCiDetail($ciId, parent::getUserInformation()->getId());
            $ciTypeDto = $ciRet['ciType'];
            $ciType    = $ciTypeDto[Db_CiType::ID];

            $sessionID  = $ciServiceUpdate->prepareCiEdit(parent::getUserInformation()->getId(), $ciId, $ciTypeDto[Db_CiType::ID]);
            $attributes = $ciServiceUpdate->getUpdateCiAttributes($sessionID);

            foreach ($attributes as $key => $att) {
                if ($att['deleted']) {
                    unset($attributes[$key]);
                }
            }

            $formData              = $ciServiceUpdate->getUpdateCiFormData($ciId, $attributes);
            $formData['ciicon']    = $ciRet['ci'][Db_Ci::ICON];
            $formData['sessionID'] = $sessionID;
            if (count($ciRet['projectList']) == 1) {
                $formData['project'] = $ciRet['projectList'][0]['id'];
            } else {
                $notification          = array();
                $notification['error'] = $this->translator->translate('ciDuplicateFailMultipleProjects');
                $this->_helper->FlashMessenger($notification);
            }

            $showCiTypeElem = 0;

        }

        // if no citype is given from url -> try to get it from form
        if (!$ciTypeFromUrl && isset($formData['parentCiType'])) {
            $ciType = $formData['parentCiType'];
        }


        if ($ciType) {

            //get ci_type_id
            $typeId = $ciType;
            if (!is_numeric($typeId)) {
                $type   = $ciTypeDaoImpl->getCiTypeByName($typeId);
                $typeId = $type[Db_CiType::ID];
            }

            //throw error if typeId can't be resolved
            if (!is_numeric($typeId)) {
                $notification          = array();
                $notification['error'] = $this->translator->translate('ciCreateLinkFailed');
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('ci/create/');
            }

            $tList = $ciTypeDaoImpl->retrieveCiTypeHierarchy($typeId);
            $tList = array_reverse($tList);


            $isParent   = true;
            $childCount = 0;
            foreach ($tList as $vt) {
                // first is parent
                if ($isParent) {
                    $isParent                   = false;
                    $ciTypeList['parentCiType'] = $vt;
                    $formData['parentCiType']   = $vt;
                } else {
                    $childCount++;
                    $childEntry              = 'child_' . $childCount;
                    $ciTypeList[$childEntry] = $vt;
                    $formData[$childEntry]   = $vt;
                }
            }
        }

        // set project
        //   1) default project from citype
        //   2) current selected project (if selected)
        //   3) no project
        if ($formFinished == 0 && $isRefresh == 1 && !$duplicate) {
            if (!empty($ciTypeList)) {
                $formData['project'] = $ciTypeDaoImpl->getDefaultProjectByCiTypeId($ciServiceCreate->getFirstHandParentCiType($ciTypeList));
            }

            if (empty($formData['project'])) {
                $formData['project'] = parent::getCurrentProjectId();
            }
        }


        if ($this->_request->isPost() || $duplicate || $ciType) {

            //get form for selecting the specific ci_type
            $ret       = $ciServiceCreate->getCreateBasicCiForm($sessionID, $formData);
            $basicForm = $ret['form'];


            if ($formFinished == 0) {

                $enableFormValidation = true;

                // if fresh page --> init form
                if ($isRefresh == 1) {
                    $ciServiceCreate->createFormAttributeSession(parent::getUserInformation()->getId(), $sessionID, $ret['ciTypesToUse']);
                    $enableFormValidation = false;
                }


                $form = $ciServiceCreate->getCreateCiForm(parent::getUserInformation()->getId(), $sessionID, $basicForm, $formData, null, $enableFormValidation, $ret['ciTypeAttributeAttach']);
                $form->populate($formData);


            } else {// handle ci-form submit

                $form = $ciServiceCreate->getCreateCiForm(parent::getUserInformation()->getId(), $sessionID, $basicForm, $formData, null, true, $ret['ciTypeAttributeAttach']);

                // remove empty entries
                foreach ($formData as $key => $val) {
                    if ($val == '' || $val == ' ' || is_null($val)) {
                        unset($formData[$key]);
                    }
                }

                //if ci-form(attributes) is valid
                if ($form->isValid($formData)) {

                    $val = $form->getValues();

                    $formData['ciicon'] = $val['ciicon'];


                    $notification = array();
                    try {
                        $ciId = $ciServiceCreate->createCi($val, parent::getUserInformation()->getId(), $sessionID);
                        $this->destroyCiSession($sessionID);
                        $notification['success'] = $this->translator->translate('ciInsertSuccess');
                    } catch (Exception_Ci_Unknown $e) {
                        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new CI', Zend_Log::ERR);
                        $notification['error'] = $this->translator->translate('ciInsertFailed');
                    } catch (Exception_Ci_InsertFailed $e) {
                        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create CI. No items where inserted!', Zend_Log::ERR);
                        $notification['error'] = $this->translator->translate('ciInsertFailed');
                    } catch (Exception $e) {
                        throw new Exception_Ci($e);
                    }

                    $this->_helper->FlashMessenger($notification);
                    if ($notification['error'])
                        $this->_redirect('ci/create');

                    //for recreating the navigation tree
                    parent::clearNavigationCache();
                    parent::init();

                    $this->_redirect('ci/detail/ciid/' . $ciId);
                } else { //if ci-form is not valid

                    $formFinished = 0;

                    // form is invalid
                    $this->logger->log('Form is invalid', Zend_Log::DEBUG);
                    $form->populate($formData);
                }
            } // END ci-form submit

        }

        //in some cases the form is not initialized
        if (!isset($form)) {
            $form = $ciServiceCreate->getCreateCiForm(parent::getUserInformation()->getId(), $sessionID, null, null, $ret['ciTypeAttributeAttach']);
            $form->populate($formData);
        }

        //if ciType is set via param or isValidate is set via param
        if ($formFinished == 0) {
            $attributeList = $ciServiceGet->getAttributeGroupAttributeList($ciServiceCreate->getCreateCiAttributes($sessionID));
        }

        //add new keys to $attributeList
        $newAttributeList = array();
        foreach ($attributeList as $key => $value) {
            $newAttributeList['children'][$key]   = $value;
            $newAttributeList['readCount'][$key]  = $value['readCount'];
            $newAttributeList['writeCount'][$key] = $value['writeCount'];
        }

        //call recursive function
        $attributeList = Service_Ci_Get::restrictAttributeList($attributeList, $newAttributeList);

        //set view-params
        $this->view->showCiTypeElem            = $showCiTypeElem;
        $this->view->ciTypeDescription         = $ret['ciTypeDescription'];
        $this->view->ciCreateButtonDescription = $ret['ciTypeButtonDescription'];
        $this->view->attributeAttach           = $ret['ciTypeAttributeAttach'];
        $this->view->form                      = $form;
        $this->view->sessionID                 = $sessionID;
        $this->view->attributeList             = $attributeList;
        $this->view->currentCiTypeId           = $ret['ciTypeId'];
        $this->view->isCiAttachAllowed         = $ciServiceCreate->isCiTypeAttachAllowed($ret['ciTypeId']);
        $this->view->tabs                      = $ret['tabs'];
        $this->view->icon                      = APPLICATION_URL . 'images/ci.png';
        $this->view->formData                  = $formData;
        $this->view->formFinished              = $formFinished;

        //append js-files
        $addConfigArray          = Zend_Registry::get('headScripts');
        $this->view->headScripts = $addConfigArray;
        foreach ($addConfigArray as $heasScript) {
            $this->view->headScript()->appendFile($heasScript, 'text/javascript');
        }
        $jscripts              = Zend_Registry::get('jsScripts');
        $this->view->jsScripts = $jscripts;

        //clear cache for the case the ci is the first one in a ci_type
        parent::clearNavigationCache();
    }


    public function editAction()
    {
        $ciId      = $this->_getParam('ciid');
        $forceLock = $this->_getParam('forcelock');
        $canceled  = $this->_request->getPost('cancel');

        $userId = parent::getUserInformation()->getId();

        $lock = Util_Locking::getByLockTypeAndResourceId(Util_Locking::CI_LOCK, $ciId);
        $lock->acquireForUser($userId);
        $heldBy = $lock->getHeldBy();
        if ($forceLock && $heldBy != $userId) {
            $lock->handOverToUser($userId);
        }

        if (empty($canceled) && !$lock->isHeldBy($userId)) {
            $this->lockErrorMessage($lock);
            $this->_redirect('ci/detail/ciid/' . $ciId);
        }

        $viewConfig      = new Util_Config('view.ini', APPLICATION_ENV);
        $breadcrumbDepth = (int)$viewConfig->getValue('ci.detail.breadcrums.depth', 10);

        Zend_Registry::set('headScripts', array());
        Zend_Registry::set('jsScripts', array());

        $this->view->inlineScript()->appendFile(APPLICATION_URL . 'js/tooltip/wz_tooltip.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet(APPLICATION_URL . 'css/dropzone.css');
        $this->view->headLink()->appendStylesheet(APPLICATION_URL . 'css/font-awesome.min.css?');


        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/dropzone/dropzone.js');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/dropzone/returnDropzone.js', 'text/javascript');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/dropzone/dropzone_single_upload.js', 'text/javascript');

        $isRefresh = $this->_getParam('isRefresh');
        $sessionID = $this->_getParam('sessionID');
        $tabIndex  = $this->_getParam('tab_index');


        $ciServiceUpdate = new Service_Ci_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        $attributeDaoImpl      = new Dao_Attribute();
        $attributeGroupDaoImpl = new Dao_AttributeGroup();


        $ciRet           = $ciServiceGet->getCiDetail($ciId, parent::getUserInformation()->getId());
        $this->elementId = $this->view->ciBreadcrumb($ciRet['breadcrumbs'], 10, 'text', true);
        $ci              = $ciRet['ci'];
        $ciTypeDto       = $ciRet['ciType'];

        $relationServiceGet = new Service_Relation_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $defaultattribute   = $relationServiceGet->getDefaultAttributeName($ciId);


        $this->view->icon             = $ciRet['icon'];
        $this->view->ciId             = $ciId;
        $this->view->breadcrumbs      = $ciRet['breadcrumbs'];
        $this->view->breadcrumbDepth  = $breadcrumbDepth;
        $this->view->defaultattribute = $defaultattribute;
        $this->view->ciType           = $ciTypeDto;
        $this->view->tabs             = $ciTypeDto[Db_CiType::IS_TAB_ENABLED];
        $this->view->projectList      = $ciRet['projectList'];
        $this->view->historyCreated   = $ciRet['historyCreated'];
        $this->view->historyChanged   = $ciRet['historyChange'];
        $this->view->relations        = $ciRet['relations'];
        $this->view->events           = $ciRet['events'];
        $this->view->tabIndex         = $tabIndex;
        $this->view->lockId           = $lock->getId();


        //session will be destroyed on every unload of the document via ajax
        //only if an attribute will be removed, the session will be kept
        if (!isset($sessionID) || empty($sessionID)) {
            $sessionID = $ciServiceUpdate->prepareCiEdit(parent::getUserInformation()->getId(), $ciId, $ciTypeDto[Db_CiType::ID]);
        }
        $this->view->sessionID = $sessionID;

        $validate = false;
        if ($isRefresh || $this->_request->isPost()) {
            $validate = true;
        }


        $form       = $ciServiceUpdate->getUpdateCiForm($ciId, $validate, $sessionID, $tabIndex, $ciTypeDto[Db_CiType::IS_ATTRIBUTE_ATTACH], parent::getUserInformation()->getId());
        $attributes = $ciServiceUpdate->getUpdateCiAttributes($sessionID);


        foreach ($attributes as $key => $att) {
            if ($att['deleted']) {
                unset($attributes[$key]);
            }
        }
        if (!$this->_request->isPost()) {
            $formData           = $ciServiceUpdate->getUpdateCiFormData($ciId, $attributes);
            $formData['ciicon'] = $ci[Db_Ci::ICON];
            $form->populate($formData);
            $this->view->formData = $formData;
        } else {
            // handle submitted forms!!!
            $formData = $this->_request->getPost();

            if (isset($canceled)) {
                $lock->release();
                $this->_redirect('ci/detail/ciid/' . $ciId . '/sessionID/' . $sessionID . '/tab_index/' . $tabIndex);
            }
            $this->view->icon = $formData['ciicon'];


            if ($form->isValid($formData, array('ciid' => $ciId)) && !$isRefresh) {
                $val       = $form->getValues();
                $sessionID = $val['sessionID'];

                $notification = array();
                try {
                    $ciServiceUpdate->updateCi(parent::getUserInformation()->getId(), $ciId, $val, $attributes, $sessionID);
                    $this->destroyCiSession($sessionID);
                    $notification['success'] = $this->translator->translate('ciUpdateSuccess');
                } catch (Exception $e) {
                    $lock->release();
                    throw new Exception_Ci($e);
                } catch (Exception_Ci_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new CI', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('ciInsertFailed');
                } catch (Exception_Ci_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update CI.', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('ciUpdateFailed');
                }
                $lock->release();
                $this->_helper->FlashMessenger($notification);

                //for recreating the navigation tree
                parent::clearNavigationCache();
                parent::init();

                $this->_redirect('ci/detail/ciid/' . $ciId . '/tab_index/' . $tabIndex);
                exit;
            } else {
                // form is invalid
                $this->logger->log('Form is invalid', Zend_Log::DEBUG);
                $form->populate($formData);

                //get first invalid field an set tab
                $errorMessages = $form->getMessages();
                $errorFields   = array_keys($errorMessages);
                if (!empty($errorFields)) {
                    $firstErrorField = $form->getElement($errorFields[0])->getFullyQualifiedName();
                    //remove genID
                    $matches = array();
                    preg_match('/(\d+)\D*\z/', $firstErrorField, $matches, PREG_OFFSET_CAPTURE);//get last occurrence of number
                    $substringEnd    = (!empty($matches)) ? $matches[0][1] : strlen($firstErrorField);//$matches 0 = result_number, 1 = string position
                    $firstErrorField = substr($firstErrorField, 0, $substringEnd);//override with correct name


                    //get Attribute by Name
                    $errorAttribute = $attributeDaoImpl->getAttributeByNameAll($firstErrorField);
                    if (!empty($errorAttribute)) {
                        $attributeGroupHierarchy = $attributeGroupDaoImpl->getAttributeGroupHierarchy($errorAttribute[Db_Attribute::ATTRIBUTE_GROUP_ID]);
                        $this->view->tabIndex    = "fragment-" . $attributeGroupHierarchy[0][Db_AttributeGroup::NAME];
                    }

                }
                $this->view->formData = $formData;
            }
        }

        $this->view->attributeList = $ciServiceGet->getAttributeGroupAttributeList($attributes);
        $this->view->form          = $form;

        $addConfigArray          = Zend_Registry::get('headScripts');
        $this->view->headScripts = $addConfigArray;

        foreach ($addConfigArray as $heasScript) {
            $this->view->headScript()->appendFile($heasScript, 'text/javascript');
        }

        $jscripts              = Zend_Registry::get('jsScripts');
        $this->view->jsScripts = $jscripts;

        $lock->refresh();
    }


    private function destroyCiSession($sessionID)
    {

        $attributeDaoImpl = new Dao_Attribute();
        $attributeDaoImpl->deleteTempTableForCiCreate($sessionID);

    }


    /**
     * adds or removes a given user from/to the current ci
     *
     * @return mixed
     */
    public function userAction()
    {
        $ciId = $this->_getParam('ciid');

        $ciServiceCreate = new Service_Ci_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ciPermission    = $ciServiceCreate->getUserMappingForm($ciId);


        $form            = $ciPermission['form'];
        $users           = $ciPermission['userList'];
        $row1            = $ciPermission['row1'];
        $row2            = $ciPermission['row2'];
        $row3            = $ciPermission['row3'];
        $count           = $ciPermission['count'];
        $currentFormData = $ciPermission['formdata'];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {

                $notification = array();
                try {
                    $ciServiceCreate->updateCiPermission($ciId, $formData, $users);
                    $notification['success'] = $this->translator->translate('ciPermissionUpdateUsersSuccess');
                } catch (Exception_Ci_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while updating Ci "' . $ciId . '" User Mapping', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('ciPermissionUpdateUsersFailed');
                } catch (Exception_Ci_UpdateUserMappingFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to update Ci "' . $ciId . '" User Mapping', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('ciPermissionUpdateUsersFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('ci/detail/ciid/' . $ciId);
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($currentFormData);
        }

        $this->view->form  = $form;
        $this->view->row1  = $row1;
        $this->view->row2  = $row2;
        $this->view->row3  = $row3;
        $this->view->count = $count;
        $this->view->ciid  = $ciId;
    }


    public function pdfexportAction()
    {
        $ciId = $this->_getParam('ciid');

        if (is_null($ciId)) {
            throw new Exception_InvalidParameter();
        }

        $ciServiceExport = new Service_Ci_Export($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $output          = $ciServiceExport->getPdfFile($ciId, parent::getUserInformation()->getId());

        //send appropriate headers and the pdf - works with modern browsers. (Die, ie6!)
        $response = new Zend_Controller_Response_Http();
        $response->setHeader('Content-Disposition', 'attachment; filename=' . $filename);
        $response->setHeader('Content-type', 'application/pdf');
        $response->setHeader('Content-length', strlen($output)); //especially useful for http-pipeling

        $response->setBody($output);
        $response->sendResponse();
        exit;
    }

    /**
     *
     * Export whole ci list
     *
     * @param citype int ci_type_id to export
     * @param type string xls csv etc
     */
    public function exportAction()
    {
        $typeId           = $this->_getParam('typeid');
        $exportType       = $this->_getParam('type');
        $exportall        = $this->_getParam('all');
        $ciRelationTypeId = $this->_getParam('ciRelationTypeId');
        $sourceCiid       = $this->_getParam('sourceCiid');
        $orderBy          = $this->_getParam('orderBy');

        // CONVERT ORDER BY STRING INTO ARRAY
        $orderBy = unserialize(rawurldecode($orderBy));
        if (!$orderBy) {
            $orderBy = array();
        }

        $sess = new Zend_Session_Namespace('search');

        if ($ciRelationTypeId !== null) {
            $sessionKey =
                'ciType_' . $typeId . '__' .
                'ciRelationTypeId_' . $ciRelationTypeId . '__' .
                'sourceCiid_' . $sourceCiid;
        } else { // view without relations --> standard case
            $sessionKey = 'ciType_' . $typeId;
        }
        $filter = $sess->$sessionKey;

        $ciServiceExport = new Service_Ci_Export($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $res             = $ciServiceExport->getCiListExport($typeId, $exportType, parent::getUserInformation()->getId(), parent::getCurrentProjectId(), $filter, $exportall, $orderBy, $ciRelationTypeId, $sourceCiid);


        $size     = $res['size'];
        $filename = $res['filename'];
        $path     = $res['path'];

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . $size);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');

        nl2br(readfile($path));
        if (file_exists($path)) {
            unlink($path);
        }
        exit;
    }

    /**
     * displays all projects and marks those assigned to the ci
     *
     * on change the new entries are inserted and deselected projects are removed from DB
     */
    public function projectAction()
    {
        $ciId = $this->_getParam('ciid');

        if (is_null($ciId)) {
            throw new Exception_InvalidParameter();
        }

        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $breadCrumbs     = $ciServiceGet->getCiBreadcrumbs($ciId);
        $this->elementId = $this->view->ciBreadcrumb($breadCrumbs, 10, 'text', true);

        $adminView = false;
        if (Zend_Registry::get('adminMode') === true) {
            $adminView = true;
        }

        if (!$ciServiceGet->checkPermission($ciId, parent::getUserInformation()->getId())) {
            throw new Exception_AccessDenied();
        }

        $ciServiceProject = new Service_Ci_Project($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ret              = $ciServiceProject->getCiProjectMappingForm(parent::getUserInformation()->getId(), $ciId);

        $form                = $ret['form'];
        $permittedList       = $ret['permittedList'];
        $selectedProjectList = $ret['selectedProjectList'];

        // validate input
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {

                if (array_sum($form->getValues()) == 0)
                    $this->_redirect('ci/project/ciid/' . $ciId . '/');

                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $ciServiceProject->updateCiProject(parent::getUserInformation()->getId(), $ciId, $permittedList, $form->getValues(), $selectedProjectList);
                    parent::clearNavigationCache();
                    $this->logger->log('Project Action', Zend_Log::INFO);
                    $notification['success'] = $this->translator->translate('ciProjectMappingUpdateSuccess');
                } catch (Exception_Ci_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating Ci "' . $ciId . '" Project mapping ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('ciProjectMappingUpdateFailed');
                } catch (Exception_Ci_UpdateProjectMappingFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Ci "' . $ciId . '" Project mapping  ', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('ciProjectMappingUpdateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('ci/detail/ciid/' . $ciId . '/');
                exit;
            } else {
                $form->populate($formData);
            }
        }

        $this->view->form     = $form;
        $this->view->projects = $permittedList;
        $this->view->ciId     = $ciId;
        $this->view->isAdmin  = $adminView;
    }


    /**
     * deletes a given CI. the ciId has to be set
     *
     * @return mixed
     */
    public function deleteAction()
    {
        $ciId    = $this->_getParam('ciid');
        $ciType  = $this->_getParam('typeid');
        $referer = $this->getRequest()->getServer('HTTP_REFERER');

        if (is_null($ciId)) {
            throw new Exception_InvalidParameter();
        }

        $notification = array();
        try {
            $ciServiceDelete = new Service_Ci_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $ciServiceDelete->deleteCi(parent::getUserInformation(), $ciId);
            parent::clearNavigationCache();
            $notification['success'] = $this->translator->translate('ciDeleteSuccess');
        } catch (Exception_Ci_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while deleting Ci "' . $ciId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('ciDeleteFailed');
        } catch (Exception_Ci_UpdateProjectMappingFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to deleting Ci "' . $ciId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('ciDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);

        //for recreating the navigation tree
        parent::clearNavigationCache();
        parent::init();

        if ($ciType) {
            $this->_redirect('ci/index/typeid/' . $ciType); // for ci/detail or ci/edit --> redirect back to list
        } else {
            $this->_redirect($referer); // back to last page
        }
    }

    // AJAX
    public function checkdeleteAction()
    {
        $this->_helper->layout->setLayout('clean');
        $ciId = $this->_getParam('ciid');

        $this->logger->log('checkdelete for ci ' . $ciId, Zend_Log::INFO);
        try {
            $deleteConfig    = new Zend_Config_Ini(APPLICATION_PATH . '/configs/delete.ini', APPLICATION_ENV);
            $deleteRelations = $deleteConfig->delete->ci->deleterelations;

            $ciRelationDaoImpl = new Dao_CiRelation();
            $relations         = $ciRelationDaoImpl->countCiRelations($ciId);
        } catch (Exception $e) {
            $this->logger->log('EXCEPTION checkdelete for ci ' . $ciId, Zend_Log::WARN);
            $this->logger->log($e);
            echo 'NOK';
            exit;
        }

        if ($relations['cnt'] > 0) {
            if (!$deleteRelations) {
                echo 'NOK';
                exit;
            }
            echo $relations['cnt'];
            exit;
        } else {
            // ready to delete
            echo 'OK';
            exit;
        }
    }


    /**
     * removes the given attribute id from create ci form
     *
     */
    public function removeattributeAction()
    {
        $attributeId = $this->_getParam('genId');
        $mark        = $this->_getParam('mark');
        $sessionId   = $this->_getParam('sessionID');


        $ciServiceCreate = new Service_Ci_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ciServiceCreate->removeAttributeSession($attributeId, $mark, $sessionId);
        exit; // required due to ajax request...
    }

    /**
     * copy the given ci
     */
    public function duplicateAction()
    {
        $ciId = $this->_getParam('ciid');
        // TODO: copy attachments to BASE folder


        $ciServiceGet = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        if (!$ciServiceGet->checkPermission($ciId, parent::getUserInformation()->getId())) {
            throw new Exception_AccessDenied();
        }

        $config         = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $useDefaultPath = $config->file->upload->path->default;
        $defaultFolder  = $config->file->upload->path->folder;

        $path = "";
        if ($useDefaultPath) {
            $path = APPLICATION_PUBLIC . $defaultFolder;
        } else {
            $path = $config->file->upload->path->custom;
        }

        $enabled     = $config->file->upload->attachment->enabled;
        $folder      = $config->file->upload->attachment->folder;
        $maxfilesize = $config->file->upload->attachment->maxfilesize;

        // setting default value
        if (!$enabled) {
            $enabled = false;
        }
        if (!$folder) {
            $folder = 'attachment';
        }

        $ciPath = $path . $folder .'/'. $ciId . '/';
        if (is_dir($ciPath)) {
            if ($dh = opendir($ciPath)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_file($ciPath .'/'. $file) && substr($file, 0, 1) != '.') {
                        if (!copy($ciPath . $file, $path . $folder .'/'. $file)) {
                            $this->logger->log("failed to copy $file...", Zend_Log::ERR);
                        }
                    }
                }
                closedir($dh);
            }
        }

        $this->_redirect('ci/create/validate/1/duplicate/1/isRefresh/1/ciid/' . $ciId);
    }

    /**
     * TODO: refactore me!!
     * used to cahnge the ci type of a given ci
     */
    public function changecitypeAction()
    {
        $ciId = $this->_getParam('ciid');

        if (is_null($ciId)) {
            throw new Exception_InvalidParameter();
        }

        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $breadCrumbs     = $ciServiceGet->getCiBreadcrumbs($ciId);
        $this->elementId = $this->view->ciBreadcrumb($breadCrumbs, 10, 'text', true);

        $adminView = false;
        if (Zend_Registry::get('adminMode') === true) {
            $adminView = true;
        }

        $ciDaoImpl = new Dao_Ci();
        $ci        = $ciDaoImpl->getCi($ciId);

        if (!$ciServiceGet->checkPermission($ciId, parent::getUserInformation()->getId())) {
            throw new Exception_AccessDenied();
        }

        $ciTypeDaoImpl = new Dao_CiType();
        $select        = $ciTypeDaoImpl->getRootCiTypeRowset();

        $ciServiceUpdate = new Service_Ci_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        // put the root ci data in useable content
        $rootCiTypes       = array();
        $rootCiTypes[null] = ' ';
        foreach ($select as $row) {
            if ($adminView === true) {
                $rootCiTypes[$row[Db_CiType::ID]] = $row[Db_CiType::NAME];
            } else {
                $rootCiTypes[$row[Db_CiType::ID]] = $row[Db_CiType::DESCRIPTION];
            }
        }

        $formdata = array();

        $form = new Form_Ci_ChangeCiType($this->translator, $rootCiTypes);

        if (!$this->_request->isPost()) {
            // handle db values
            $ciTypesToUse = array_reverse($ciTypeDaoImpl->retrieveCiTypeHierarchyByCiId($ciId));

            foreach ($ciTypesToUse as $key => $value) {
                $ciTypesToUse[$key] = $value[0];
            }

            $formdata['parentCiType'] = $ciTypesToUse[0];
        } else {
            // handle user input -> create form with form data
            $formdata     = $this->_request->getPost();
            $ciTypesToUse = array();

            $i       = 0;
            $hasMore = true;

            if ($formdata['parentCiType']) {
                $ciTypesToUse[0] = $formdata['parentCiType'];

                while ($hasMore) {
                    $var = 'child_' . $i;
                    if (!$formdata[$var]) {
                        $hasMore = false;
                        break;
                    }

                    $ciTypesToUse[$i + 1] = $formdata[$var];

                    $i++;
                }
            }


            // check form valid
            if ($form->isValid($formdata) && $formdata['save']) {
                // button is pressed!! hurray!! submit
                $childCounter = 0;
                $ciType       = null;

                while (true) {
                    $currentChild = 'child_' . $childCounter;

                    if (is_null($formdata[$currentChild]) || $formdata[$currentChild] == 0 || $formdata[$currentChild] == "") {
                        // value not found, so previous value was the last selected.
                        if ($childCounter == 0) {
                            // it's the parent
                            $ciType = $formdata['parentCiType'];
                        } else {
                            $childCounter--;
                            $currentChild = 'child_' . $childCounter;
                            $ciType       = $formdata[$currentChild];
                        }
                        break;
                    }
                    $childCounter++;
                }

                //don't do anything, if nothing changes
                if ($ci[Db_Ci::CI_TYPE_ID] != $ciType) {
                    $ciServiceUpdate->updateCiType(parent::getUserInformation()->getId(), $ciId, $ciType);
                }

                $notification['success'] = $this->translator->translate('ciTypeChangeSuccess');
                //for recreating the navigation tree
                parent::clearNavigationCache();
                parent::init();
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('ci/detail/ciid/' . $ciId);
            }
        }

        if ($ciTypesToUse && $ciTypesToUse[0]) {
            // handle the first child element
            $childs          = $ciTypeDaoImpl->retrieveCiTypeChildElements($ciTypesToUse[0]);
            $childList       = array();
            $childList[null] = ' ';
            foreach ($childs as $child) {
                if ($adminView === true) {
                    $childList[$child[Db_CiType::ID]] = $child[Db_CiType::NAME];
                } else {
                    $childList[$child[Db_CiType::ID]] = $child[Db_CiType::DESCRIPTION];
                }
            }
            if (count($childList)) {
                $form->addChild($childList, 0);
                $formdata['child_0'] = $ciTypesToUse[1];
                // handle all other child elements
                for ($i = 1; $i < count($ciTypesToUse) + 1; $i++) {
                    // every step represents another depth
                    $varToCheck = 'child_' . $i;
                    // so, first check if something is selected and if it matches with the previous selected value;
                    if (!is_null($ciTypesToUse[$i]) && $ciTypesToUse[$i] > 0) {
                        unset($childs);
                        unset($childList);
                        $childs = $ciTypeDaoImpl->retrieveCiTypeChildElements($ciTypesToUse[$i]);
                        foreach ($childs as $child) {
                            $childList[null] = ' ';
                            if ($adminView === true) {
                                $childList[$child[Db_CiType::ID]] = $child[Db_CiType::NAME];
                            } else {
                                $childList[$child[Db_CiType::ID]] = $child[Db_CiType::DESCRIPTION];
                            }
                        }
                        if (count($childList)) {
                            $form->addChild($childList, $i);
                            $formdata[$varToCheck] = $ciTypesToUse[$i + 1];
                        }
                    } else {
                        break;
                    }
                }
            }
        }

        $isCiAttach = true;
        $form->finalizeForm($isCiAttach);

        $this->view->isCiAttach = $isCiAttach;
        $form->populate($formdata);
        $this->view->form = $form;
        $this->view->ciid = $ciId;
    }


    public function colorAction()
    {
        $ciId  = $this->_getParam('ciid');
        $color = $this->_getParam('color');

        $delete = $this->_getParam('delete');
        $this->logger->log('change color; $ciId: ' . $ciId . '; $color: ' . $color, Zend_Log::INFO);

        try {
            $ciDaoImpl = new Dao_Ci();
            $highlight = $ciDaoImpl->updateColor(parent::getUserInformation()->getId(), $ciId, $color, $delete);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }
        echo "OK";
        exit;
    }

    public function autocompletecitypeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $attributeId = (int) $this->_getParam('attributeId');
        $filter      = $this->_getParam('name', '');
        $ciId        = (int) $this->_getParam('ciId', '');

        if (!$filter || $filter == '' || $filter == ' ') {
            $filter = null;
        }
        try {
            // first of all, check attribute!
            $daoAttribute = new Dao_Attribute();
            $attribute    = $daoAttribute->getSingleAttributeWithType($attributeId);


            // create new util
            $class     = Util_AttributeType_Factory::get($attribute[Db_AttributeType::NAME]);
            $selection = $class->getAutocompleteSelection($attributeId, $filter, $ciId, parent::getUserInformation()->getId());
            $limit     = 0;

            $newRes = array();
            foreach ($selection as $key => $val) {
                array_push($newRes, array('id' => $key, 'value' => $val));
                unset($selection[$key]);
                if ($limit == 50) {
                    array_push($newRes, array('value' => '...'));
                    break;
                }
                $limit++;
            }

        } catch (Exception $e) {
            $this->logger->log($e);
            $newRes = array();
        }


        echo json_encode($newRes);
        exit;
    }


    public function autocompletemultiselectAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $attributeId     = (int) $this->_getParam('attributeId');
        $filter          = $this->_getParam('q', null);
        $values_selected = $this->_getParam('values');
        $ciId            = (int) $this->_getParam('ciId', '');


        if (!$filter || $filter == '' || $filter == ' ') {
            $filter = null;
        }


        if (!$values_selected || $values_selected == '' || $values_selected == ' ' || isset($filter)) {
            $values_selected = null;
        }


        try {
            // first of all, check attribute!
            $daoAttribute = new Dao_Attribute();
            $attribute    = $daoAttribute->getSingleAttributeWithType($attributeId);


            // create new util
            $class     = Util_AttributeType_Factory::get($attribute[Db_AttributeType::NAME]);
            $selection = $class->getAutocompleteSelection($attributeId, $filter, $ciId, parent::getUserInformation()->getId());
            $limit     = 0;

            if (isset($values_selected)) {
                $values_selected         = explode(",", $values_selected);
                $cleared_values_selected = array();
                foreach ($values_selected as $key => $value) {
                    $cleared_values_selected[trim($key)] = trim($value);
                }
                $values_selected = $cleared_values_selected;

            }


            $result = '';
            foreach ($selection as $key => $val) {
                if (!in_array($key, is_array($values_selected) ? $values_selected : array()))
                    $result .= $key . "=" . $val . "\n";
                if ($limit == 50)
                    break;

                $limit++;

            }


            if (is_array($values_selected)) {


                foreach ($values_selected as $val) {

                    $valueParts = explode('::', $val);
                    $identifier = $valueParts[0];
                    $result     .= "s_" . $val . "=" . $selection[$identifier] . "\n";

                }


            }

            unset($selection);

        } catch (Exception $e) {
            $this->logger->log($e);

        }

        print $result;

        exit;
    }


    public function checkuniqueinputAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $attributeId = $this->_getParam('attributeId');
        $value       = (string)$this->_getParam('value');
        $ciid        = $this->_getParam('ciid');

        try {
            $daoCi    = new Dao_Ci();
            $isunique = $daoCi->checkUnique($value, $attributeId, $ciid);
        } catch (Exception $e) {
            $this->logger->log($e);
            $isunique = '';
        }

        echo json_encode($isunique);
        exit;
    }


    public function autocompleteattributeidAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $attributeId = $this->_getParam('attributeId');
        $ciId        = $this->_getParam('ciId');

        $this->logger->log('CriT ' . $attributeId . ' | ' . $ciId, Zend_Log::DEBUG);
        $result = array();


        if (!$attributeId || !$ciId) {

        } else {

            $daoAttribute = new Dao_Attribute();
            $attribute    = $daoAttribute->getSingleAttributeWithType($attributeId);


            // create new util
            $class = Util_AttributeType_Factory::get($attribute[Db_AttributeType::NAME]);
            $val   = $class->getAutocompleteValue($attributeId, $ciId);

            $result['id']    = $ciId;
            $result['value'] = $val;
        }

        $this->logger->log('return ' . $result['id'] . ' | ' . $result['value'], Zend_Log::DEBUG);

        echo json_encode($result);
        exit;
    }

    public function destroysessionAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $sessionID = $this->_getParam('sessionID');

        $this->destroyCiSession($sessionID);

    }

}
