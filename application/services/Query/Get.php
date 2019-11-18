<?php

/**
 *
 *
 *
 */
class Service_Query_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3001, $themeId);
    }


    public function getQueryList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $this->logger->log("Service_Query: getQueryList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/query.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $form = new Form_Query_Filter($this->translator);
        $form->populate(array('search' => $filter));

        if ($filter) {
            $filterArray           = array();
            $filterArray['search'] = $filter;
            $form->populate($filterArray);
        }

        $queryDaoImpl   = new Dao_Query();
        $defaultQueries = $queryDaoImpl->getDefaultQuery($orderBy, $direction);

        $select = $queryDaoImpl->getQueryForPagination($orderBy, $direction, $filter);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $result['paginator']      = $paginator;
        $result['searchForm']     = $form;
        $result['defaultQueries'] = $defaultQueries;
        return $result;
    }

    public function testStatement($statement)
    {
        try {
            $queryDaoImpl = new Dao_Query();
            $result       = $queryDaoImpl->executeQuery($statement);

        } catch (Exception $e) {
            echo utf8_decode('<em style="color:red">' . $this->translator->translate('testQueryException') . '</em>');
            exit;
            echo $e;
            exit;
        }

        return $result;
    }


    public function testQuery($queryId, $apiCall, $userId = '0')
    {
        $method = null;
        $query  = $this->getQuery($queryId);

        $divorce = $query[Db_StoredQuery::NAME] . '/';
        $temp    = explode($divorce, $apiCall);

        $paramList = explode('/', $temp[1]);

        $paramValues = array();
        $isParam     = true;
        $lastParam   = "";
        foreach ($paramList as $p) {
            if ($isParam) {
                if ($p)
                    $paramValues[$p] = 'dummy';
                $lastParam = $p;
                $isParam   = false;
            } else {
                $paramValues[$lastParam] = $p;
                $isParam                 = true;
            }
        }

        if ($paramValues['method']) {
            $method = $paramValues['method'];
            unset($paramValues['method']);
        }

        $paramValues['user_id'] = $userId;

        $statement = $query[Db_StoredQuery::QUERY];

        try {
            $queryDaoImpl = new Dao_Query();
            $result       = $queryDaoImpl->executeQuery($statement, $paramValues);

            $this->updateQueryStatus($queryId, '1');

        } catch (Exception $e) {
            $this->updateQueryStatus($queryId, '0', $e);
            echo utf8_decode('<em style="color:red">' . $this->translator->translate('testQueryException') . '</em>');
            exit;
            echo $e;
            exit;
        }

        return array('method' => $method, 'result' => $result);
    }

    public function updateQueryStatus($queryId, $status = '1', $message = null)
    {
        $queryDaoImpl = new Dao_Query();
        $queryDaoImpl->updateQueryStatus($queryId, $status, $message);
    }

    public function getQuery($queryId)
    {
        $queryDaoImpl = new Dao_Query();
        return $queryDaoImpl->getQueryById($queryId);
    }


    public function getQueryDetail($queryId)
    {
        $query = $this->getQuery($queryId);

        if (!$query)
            return; // TODO:

        $statement = $query[Db_StoredQuery::QUERY];
        $concArray = explode(':', $statement);


        // get query parameter
        $parameter = array();
        foreach ($concArray as $conc) {
            #if there is a not allowed char in parameter-name, it's something else but not a param
            $patterns = array(' ', ';', ',', '-', '?', '!', '"', "'", '$', '%', '&', '/', '(', ')', '=', '<', '>', '|');
            $addItem  = true;
            foreach ($patterns as $pattern) {
                if (stripos($conc, $pattern) !== false) {
                    $addItem = false;
                }
            }
            if ($addItem == true) {
                array_push($parameter, $conc);
            }
        }

        // get API call
        $apiString = APPLICATION_URL . '/api/adapter/query/';
        $apiString .= $query[Db_StoredQuery::NAME] . '/method/json/';

        if ($parameter)
            foreach ($parameter as $param) {
                $apiString .= $param . '/?/';
            }


        return array(
            'query'     => $query,
            'parameter' => $parameter,
            'apiCall'   => $apiString,
        );
    }
}