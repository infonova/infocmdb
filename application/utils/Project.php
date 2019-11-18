<?php

class Util_Project
{

    /**
     * returns a list of all projects available for the given user
     *
     * @param unknown_type $translator
     * @param unknown_type $userDto
     *
     * @return unknown_type
     */
    public static function createProjectList($translator, $userDto)
    {
        $projectList = null;

        // retrieve a list of active projects for the given user
        $projectDao  = new Dao_Project();
        $projectList = $projectDao->getProjectsByUserId($userDto->getId(), true);

        $newArray = array();
        if (!$projectList) {
            $no_projects                          = array();
            $no_projects[Db_Project::ID]          = 0;
            $no_projects[Db_Project::DESCRIPTION] = $translator->_('projectNone');
            $no_projects[Db_Project::NOTE]        = $translator->_('projectNone');
            $no_projects[Db_Project::IS_ACTIVE]   = '1';
            $newArray[0]                          = $no_projects;
        } elseif (count($projectList) > 1) {
            $atp                          = array();
            $atp[Db_Project::ID]          = 0;
            $atp[Db_Project::DESCRIPTION] = $translator->_('projectAll');
            $atp[Db_Project::NOTE]        = $translator->_('projectAll');
            $atp[Db_Project::IS_ACTIVE]   = '1';
            $newArray[0]                  = $atp;
        }
        if ($projectList)
            $newArray = array_merge($newArray, $projectList);

        $projectList = $newArray;
        unset($newArray);

        return $projectList;
    }

}