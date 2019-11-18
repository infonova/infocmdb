<?php

/**
 *
 *
 *
 */
class Service_Workflow_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3603, $themeId);
    }


    /**
     * retrieves Form for Workflow Update
     */
    public function getUpdateWorkflowForm()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);
        return new Form_Workflow_Create($this->translator, $this->getAllUsers(), $config);
    }


    private function getAllUsers()
    {
        $userDao        = new Dao_User();
        $users          = $userDao->getUsers();
        $userlist[null] = $this->translator->translate('pleaseChose');
        foreach ($users as $user)
            $userlist[$user[Db_User::ID]] = $user[Db_User::USERNAME];
        return $userlist;
    }

    /**
     * updates a workflow by the given WorkflowId and values
     *
     * @param int $workflowId
     * @param array $workflow
     */
    public function updateWorkflow($formData, $workflowId, $workflowTaskId, $user)
    {
        $daoWorkflow = new Dao_Workflow();
        $workflow    = $daoWorkflow->getWorkflow($workflowId);

        if ($formData['name']) {
            $formData['name'] = trim($formData['name']);
        }

        if ($formData['description']) {
            $formData['description'] = trim($formData['description']);
        }

        if ($formData['note']) {
            $formData['note'] = trim($formData['note']);
        }

        $docChanged         = false;
        $currentTriggerType = Util_Workflow::getWorkflowTriggerType($workflow);
        $futureTriggerType  = Util_Workflow::getWorkflowTriggerType($formData);
        if (
            $workflow[Db_Workflow::NAME] != $formData['name'] ||
            $workflow[Db_Workflow::DESCRIPTION] != $formData['description'] ||
            $currentTriggerType != $futureTriggerType ||
            $workflow[Db_Workflow::RESPONSE_FORMAT] != $formData['responseFormat'] ||
            $workflow[Db_Workflow::SCRIPT_LANG] != $formData['script_lang'] ||
            $workflow[Db_Workflow::IS_ASYNC] != $formData['asynch']
        ) {
            $docChanged = true;
        }

        $workflow[Db_Workflow::NAME]            = $formData['name'];
        $workflow[Db_Workflow::DESCRIPTION]     = $formData['description'];
        $workflow[Db_Workflow::NOTE]            = $formData['note'];
        $workflow[Db_Workflow::EXECUTE_USER_ID] = $formData['user'];
        $workflow[Db_Workflow::RESPONSE_FORMAT] = $formData['responseFormat'];
        $workflow[Db_Workflow::IS_ASYNC]        = $formData['asynch'];
        $workflow[Db_Workflow::IS_ACTIVE]       = $formData['active'];
        $workflow[Db_Workflow::SCRIPT_LANG]     = $formData['script_lang'];
        $workflow[Db_Workflow::USER_ID]         = $user->getId();

        if ($formData['trigger']) {
            if ($formData['trigger'] == 'time') {
                $workflow[Db_Workflow::TRIGGER_TIME]   = '1';
                $workflow[Db_Workflow::EXECUTION_TIME] = Service_Cron_Get::getExecutionTimeAsString($formData);

                $workflow[Db_Workflow::TRIGGER_ATTRIBUTE]      = '0';
                $workflow[Db_Workflow::TRIGGER_PROJECT]        = '0';
                $workflow[Db_Workflow::TRIGGER_CI]             = '0';
                $workflow[Db_Workflow::TRIGGER_CI_TYPE_CHANGE] = '0';
                $workflow[Db_Workflow::TRIGGER_RELATION]       = '0';
                $workflow[Db_Workflow::TRIGGER_FILEIMPORT]     = '0';
            } elseif ($formData['trigger'] == 'activity') {
                $workflow[Db_Workflow::TRIGGER_TIME]   = '0';
                $workflow[Db_Workflow::EXECUTION_TIME] = null;

                $workflow[Db_Workflow::TRIGGER_ATTRIBUTE]      = $formData['trigger_attribute'];
                $workflow[Db_Workflow::TRIGGER_PROJECT]        = $formData['trigger_project'];
                $workflow[Db_Workflow::TRIGGER_CI]             = $formData['trigger_ci'];
                $workflow[Db_Workflow::TRIGGER_CI_TYPE_CHANGE] = $formData['trigger_ci_type_change'];
                $workflow[Db_Workflow::TRIGGER_RELATION]       = $formData['trigger_relation'];
                $workflow[Db_Workflow::TRIGGER_FILEIMPORT]     = $formData['trigger_fileimport'];
            } elseif ($formData['trigger'] == 'manual') {
                $workflow[Db_Workflow::TRIGGER_TIME]   = '0';
                $workflow[Db_Workflow::EXECUTION_TIME] = '0';

                $workflow[Db_Workflow::TRIGGER_ATTRIBUTE]      = '0';
                $workflow[Db_Workflow::TRIGGER_PROJECT]        = '0';
                $workflow[Db_Workflow::TRIGGER_CI]             = '0';
                $workflow[Db_Workflow::TRIGGER_CI_TYPE_CHANGE] = '0';
                $workflow[Db_Workflow::TRIGGER_RELATION]       = '0';
                $workflow[Db_Workflow::TRIGGER_FILEIMPORT]     = '0';
            }
        }

        try {
            $workflowDaoImpl = new Dao_Workflow();
            $workflowDaoImpl->updateWorkflow($workflow, $workflowId);
            $workflowType = Util_Workflow_TypeFactory::create($formData['script_lang'], $workflow);

            try {
                $script_old              = $workflowType->getScriptContent();
                $testOld                 = $workflowType->getTestContent();
                $formData['script']      = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $formData['script']);
                $formData['script_test'] = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $formData['script_test']);

                if ($script_old != $formData['script'] || $testOld != $formData['script_test'] || $docChanged === true) {

                    $filename = $workflow[Db_Workflow::NAME] . "." . $workflowType->getExtension();

                    $workflowTask                              = array();
                    $workflowTask[Db_WorkflowTask::NAME]       = $filename;
                    $workflowTask[Db_WorkflowTask::SCRIPT]     = $filename;
                    $workflowTask[Db_WorkflowTask::SCRIPTNAME] = $filename;
                    $workflowTask[Db_WorkflowTask::IS_ASYNC]   = '0';

                    $workflowDaoImpl->updateWorkflowTask($workflowTask, $workflowTaskId);//do not overwrite $workflowTaskId

                    $workflowTransition[Db_WorkflowTransition::NAME]             = $filename;
                    $workflowTransition[Db_WorkflowTransition::DESCRIPTION]      = $filename;
                    $workflowTransition[Db_WorkflowTransition::NOTE]             = '';
                    $workflowTransition[Db_WorkflowTransition::TRIGGER]          = 'AUTO';
                    $workflowTransition[Db_WorkflowTransition::WORKFLOW_TASK_ID] = $workflowTaskId;//need to update, otherwise always old version

                    $workflowTransitionId = $workflowDaoImpl->updateWorkflowTransitionByWorkflowId($workflowTransition, $workflowId);

                    $workflowType->archive();
                    $workflowType->saveScript($formData['script'], $user);
                    $workflowType->saveTest($formData['script_test'], $user);
                }

            } catch (Exception $e) {
                throw new Exception_Workflow_InsertFailed($e);
            }


            if ($formData['trigger'] == 'activity') {
                try {
                    foreach ($formData as $key => $value) {
                        // if trigger_type is chosen && a trigger is linked to a ci
                        if ($formData['trigger_ci'] && strpos($key, 'ci__') === 0) {
                            $type = 'ci';
                        } elseif ($formData['trigger_ci_type_change'] && strpos($key, 'ci_type_change__') === 0) {
                            $type = 'ci_type_change';
                        } elseif ($formData['trigger_attribute'] && strpos($key, 'attribute__') === 0) {
                            $type = 'attribute';
                        } elseif ($formData['trigger_relation'] && strpos($key, 'relation__') === 0) {
                            $type = 'relation';
                        } elseif ($formData['trigger_project'] && strpos($key, 'project__') === 0) {
                            $type = 'project';
                        }
                        if ($type) {
                            $mappingId = substr($key, strlen($type . '__'));

                            foreach ($value as $method) {
                                if ($method == 'create')
                                    $newData[$type][$mappingId][Db_WorkflowTrigger::METHOD_KEY_CREATE] = $method;
                                if ($method == 'update')
                                    $newData[$type][$mappingId][Db_WorkflowTrigger::METHOD_KEY_UPDATE] = $method;
                                if ($method == 'delete')
                                    $newData[$type][$mappingId][Db_WorkflowTrigger::METHOD_KEY_DELETE] = $method;
                            }
                        }
                        unset($type);
                    }
                    if ($formData['trigger_fileimport'] && isset($formData['fileimport_trigger_regex'])) {
                        $regex = json_decode($formData['fileimport_trigger_regex']);
                        if (isset($regex) && !is_null($regex) && is_array($regex)) {
                            $newData[Db_WorkflowTrigger::TYPE_FILEIMPORT][0]['method'] = $formData['fileimport_trigger_method'];
                            $newData[Db_WorkflowTrigger::TYPE_FILEIMPORT][0]['regex']  = $formData['fileimport_trigger_regex'];
                        }
                    }
                    $this->updateMapping($newData, $workflowId);
                } catch (Exception $e) {
                    throw new Exception_Workflow_UpdateFailed($e);
                }
            }

        } catch (Exception $e) {
            throw new Exception_Workflow_UpdateFailed($e);
        }
    }

    private function updateMapping($formValues, $workflowId)
    {
        $workflowDaoImpl = new Dao_Workflow();

        $currentValues = $workflowDaoImpl->getWorkflowTriggerByWorkflowId($workflowId);


        if ($formValues) {
            $workflowDaoImpl->deleteWorkflowMappingByWorkflowId($workflowId);

            foreach ($formValues as $newType => $newValSelected) {
                foreach ($newValSelected as $newId => $newMethods) {
                    if ($newId === 0 && $newType === Db_WorkflowTrigger::TYPE_FILEIMPORT) {
                        $data                                       = array();
                        $data[Db_WorkflowTrigger::WORKFLOW_ID]      = $workflowId;
                        $data[Db_WorkflowTrigger::TYPE]             = $newType;
                        $data[Db_WorkflowTrigger::METHOD]           = $newMethods['method'];
                        $data[Db_WorkflowTrigger::FILEIMPORT_REGEX] = $newMethods['regex'];
                        $workflowDaoImpl->insertWorkflowTrigger($data);
                        break;
                    }
                    foreach ($newMethods as $newMethod) {
                        // always true because variable is not defined
                        if (!$newCurrentValues[$newId] || ($newCurrentValues[$newId] && !in_array($newMethod, $newCurrentValues[$newId]))) {
                            //insert
                            $data                                  = array();
                            $data[Db_WorkflowTrigger::WORKFLOW_ID] = $workflowId;
                            $data[Db_WorkflowTrigger::MAPPING_ID]  = $newId;
                            $data[Db_WorkflowTrigger::TYPE]        = $newType;
                            $data[Db_WorkflowTrigger::METHOD]      = $newMethod;
                            $workflowDaoImpl->insertWorkflowTrigger($data);
                        }
                    }
                }
            }
        } else {
            $workflowDaoImpl->deleteWorkflowMappingByWorkflowId($workflowId);
        }
    }

}