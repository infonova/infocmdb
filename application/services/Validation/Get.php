<?php

/**
 *
 *
 *
 */
class Service_Validation_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3301, $themeId);
    }

    public function getImportFileList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $this->logger->log("Service_Validation: getImportFileAttributeList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);
        try {
            $config            = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/validation.ini', APPLICATION_ENV);
            $itemsCountPerPage = $config->pagination->itemsCountPerPage;
            $itemsPerPage      = $config->pagination->itemsPerPage;
            $scrollingStyle    = $config->pagination->scrollingStyle;
            $scrollingControl  = $config->pagination->scrollingControl;

            if (is_null($page)) {
                $this->logger->log('Service_Validation: getImportFileList page var was null. using default value 1 for user display', Zend_Log::DEBUG);
                $page = 1;
            }

            $validationDaoImpl = new Dao_Validation();
            $select            = $validationDaoImpl->getImportFiles();

            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage($itemsCountPerPage);

            Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
            Zend_View_Helper_PaginationControl::setDefaultViewPartial($scrollingControl);

        } catch (Exception $e) {
            throw new Exception_Validation_RetrieveIndexListFailed($e);
        }

        return $paginator;
    }


    public function getValidationFileDetailUpdate($validationId, $userId = '0', $page = null, $orderBy = null, $direction = null)
    {
        try {
            $config            = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/validation.ini', APPLICATION_ENV);
            $itemsCountPerPage = $config->pagination->itemsCountPerPage;
            $itemsPerPage      = $config->pagination->itemsPerPage;
            $scrollingStyle    = $config->pagination->scrollingStyle;
            $scrollingControl  = $config->pagination->scrollingControl;

            if ($page) {
                $limit_from = $itemsCountPerPage * ($page - 1);
            } else {
                $limit_from = 0;
            }


            $validationDaoImpl = new Dao_Validation();

            // count first
            $count      = $validationDaoImpl->getValidationDetailForPagination($validationId, true);
            $numberRows = $count[0]['cnt'];

            $select       = $validationDaoImpl->getValidationDetailForPagination($validationId, false, $limit_from, $itemsCountPerPage, $orderBy, $direction);
            $serviceCiGet = new Service_Ci_Get($this->translator, $this->logger, parent::getThemeId());

            $resList = array();
            foreach ($select as $res) {
                $resItem = $res;

                $attributeList                                     = array();
                $attributeList[0][Db_Attribute::ID]                = $res[Db_ImportFileValidationAttributes::ATTRIBUTE_ID];
                $attributeList[0][Db_Attribute::NAME]              = $res[Db_Attribute::NAME];
                $attributeList[0][Db_Attribute::ATTRIBUTE_TYPE_ID] = $res[Db_Attribute::ATTRIBUTE_TYPE_ID];

                $ciList = array();

                $preparedOutput = null;
                if (isset($res[Db_CiAttribute::VALUE_TEXT])) {
                    $preparedOutput = nl2br($res[Db_CiAttribute::VALUE_TEXT]);
                } else if (isset($res[Db_CiAttribute::VALUE_DATE])) {
                    $preparedOutput = $res[Db_CiAttribute::VALUE_DATE];
                } else if (isset($res[Db_CiAttribute::VALUE_DEFAULT])) {
                    $preparedOutput = nl2br($res[Db_CiAttribute::VALUE_DEFAULT]);
                } else if (isset($res[Db_CiAttribute::VALUE_CI])) {
                    $preparedOutput = nl2br($res[Db_CiAttribute::VALUE_CI]);
                }

                $ciList[0]                           = array(); // current item
                $ciList[0][$res[Db_Attribute::NAME]] = $preparedOutput;
                $ciList[0]['id']                     = $res[Db_ImportFileValidationAttributes::CI_ID];


                $ciList[1]                           = array(); // new item
                $ciList[1][$res[Db_Attribute::NAME]] = $res[Db_ImportFileValidationAttributes::VALUE];

                $item = $serviceCiGet->decodeCiAttributes($ciList, $attributeList, array( Util_AttributeType_Type_Input::ATTRIBUTE_TYPE_ID   => 'exclude' ));

                $item_current = $item[0][$res[Db_Attribute::NAME]];
                $item_new     = $item[1][$res[Db_Attribute::NAME]];

                unset($resItem[Db_CiAttribute::VALUE_TEXT]);
                unset($resItem[Db_CiAttribute::VALUE_CI]);
                unset($resItem[Db_CiAttribute::VALUE_DATE]);
                unset($resItem[Db_CiAttribute::VALUE_DEFAULT]);

                $resItem[Db_ImportFileValidationAttributes::VALUE] = $item_new;
                $resItem[Db_CiAttribute::VALUE_TEXT]               = $item_current;
                array_push($resList, $resItem);
            }

            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($numberRows));

            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage($itemsCountPerPage);

            Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
            Zend_View_Helper_PaginationControl::setDefaultViewPartial($scrollingControl);

        } catch (Exception $e) {
            throw new Exception_Validation_RetrieveDetailListFailed($e);
        }
        return array('paginator' => $paginator, 'ciList' => $resList);
    }


    public function getValidationFileDetailInsert($validationId, $userId, $page = null, $orderBy = null, $direction = null)
    {
        try {
            $config            = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/validation.ini', APPLICATION_ENV);
            $itemsCountPerPage = $config->pagination->itemsCountPerPage;
            $itemsPerPage      = $config->pagination->itemsPerPage;
            $scrollingStyle    = $config->pagination->scrollingStyle;
            $scrollingControl  = $config->pagination->scrollingControl;

            $validationDaoImpl = new Dao_Validation();

            if (is_null($page)) {
                $page = 1;
            }

            // get page
            $ciList = $validationDaoImpl->getValidationAttributesGroupByCiId($validationId);

            if ($page) {
                $limit_from = $itemsCountPerPage * ($page - 1);
            } else {
                $limit_from = 0;
            }

            $numberRows = count($ciList);
            if ($numberRows > 0) {
                $limit_to = $limit_from + $itemsCountPerPage;
                for ($i = 0; $i < $numberRows; $i++) {
                    if ($i < $limit_from || $i >= $limit_to) {
                        unset($ciList[$i]);
                    }
                }


                // now handle fricking rest
                $ciTypeList = array();
                // gather ciTypes
                foreach ($ciList as $ci) {
                    $ciTypeList[$ci[Db_ImportFileValidation::CI_TYPE_ID]] = array();
                }


                $typeAttributeList = array();
                $attributeDaoImpl  = new Dao_Attribute();
                foreach ($ciTypeList as $ciType => $val) {
                    $attributeList              = $attributeDaoImpl->getAttributesByTypeId($ciType, $this->getThemeId(), $userId);
                    $typeAttributeList[$ciType] = $attributeList;
                }

                $serviceCiGet = new Service_Ci_Get($this->translator, $this->logger, parent::getThemeId());
                $projectDao   = new Dao_Project();
                $ciTypeDao    = new Dao_CiType();

                $resList = array();
                foreach ($ciList as $ci) {
                    // get final list
                    $cType = $ci[Db_ImportFileValidation::CI_TYPE_ID];
                    if (!$resList[$cType])
                        $resList[$cType] = array();

                    $ciAttList = $typeAttributeList[$cType];
                    $item      = $validationDaoImpl->getInsertList($validationId, $ci[Db_ImportFileValidationAttributes::CI_ID], $ciAttList);

                    /* resolving project_id and ci_type_id to name */
                    $project                                             = $projectDao->getProject($item[Db_ImportFileValidationAttributes::PROJECT_ID]);
                    $item[Db_ImportFileValidationAttributes::PROJECT_ID] = $project[Db_Project::NAME];

                    $ciType                                              = $ciTypeDao->getCiType($item[Db_ImportFileValidationAttributes::CI_TYPE_ID]);
                    $item[Db_ImportFileValidationAttributes::CI_TYPE_ID] = $ciType[Db_CiType::NAME];
                    /**/
                    $t    = array();
                    $t[0] = $item;
                    $item = $serviceCiGet->decodeCiAttributes($t, $ciAttList, array( Util_AttributeType_Type_Input::ATTRIBUTE_TYPE_ID   => 'exclude' ));
                    $item = $item[0];
                    // ad to parent
                    array_push($resList[$cType], $item);
                }
            }

            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($numberRows));
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage($itemsCountPerPage);

            Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
            Zend_View_Helper_PaginationControl::setDefaultViewPartial($scrollingControl);

        } catch (Exception $e) {
            throw new Exception_Validation_RetrieveDetailListFailed($e);
        }
        return array(
            'paginator'     => $paginator,
            'attributeList' => $typeAttributeList,
            'ciList'        => $resList,
        );
    }

    public function checkAllAttributesValidated($validationId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();
            $attributes        = $validationDaoImpl->getValidationAttribtuesCheck($validationId);

            if (count($attributes) > 0) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            throw new Exception_Validation_AttributeCheckError($e);
        }
    }

    public function getImportFileAttributesList($validationId, $newCiNr = null)
    {
        $this->logger->log("Service_Validation: getImportFileAttributeList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $validationDaoImpl = new Dao_Validation();
        if ($newCiNr)
            return $validationDaoImpl->getImportFileInsertAttributesByValidationId($validationId, $newCiNr);
        else
            return $validationDaoImpl->getImportFileAttributesByValidationId($validationId);
    }


    public function getImportFileNewCiList($validationId)
    {
        $this->logger->log("Service_Validation: getImportFileAttributeList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $validationDaoImpl = new Dao_Validation();
        return $validationDaoImpl->getImportFileNewCisByValidationId($validationId);
    }

    public function getImportFile($validationId)
    {
        $this->logger->log("Service_Validation: getImportFileAttributeList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $validationDaoImpl = new Dao_Validation();
        return $validationDaoImpl->getImportFileByValidationId($validationId);
    }

}