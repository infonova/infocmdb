<?php

/**
 *
 *
 *
 */
class Service_Project_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1901, $themeId);
    }


    /**
     * retrieves a list of Projects by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getProjectList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/project.ini', APPLICATION_ENV);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['project'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $projectDaoImpl = new Dao_Project();
        $select         = $projectDaoImpl->getProjectsForPagination($orderBy, $direction, $filter);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $result              = array();
        $result['paginator'] = $paginator;
        return $result;
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

    /**
     * retrieves all necessary Data for a project
     *
     * @param int $projectId
     */
    public function getProjectData($projectId)
    {
        try {
            $projectDaoImpl = new Dao_Project();
            $project        = $projectDaoImpl->getProject($projectId);

            if (!$project) {
                throw new Exception_Project_RetrieveNotFound();
            }
            $dbFormData                = array();
            $dbFormData['name']        = trim($project[Db_Project::NAME]);
            $dbFormData['valid']       = $project[Db_Project::IS_ACTIVE];
            $dbFormData['description'] = trim($project[Db_Project::DESCRIPTION]);
            $dbFormData['note']        = trim($project[Db_Project::NOTE]);
            $dbFormData['order']       = $project[Db_Project::ORDER_NUMBER];
            $dbFormData['active']      = $project[Db_Project::IS_ACTIVE];

            $projectDaoImpl = new Dao_Project();
            $userMapping    = $projectDaoImpl->getUserMappingByProjectId($projectId);
            if ($userMapping) {
                foreach ($userMapping as $mapping)
                    $dbFormData[$mapping[Db_UserProject::USER_ID]] = true;
            }
            return $dbFormData;
        } catch (Exception $e) {
            if ($e instanceof Exception_Project)
                throw $e;
            throw new Exception_Project_RetrieveFailed($e);
        }
    }

}