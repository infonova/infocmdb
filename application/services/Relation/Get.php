<?php

/**
 *
 *
 *
 */
class Service_Relation_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2001, $themeId);
    }

    public function getDefaultAttributeName($ciId)
    {
        $ciDaoImpl        = new Dao_Ci();
        $defaultAttribute = $ciDaoImpl->getDefaultAttribute($ciId);

        if (isset($defaultAttribute[Db_CiAttribute::VALUE_TEXT])) {
            return $defaultAttribute[Db_CiAttribute::VALUE_TEXT];
        } else {
            return $ciId;
        }
    }

    /**
     *
     * @param array   $ciID
     *            of Ci for Graph
     * @param array   $projectList
     *            for currently selected project
     * @param int     $userId
     *            for project ids, if projectList is empty
     * @param int     $count
     *            the number of Cis beyond which the user is asked for
     *            conformation
     * @param boolean $confirm
     *            weather or not the user is asked for conformation
     *
     * @return array( 'ci' => $ciArray,'relation'=>$relationArray)
     */
    public function getCiRelationForGraph($ciID, $projectList, $userId, $count, $confirm)
    {
        if (empty($projectList)) {//gets projectslist for user if the given one is empty
            $projectDao   = new Dao_Project();
            $projectList  = $projectDao->getProjectsByUserId($userId);
            $project_list = "";
            foreach ($projectList as $p) {
                $project_list .= $p[Db_Project::ID] . ',';
            }

            $project_list = substr($project_list, 0, -1);
        } else {
            $project_list = $projectList;
        }

        $ciRelationDao = new Dao_CiRelation();
        return $ciRelationDao->getCiRelationForGraph($ciID, $project_list, $count, $confirm);
    }

    public function getRelationDetail($ciId, $ciRelationTypeId = null, $userId = null, $themeId = null, $ciResolveLimit = 5)
    {

        $projectDao    = new Dao_Project();
        $ciRelationDao = new Dao_CiRelation();
        $ciTypeDao     = new Dao_CiType();
        $attributeDao  = new Dao_Attribute();
        $ciServiceGet  = new Service_Ci_Get($this->translator, $this->logger, parent::getThemeId());


        $projectList = $projectDao->getProjectsByUserId($userId);
        $projectIds  = array();
        foreach ($projectList as $p) {
            $projectIds[] = $p[Db_Project::ID];
        }

        $projectIds = implode(',', $projectIds);


        $ciRelationRows = $ciRelationDao->getCiRelationsByCiId($ciId, $ciRelationTypeId, $projectIds);

        $ciRelationTypes = array();
        $ciTypes         = array();
        $relationData    = array('counter' => 0);
        foreach ($ciRelationRows as $ciRelationRow) {
            $ciRelationTypeId    = $ciRelationRow['ci_relation_type_id'];
            $foreignColumnNumber = ($ciRelationRow['ci_id_1'] == $ciId) ? 2 : 1;
            $foreignCiId         = $ciRelationRow['ci_id_' . $foreignColumnNumber];
            $ciTypeId            = $ciRelationRow['citypeId' . $foreignColumnNumber];

            if (!isset($ciRelationTypes[$ciRelationTypeId])) {
                $ciRelationTypes[$ciRelationTypeId] = $ciRelationDao->getRelation($ciRelationTypeId);
            }
            if (!isset($ciTypes[$ciTypeId])) {
                $breadcrumbs                      = $ciTypeDao->getBreadcrumbHierarchy($ciTypeId);
                $breadcrumbs                      = array_reverse($breadcrumbs);
                $ciTypes[$ciTypeId]               = $ciTypeDao->getCiType($ciTypeId);
                $ciTypes[$ciTypeId]['breadcrumb'] = $breadcrumbs;
                $ciTypes[$ciTypeId]['attributes'] = $attributeDao->getAttributesByTypeId($ciTypeId, $themeId, $userId);
            }

            if (!isset($relationData[$ciRelationTypeId])) {
                $relationData[$ciRelationTypeId]['counter'] = 0;
            }

            if (!isset($relationData[$ciRelationTypeId]['citypes'][$ciTypeId])) {
                $relationData[$ciRelationTypeId]['citypes'][$ciTypeId]['counter'] = 0;
            }

            $relationData['counter']++;
            $relationData[$ciRelationTypeId]['counter']++;
            $relationData[$ciRelationTypeId]['citypes'][$ciTypeId]['counter']++;

            $relationData[$ciRelationTypeId]['citypes'][$ciTypeId]['ciids'][] = $foreignCiId;

            if ($ciResolveLimit === null || $relationData[$ciRelationTypeId]['citypes'][$ciTypeId]['counter'] <= $ciResolveLimit) {
                $attributeList = $ciTypes[$ciTypeId]['attributes'];
                $resolvedCis   = $ciServiceGet->getListResultForCiList($attributeList, array(array('id' => $foreignCiId)));

                $relationData[$ciRelationTypeId]['citypes'][$ciTypeId]['ciData'][$foreignCiId]                       = $resolvedCis[0];
                $relationData[$ciRelationTypeId]['citypes'][$ciTypeId]['ciData'][$foreignCiId]['foreign_column']     = $foreignColumnNumber;
                $relationData[$ciRelationTypeId]['citypes'][$ciTypeId]['ciData'][$foreignCiId]['ci_relation_id']     = $ciRelationRow['relationId'];
                $relationData[$ciRelationTypeId]['citypes'][$ciTypeId]['ciData'][$foreignCiId]['relation_direction'] = $ciRelationRow[Db_CiRelation::DIRECTION];
            }

        }


        $result = array(
            'relations'       => $relationData,
            'ciTypes'         => $ciTypes,
            'ciRelationTypes' => $ciRelationTypes,
        );

        return $result;

    }


    public function getVisualizationList($page)
    {
        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/relation.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;

        $dir      = APPLICATION_PUBLIC . '/visualization/';
        $fileList = $this->getVisualizationFileList($dir);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($fileList));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial($scrollingControl);

        return $paginator;
    }

    private function getVisualizationFileList($dir)
    {
        $fileList = array();

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_file($dir . '/' . $file) && substr($file, 0, 1) != '.') {
                        $mod = date("Y-m-d, H:i:s", filemtime($dir . '/' . $file));
                        array_push($fileList,
                            array(
                                'name' => $file,
                                'time' => $mod,
                            ));
                    }
                }
                closedir($dh);
            }
        }

        return $fileList;
    }

    private function aasort(&$array, $key)
    {
        $sorter = array();
        $ret    = array();
        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii] = $va[$key];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }
        return $ret;
    }

    /**
     * @param $ciID
     */
    public function getMinimalRelationInfo($ciID)
    {
        $relDaoImpl = new Dao_CiRelation();

        $info = array();

        $relations  = $relDaoImpl->getRelationsForCi($ciID);
        $directions = $relDaoImpl->getDirections(Db_CiRelationDirection::ID);
        foreach ($relations as $relation) {
            $identifier                              = $relation['id'];
            $info[$identifier]                       = array();
            $info[$identifier]['ci_id_1']            = $relation['ci_id_1'];
            $info[$identifier]['ci_id_2']            = $relation['ci_id_2'];
            $info[$identifier]['relation_type_id']   = $relation['ci_relation_type_id'];
            $info[$identifier]['direction']          = $relation['direction'];
            $info[$identifier]['relation_type_name'] = $relDaoImpl->getRelationTypeById($relation['ci_relation_type_id'])['name'];
            $info[$identifier]['direction_name']     = $directions[$relation['direction']]['name'];
        }
        return $info;
    }
}