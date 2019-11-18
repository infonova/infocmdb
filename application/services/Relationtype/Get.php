<?php

/**
 *
 *
 *
 */
class Service_Relationtype_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2801, $themeId);
    }


    /**
     * retrieves a list of relation type entries by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getRelationTypeList($page, $orderBy = null, $direction = null, $filter = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/relationtype.ini', APPLICATION_ENV);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['relationtype'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        $form            = new Form_Filter($this->translator);
        $relationDaoImpl = new Dao_CiRelation();

        if (is_null($page)) {
            $this->logger->log('Service_Relation getRelationTypeList page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        if ($filter) {
            $select                = $relationDaoImpl->getRelationsForPaginationWithFilter($filter, $orderBy, $direction);
            $filterArray           = array();
            $filterArray['search'] = $filter;
            $form->populate($filterArray);
        } else {
            $select = $relationDaoImpl->getRelationsForPagination($orderBy, $direction);
        }

        unset($relationDaoImpl);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $result               = array();
        $result['searchForm'] = $form;
        $result['paginator']  = $paginator;
        return $result;
    }

    public function getCurrentCiTypes($relationTypeId)
    {
        $relationDaoImpl = new Dao_CiRelation();

        return $relationDaoImpl->getCiTypesByRelationTypeId($relationTypeId);
    }

    /**
     * get single relation type by id
     *
     * @param $relationTypeId
     */
    public function getRelationType($relationTypeId)
    {
        $relationDaoImpl = new Dao_CiRelation();
        return $relationDaoImpl->getRelation($relationTypeId);
    }

    /**
     * retrieves all necessary Data for a relationtype
     *
     * @param int $projectId
     */
    public function getRelationTypeData($relationTypeId)
    {
        try {
            $relationDaoImpl = new Dao_CiRelation();
            $relation        = $relationDaoImpl->getRelation($relationTypeId);

            if (!$relation) {
                throw new Exception_Relation_RetrieveNotFound();
            }

            $dbFormData                 = array();
            $dbFormData['name']         = trim($relation[Db_CiRelationType::NAME]);
            $dbFormData['description']  = trim($relation[Db_CiRelationType::DESCRIPTION]);
            $dbFormData['description2'] = trim($relation[Db_CiRelationType::DESCRIPTION_OPTIONAL]);
            $dbFormData['note']         = trim($relation[Db_CiRelationType::NOTE]);
            $dbFormData['color']        = $relation[Db_CiRelationType::COLOR];
            $dbFormData['visualize']    = $relation[Db_CiRelationType::VISUALIZE];

            $mapping = $relationDaoImpl->getCiTypesByRelationTypeId($relationTypeId);
            if ($mapping) {
                foreach ($mapping as $ciType)
                    $dbFormData[$ciType[Db_CiType::ID]] = true;
            }
            return $dbFormData;
        } catch (Exception $e) {
            if ($e instanceof Exception_Project)
                throw $e;
            throw new Exception_Relation_RetrieveFailed($e);
        }
    }


}