<?php

/**
 *
 *
 *
 */
class Service_Project_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1904, $themeId);
    }


    /**
     * deletes a Project by the given Project Id
     *
     * @param $projectId the Project to delete
     *
     * @throws Exception_Project_DeleteFailed
     */
    public function deleteProject($projectId)
    {
        try {
            $projectDaoImpl   = new Dao_Project();
            $citypeDaoImpl    = new Dao_CiType();
            $ciprojectDaoImpl = new Dao_CiProject();

            $countCiTypeProjects = $citypeDaoImpl->getCountProjectsByProjectId($projectId);


            $statusCode = 0;
            if ($countCiTypeProjects['cnt'] > 0) {
                $rows       = $projectDaoImpl->deactivateProject($projectId);
                $statusCode = 2;
            } else {
                $rows       = $projectDaoImpl->deleteProject($projectId);
                $statusCode = 1;
            }
            if ($rows != 1) {
                throw new Exception();
                //$statusCode = 0;
            }
        } catch (Exception $e) {
            throw new Exception_Project_DeleteFailed($e);
        }
        return $statusCode;
    }

    /**
     * activate a Project by the given Project Id
     *
     * @param $projectId the Project to delete
     *
     * @throws Exception_Project_ActivationFailed
     */
    public function activateProject($projectId)
    {
        try {
            $projectDaoImpl  = new Dao_Project();
            $activateProject = $projectDaoImpl->activateProject($projectId);
        } catch (Exception $e) {
            throw new Exception_Project_ActivationFailed($e);
        }
    }

}