<?php

/**
 *
 *
 *
 */
class Service_Favourites_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2901, $themeId);
    }

    public function getFilterForm($userId)
    {
        $ciDao  = new Dao_Ci();
        $groups = $ciDao->getCurrentFavouriteGroups($userId);
        return new Form_Favourites_Filter($this->translator, $groups);
    }


    public function getFavouriteCiList($group = 'default', $userId, $themeId, $projectId, $page = null, $orderBy = null, $direction = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/ci.ini', APPLICATION_ENV);


        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;


        $ciDao  = new Dao_Ci();
        $ciList = $ciDao->getFavouriteCiByGroup($group, $userId);

        $numberRows = count($ciList);
        if (!$ciList)
            return null;


        //select attributes for the given ci*s
        $listOfCiTypes = array();

        $currentCi = null;

        if ($ciList)
            foreach ($ciList as $ci) {
                if (!$currentCi) {
                    $currentCi                    = array();
                    $currentCi[Db_Ci::CI_TYPE_ID] = $ci[Db_Ci::CI_TYPE_ID];
                    array_push($currentCi, $ci);

                } else if ($currentCi[Db_Ci::CI_TYPE_ID] != $ci[Db_Ci::CI_TYPE_ID]) {
                    // create new ciType
                    array_push($listOfCiTypes, $currentCi);
                    $currentCi                    = array();
                    $currentCi[Db_Ci::CI_TYPE_ID] = $ci[Db_Ci::CI_TYPE_ID];
                    array_push($currentCi, $ci);

                } else {
                    array_push($currentCi, $ci);
                }
            }
        array_push($listOfCiTypes, $currentCi);
        unset($ciList);

        $attributeDao = new Dao_Attribute();


        $newValueList = array();
        foreach ($listOfCiTypes as $ciTypeList) {

            // make joined to ci_type_attribute table
            $attributeList   = $attributeDao->getAttributesByTypeId($ciTypeList[Db_Ci::CI_TYPE_ID], $themeId, $userId);
            $attributesToUse = '';

            foreach ($attributeList as $attribute) {
                $attributesToUse = $attributesToUse . $attribute[Db_Attribute::ID] . ', ';
            }
            $attributesToUse = $attributesToUse . '0';

            // retrieve all ci's of the given type and project id(optional)
            $searchDaoImpl = new Dao_Search();


            $ciList    = null;
            $newCiList = array();
            // select attributes for each ci
            $i = 0;
            while ($ciTypeList[$i]) {

                if ($ciList) {
                    $ciList = $ciList . ', ' . $ciTypeList[$i][Db_CiHighlight::CI_ID];
                } else {
                    $ciList = $ciTypeList[$i][Db_CiHighlight::CI_ID];
                }
                $i++;
            }

            $newCiList = $searchDaoImpl->getAtrributeValuesForCi($ciList, $ciTypeList[Db_Ci::CI_TYPE_ID], $attributeList, $projectId);

            $temp               = array();
            $temp['attribList'] = $attributeList;
            $temp['ciList']     = $newCiList;

            $newValueList[$ciTypeList[Db_Ci::CI_TYPE_ID]] = $temp;

        }

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($numberRows));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return array(
            'ciList'       => $newValueList,
            'paginator'    => $paginator,
            'numberOfRows' => $numberRows,
        );
    }
}