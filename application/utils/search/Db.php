<?php

class Util_Search_Db
{

    private $userDto         = null;
    private $projectId       = null;
    private $searchMethod    = null;
    private $searchParameter = null;

    public function __construct($userDto, $projectId, $searchMethod = null, $searchParameter = array())
    {
        $this->userDto         = $userDto;
        $this->projectId       = $projectId;
        $this->searchMethod    = $searchMethod;
        $this->searchParameter = $searchParameter;
    }


    public function search($values = null, $history = null)
    {
        $searchstring = $values['searchstring'];
        $page         = $values['page'];
        $session      = $values['session'];

        if (!$page) {
            $page = 1;
        }

        if (is_null($searchstring) || $searchstring == ' ' || $searchstring == '') {
            return array();
        }

        $searchstring = trim($searchstring);
        $result       = $this->searchDataBase($searchstring, $page, $session, $history);


        if (is_null($result['items']) || count($result['items']) < 1) {
            $result['items'] = array();
            return $result;
        }


        //select attributes for the given ci*s
        $listOfCiTypes = array();

        $currentCi = null;
        foreach ($result['items'] as $ci) {
            if (!$currentCi) {
                $currentCi              = array();
                $currentCi['citype_id'] = $ci['citype_id'];
                array_push($currentCi, $ci);

            } else if ($currentCi['citype_id'] != $ci['citype_id']) {
                // create new ciType
                array_push($listOfCiTypes, $currentCi);
                $currentCi                = array();
                $currentCi['citype_id']   = $ci['citype_id'];
                $currentCi['citype_name'] = $ci['citype_name'];
                array_push($currentCi, $ci);

            } else {
                array_push($currentCi, $ci);
            }
        }
        array_push($listOfCiTypes, $currentCi);
        unset($result['items']);

        $attributeDao = new Dao_Attribute();

        $serviceCiGet = new Service_Ci_Get(Zend_Registry::get('Zend_Translate'), Zend_Registry::get('Log'), $this->userDto->getThemeId());

        $newValueList = array();
        $ciTypeDao    = new Dao_CiType();
        foreach ($listOfCiTypes as $k => $ciTypeList) {
            $ciType = $ciTypeDao->getCiType($ciTypeList['citype_id']);

            // make joined to ci_type_attribute table
            $attributeList   = $attributeDao->getAttributesByTypeId($ciTypeList['citype_id'], $this->userDto->getThemeId(), $this->userDto->getId());
            $attributesToUse = '';

            foreach ($attributeList as $attribute) {
                $attributesToUse = $attributesToUse . $attribute[Db_Attribute::ID] . ', ';
            }
            $attributesToUse = $attributesToUse . '0';

            // retrieve all ci's of the given type and project id(optional)
            $searchDaoImpl = new Dao_Search();


            $newCiList = array();
            $ciArray   = array();
            // select attributes for each ci
            $i = 0;
            while ($ciTypeList[$i]) {
                array_push($ciArray, array('id' => $ciTypeList[$i]['ci_id']));

                $i++;
            }

            $newCiList = $serviceCiGet->getListResultForCiList($attributeList, $ciArray, null, null, null, null, $history);

            $temp                       = array();
            $temp['citype_name']        = $ciType[Db_CiType::NAME];
            $temp['citype_description'] = $ciType[Db_CiType::DESCRIPTION];
            $temp['attribList']         = $attributeList;
            $temp['ciList']             = $newCiList;


            $newValueList[$ciTypeList['citype_id']] = $temp;
        }


        $result['items']   = $newValueList;
        $result['history'] = $history;
        return $result;
    }


    private function searchDataBase($searchstr, $page, $session, $history)
    {
        $searchstring      = trim($searchstr);
        $searchstring      = str_replace("'", "\"", $searchstring);
        $search_components = $this->parseSearchString($searchstring);

        $cis = $this->getSearchResults($search_components, $page, $session, $history);
        return $cis;
    }


    private function parseSearchString($searchstring)
    {
        $searchstring      = preg_replace('/(^[\*\%]$|^([\*\%]+)[\s]+|[\s]+([\*\%]+)[\s]+|[\s]+([\*\%]+)$)/', '*', $searchstring);
        $length            = strlen($searchstring);
        $search_components = array("value" => array(), "action" => array());

        $isEncapsulated  = false;
        $currentPosition = 0;
        for ($i = 0; $i < $length; $i++) {

            if ($searchstring[$i] == "\"") {
                if ($isEncapsulated) {
                    $isEncapsulated = false;
                } else {
                    $isEncapsulated = true;
                }
            }

            if ($isEncapsulated && $searchstring[$i] != "\"") {
                $search_components['value'][$currentPosition]  .= $searchstring[$i];
                $search_components['action'][$currentPosition] = '+';
            } else if ($searchstring[$i] == "\"") {
                // do nothing
            } else {
                switch ($searchstring{$i}) {
                    case "+":
                        $currentPosition++;
                        $search_components['action'][$currentPosition] = '+';
                        break;
                    case "-":
                        $currentPosition++;
                        $search_components['action'][$currentPosition] = '-';
                        break;
                    case " ":
                        $currentPosition++;
                        $search_components['action'][$currentPosition] = '+';
                        break;
                    default:
                        $search_components['value'][$currentPosition] .= $searchstring[$i];
                        if (!$search_components['action'][$currentPosition]) {
                            $search_components['action'][$currentPosition] = '+';
                        }
                        break;
                }
            }
        }

        return $search_components;
    }


    private function getSearchResults($searchstring, $page, $session, $history)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/search.ini', APPLICATION_ENV);

        $number_search_rows = $config->pagination->itemsCountPerPage;
        $amountOfShownPages = $config->pagination->itemsPerPage;

        $current_page = $page;

        if (is_null($current_page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $current_page = 1;
        }


        $limit_from = $number_search_rows * ($current_page - 1);

        $this->num_results = 0;
        $wildcardallowed   = false;

        // get from user
        $themeDaoImpl = new Dao_Theme();
        $theme        = $themeDaoImpl->getThemeByUserId($this->userDto->getId());

        $wildcardallowed = $theme[Db_Theme::IS_WILDCARD_ENABLED];
        $searchDaoImpl   = new Dao_Search();

        // unset!
        unset($theme);


        // check for wildcard search
        if ($wildcardallowed) {
            foreach ($searchstring['value'] as $key => $str) {
                $searchstring['value'][$key] = str_replace("*", "%", $str);
            }
        } else {
            $hasValue = false;

            foreach ($searchstring['value'] as $key => $str) {
                $str = str_replace("*", "", $str);
                $str = str_replace("%", "", $str);

                $searchstring['value'][$key] = $str;
                if ($searchstring['value'][$key] != "") {
                    $hasValue = true;
                } else {
                    unset($searchstring['value'][$key]);
                }
            }

            if (!$hasValue) {
                throw new Exception_Search_WildcardSearchNotAllowed();
            }
        }

        $pid_string = null;
        if (is_null($this->projectId)) {

            // select available projects
            $projectList = $searchDaoImpl->getProjects($this->userDto->getId());

            foreach ($projectList as $project) {
                if ($pid_string) {
                    $pid_string = $pid_string . "," . $project['project_id'];
                } else {
                    $pid_string = $project['project_id'];
                }
            }


        } else {
            $pid_string = $this->projectId;
        }
        unset($projectList);

        // TODO: enable if used time is needed
        $messungStart = strtok(microtime(), " ") + strtok(" ");

        $searchResult = array();

        if ($this->searchParameter['relation']) {
            if (count($searchstring['value']) == 1 && $searchstring['value'][0] == "%") {
                $searchResult = $searchDaoImpl->getAllCiIdsWithRelation($pid_string, $this->searchParameter['relation']);
            }
        } else {
            if (count($searchstring['value']) == 1 && $searchstring['value'][0] == "%") {
                $searchResult = $searchDaoImpl->getAllCiIds($pid_string);
            }
        }

        if (count($searchResult) < 1) {
            $config  = new Zend_Config_Ini(APPLICATION_PATH . '/configs/search.ini', APPLICATION_ENV);
            $speedup = $config->search->db->speedup->enabled;

            if ($speedup) {
                $searchResult = Util_Search_Method_Speedup::search($config, $searchstring, $pid_string, $this->searchParameter, $number_search_rows, $limit_from, $session, $history);
            } else {
                $searchResult = Util_Search_Method_Original::search($config, $searchstring, $pid_string, $this->searchParameter, $number_search_rows, $limit_from, $session, $history);
            }
        } else {
            // found other result -> fake search
            $res           = array();
            $res['number'] = count($searchResult);

            $limit_to = $limit_from + $number_search_rows;
            for ($i = 0; $i < $res['number']; $i++) {

                if ($i < $limit_from || $i >= $limit_to) {
                    unset($searchResult[$i]);
                }
            }

            $res['items'] = $searchResult;
            $searchResult = $res;
            unset($res);
        }

        $messungEnde = strtok(microtime(), " ") + strtok(" ");

        try {
            $logger  = Zend_Registry::get('Log');
            $sstring = "";

            if ($searchstring['value'])
                foreach ($searchstring['value'] as $key => $value) {
                    $sstring = $sstring . '' . $searchstring['action'][$key] . ' ' . $value;
                }
            $logger->log('Search for "' . $sstring . '" took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);
        } catch (Exception $e) {
            // TODO: do nothing?
        }

        // echo "<br>Dauer: ".number_format($messungEnde - $messungStart, 6)." Sekunden";

        $numberRows   = $searchResult['number'];
        $session      = $searchResult['session'];
        $searchResult = $searchResult['items'];

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/search.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($numberRows));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return array('searchstring' => $searchstring,
                     'items'        => $searchResult,
                     'numberRows'   => $numberRows,
                     'session'      => $session,
                     'paginator'    => $paginator,
                     'page'         => $current_page);
    }
}