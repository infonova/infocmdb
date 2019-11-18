<?php
require_once 'AbstractAppAction.php';

/**
 * CONFIG ONLY!!
 *
 *
 */
class QueryController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/query_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/query_en.csv', 'en');
            parent::addUserTranslation('query');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function indexAction()
    {
        $page      = $this->_getParam('page');
        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $filter = null;
        if ($this->_hasParam('search')) {
            if (!$this->_getParam('search')) {
                $filterString = '';
            } else {
                $filterString = '/filter/' . $this->_getParam('search') . '/';
            }
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'query/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));
        }


        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $queryServiceGet = new Service_Query_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result          = $queryServiceGet->getQueryList($page, $orderBy, $direction, $filter);

        $this->view->page           = $page;
        $this->view->orderBy        = $orderBy;
        $this->view->direction      = $direction;
        $this->view->paginator      = $result['paginator'];
        $this->view->defaultQueries = $result['defaultQueries'];
        $this->view->filter         = $filter;
        $this->view->searchForm     = $result['searchForm']->setAction($this->view->url(array('filter' => null, 'page' => null)));
    }


    public function detailAction()
    {
        $queryId = $this->_getParam('queryId');

        $queryServiceGet = new Service_Query_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $res             = $queryServiceGet->getQueryDetail($queryId);
        $this->elementId = $res['query'][Db_StoredQuery::NAME];


        $this->view->query     = $res['query'];
        $this->view->parameter = $res['parameter'];
        $this->view->apiCall   = $res['apiCall'];
        $this->view->queryId   = $queryId;
    }

    // AJAX - test stored query
    public function testAction()
    {
        $queryId = $this->_getParam('queryId');
        $post    = $formData = $this->_request->getPost();
        $apiCall = $post['test'];

        $queryServiceGet = new Service_Query_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $res             = $queryServiceGet->testQuery($queryId, $apiCall, parent::getUserInformation()->getId());

        $result = $res['result'];
        $method = $res['method'];


        $notification           = array();
        $notification['status'] = 'OK';
        $notification['data']   = $result;
        $notification           = Util_Query::convertResult($notification, $method);

        if ($method && $method == 'plain') {
            echo $notification;
            exit;
        } else if (!$method || $method == 'xml') {
            $view = new Zend_View();
            echo $view->escape($notification);
            exit;
        }

        print_r($notification);
        exit;
    }

    // AJAX - test stored query create
    public function teststatementAction()
    {
        $post    = $formData = $this->_request->getPost();
        $apiCall = $post['test'];
        $method  = $post['method'];

        $queryServiceGet = new Service_Query_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result          = $queryServiceGet->testStatement($apiCall);


        $notification           = array();
        $notification['status'] = 'OK';
        $notification['data']   = $result;
        $notification           = Util_Query::convertResult($notification, $method);

        if ($method && $method == 'plain') {
            echo $notification;
            exit;
        } else if (!$method || $method == 'xml') {
            $view = new Zend_View();
            echo $view->escape($notification);
            exit;
        }

        print_r($notification);
        exit;
    }


    public function errorAction()
    {
        $queryId = $this->_getParam('queryId');

        $queryServiceGet = new Service_Query_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $query           = $queryServiceGet->getQuery($queryId);

        $this->view->query   = $query;
        $this->view->queryId = $queryId;
    }

    public function createAction()
    {
        $queryServiceCreate = new Service_Query_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form               = $queryServiceCreate->getCreateQueryForm();

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                try {
                    $queryId      = $queryServiceCreate->createQuery($form->getValues());
                    $notification = array('success' => $this->translator->translate('queryCreateSuccess'));

                } catch (Exception $e) {
                    $notification = array('error' => $this->translator->translate('queryCreateFailed'));
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect(APPLICATION_URL . 'query/index');
            }
        }

        $this->view->form = $form;
    }


    public function editAction()
    {
        $queryId         = $this->_getParam('queryId');
        $this->elementId = $queryId;

        try {
            $queryServiceUpdate = new Service_Query_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $ret                = $queryServiceUpdate->getUpdateQueryForm($queryId);
            $this->elementId    = $ret['formdata'][Db_StoredQuery::NAME];
        } catch (Exception_Query $e) {
            $notification = array('error' => $this->translator->translate('queryUpdateForbidden'));
            $this->_helper->FlashMessenger($notification);
            $this->_redirect(APPLICATION_URL . 'query/detail/queryId/' . $queryId);
        }

        $form           = $ret['form'];
        $storedFormData = $ret['formdata'];
        $query          = $ret['query'];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                try {
                    $queryServiceUpdate->updateQuery($queryId, $form->getValues());
                    $notification = array('success' => $this->translator->translate('queryUpdateSuccess'));

                } catch (Exception $e) {
                    $notification = array('error' => $this->translator->translate('queryUpdateFailed'));
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect(APPLICATION_URL . 'query/detail/queryId/' . $queryId);
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($storedFormData);
        }

        $this->view->query   = $query;
        $this->view->queryId = $queryId;
        $this->view->form    = $form;
    }

    public function deleteAction()
    {
        $queryId = $this->_getParam('queryId');

        try {
            $queryServiceDelete = new Service_Query_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $queryServiceDelete->deleteQuery($queryId);
            $notification = array('success' => $this->translator->translate('queryDeleteSuccess'));
        } catch (Exception $e) {
            $notification = array('error' => $this->translator->translate('queryDeleteFailed'));
        }
        $this->_helper->FlashMessenger($notification);
        $this->_redirect(APPLICATION_URL . 'query/index');
    }
}