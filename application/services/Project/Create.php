<?php

/**
 *
 *
 *
 */
class Service_Project_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1902, $themeId);
    }


    /**
     * retrieves Form for Project Create
     */
    public function getCreateProjectForm($users)
    {
        $projectConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/project.ini', APPLICATION_ENV);
        $form          = new Form_Project_Create($this->translator, $projectConfig);

        foreach ($users as $user) {
            $form->addUser($user[Db_User::ID], $user[Db_User::USERNAME], $user[Db_User::DESCRIPTION]);
        }

        return $form;
    }

    public function getUsers()
    {
        $userDaoImpl = new Dao_User();
        return $userDaoImpl->getUsers();
    }


    /**
     * creates a Project by the given values
     *
     * @param array $values
     */
    public function createProject($formData, $userId)
    {
        try {
            $project                           = array();
            $project[Db_Project::NAME]         = trim($formData['name']);
            $project[Db_Project::DESCRIPTION]  = trim($formData['description']);
            $project[Db_Project::NOTE]         = trim($formData['note']);
            $project[Db_Project::ORDER_NUMBER] = $formData['order'];
            $project[Db_Project::IS_ACTIVE]    = '1';
            $project[Db_Project::USER_ID]      = $userId;

            $projectDaoImpl = new Dao_Project();
            $projectId      = $projectDaoImpl->insertProject($project);

            if (!$projectId) {
                throw new Exception();
            } else {
                try {
                    $mapping = $formData;
                    unset($mapping['name']);
                    unset($mapping['description']);
                    unset($mapping['note']);
                    unset($mapping['order']);

                    $userDaoImpl = new Dao_User();
                    foreach ($mapping as $id => $value) {
                        if ($value)
                            $userDaoImpl->insertUserProjectMapping($id, $projectId);
                    }
                } catch (Exception $e) {
                    throw new Exception_Project_UserInsertFailed($e);
                }

                return $projectId;
            }
        } catch (Exception $e) {
            throw new Exception_Project_InsertFailed($e);
        }
    }
}