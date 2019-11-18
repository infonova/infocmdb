<?php

/**
 *
 *
 *
 */
class Service_Ci_Project extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 311, $themeId);
    }


    public function getCiProjectMappingForm($userId, $ciId)
    {
        try {

            // select zugewiesene Projekte
            $projectDao  = new Dao_Project();
            $userDao     = new Dao_User();
            $projectList = $projectDao->getProjectRowset();
            $accessList  = array();

            foreach ($projectList as $project) {
                if ($userDao->getUserProjectMapping($userId, $project[Db_Project::ID]))
                    array_push($accessList, $project);
            }

            $selectedProjectList = $projectDao->getProjectsByCiId($ciId);
            $form                = new Form_Ci_Project($this->translator, $accessList, $selectedProjectList, null);

            return array(
                'form'                => $form,
                'permittedList'       => $accessList,
                'selectedProjectList' => $selectedProjectList,
            );
        } catch (Exception $e) {
            throw new Exception_Ci_Unknown($e);
        }
    }


    public function updateCiProject($userId, $ciId, $projectList, $values, $selectedProjectList)
    {
        try {
            // delete deselected and add new selected
            $ciProjectDaoImpl = new Dao_CiProject();
            $triggerUtil      = new Util_Trigger($this->logger);
            $daoHistory       = new Dao_History();

            $historyId = null;

            foreach ($projectList as $project) {
                $found = false;
                foreach ($selectedProjectList as $selected) {
                    if ($selected[Db_Project::ID] == $project[Db_Project::ID]) {
                        if (!$values[$project[Db_Project::ID]]) {
                            // needs to be deleted

                            // historization
                            if (!$historyId)
                                $historyId = $daoHistory->createHistory($userId, Enum_History::CI_PROJECTS_EDIT);

                            $ciProjectId = $ciProjectDaoImpl->getCiProject($ciId, $project[Db_Project::ID]);

                            // delete
                            $ciProjectDaoImpl->deleteCiProject($ciProjectId, $historyId);

                            // customization handling
                            $triggerUtil->deleteProject($ciProjectId, $userId);
                        } else {
                            // is already configured! nothing to do
                        }
                        // value is already configured. no interaction required
                        $found = true;
                    }
                }


                if (!$found && $values[$project[Db_Project::ID]]) {
                    // insert

                    if (!$historyId)
                        $historyId = $daoHistory->createHistory($userId, Enum_History::CI_PROJECTS_EDIT);

                    $ciProjectId = $ciProjectDaoImpl->insertCiProject($ciId, $project[Db_Project::ID], $historyId);

                    // customization handling
                    $triggerUtil->createProject($ciProjectId, $userId);
                }
            }
        } catch (Exception $e) {
            $this->logger - log($e, Zend_Log::ERR);
            throw new Exception_Ci_Unknown($e);
        }
    }
}