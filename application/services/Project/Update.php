<?php

/**
 *
 *
 *
 */
class Service_Project_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1903, $themeId);
    }


    /**
     * retrieves Form for Project Update
     */
    public function getUpdateProjectForm($users)
    {
        $projectConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/project.ini', APPLICATION_ENV);
        $form          = new Form_Project_Update($this->translator, $projectConfig);

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
     * updates a View Type by the given View Type Id and values
     *
     * @param int   $projectId
     * @param array $project
     *
     * @throws Exception_Project_UpdateItemNotFound if no items are updated
     * @throws Exception_Project_UpdateFailed on all other errors
     */
    public function updateProject($projectId, $formData, $dbData)
    {
        try {
            $dbUpdate = false;

            foreach ($formData as $key => $value) {
                if ($formData[$key] != $dbData[$key])
                    $updateData[$key] = $value;
            }

            $project = array();
            if ($updateData['name'] !== null)
                trim($project[Db_Project::NAME] = $updateData['name']);
            if ($updateData['description'] !== null)
                $project[Db_Project::DESCRIPTION] = trim($updateData['description']);
            if ($updateData['note'] !== null)
                $project[Db_Project::NOTE] = trim($updateData['note']);
            if ($updateData['order'] !== null)
                $project[Db_Project::ORDER_NUMBER] = $updateData['order'];

            if (!empty($project)) {
                $projectDaoImpl = new Dao_Project();
                $rows           = $projectDaoImpl->updateProject($project, $projectId);
                $dbUpdate       = true;
            }

            $mapping = $updateData;
            unset($mapping['name']);
            unset($mapping['description']);
            unset($mapping['note']);
            unset($mapping['order']);

            $users       = $this->getUsers();
            $userDaoImpl = new Dao_User();
            foreach ($users as $user) {
                if ($mapping[$user[Db_User::ID]] === '1') {
                    $userDaoImpl->insertUserProjectMapping($user[Db_User::ID], $projectId);
                    $dbUpdate = true;
                } elseif ($mapping[$user[Db_User::ID]] === '0' && $dbData[$user[Db_User::ID]]) {
                    $userDaoImpl->deleteUserProjectMapping($user[Db_User::ID], $projectId);
                    $dbUpdate = true;
                }
            }
            return true;
        } catch (Exception_Project $e) {
            throw new Exception_Project_UpdateItemNotFound($e);
        } catch (Exception $e) {
            if (!($e instanceof Exception_Project))
                throw new Exception_Project_UpdateFailed($e);
        }
    }
}