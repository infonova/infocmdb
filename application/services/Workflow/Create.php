<?php

/**
 *
 *
 *
 */
class Service_Workflow_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3602, $themeId);
    }


    /**
     * creates a Workflow by the given values
     *
     * @param array $values
     */
    public function createWorkflow($formData, $user)
    {
        $workflow                               = array();
        $workflow[Db_Workflow::NAME]            = trim($formData['name']);
        $workflow[Db_Workflow::DESCRIPTION]     = trim($formData['description']);
        $workflow[Db_Workflow::NOTE]            = trim($formData['note']);
        $workflow[Db_Workflow::IS_ASYNC]        = $formData['asynch'];
        $workflow[Db_Workflow::IS_ACTIVE]       = $formData['active'];
        $workflow[Db_Workflow::EXECUTE_USER_ID] = $formData['user'];
        $workflow[Db_Workflow::SCRIPT_LANG]     = $formData['script_lang'];
        $workflow[Db_Workflow::USER_ID]         = $user->getId();
        $workflow[Db_Workflow::RESPONSE_FORMAT] = $formData['responseFormat'];

        if ($formData['trigger'] == 'time') {
            $workflow[Db_Workflow::TRIGGER_TIME]   = 1;
            $workflow[Db_Workflow::EXECUTION_TIME] = Service_Cron_Get::getExecutionTimeAsString($formData);

            $workflow[Db_Workflow::TRIGGER_ATTRIBUTE] = 0;
            $workflow[Db_Workflow::TRIGGER_PROJECT]   = 0;
            $workflow[Db_Workflow::TRIGGER_CI]        = 0;
            $workflow[Db_Workflow::TRIGGER_RELATION]  = 0;
        } elseif ($formData['trigger'] == 'activity') {
            $workflow[Db_Workflow::TRIGGER_TIME]   = 0;
            $workflow[Db_Workflow::EXECUTION_TIME] = null;

            $workflow[Db_Workflow::TRIGGER_ATTRIBUTE]      = $formData['trigger_attribute'];
            $workflow[Db_Workflow::TRIGGER_PROJECT]        = $formData['trigger_project'];
            $workflow[Db_Workflow::TRIGGER_CI]             = $formData['trigger_ci'];
            $workflow[Db_Workflow::TRIGGER_CI_TYPE_CHANGE] = $formData['trigger_ci_type_change'];
            $workflow[Db_Workflow::TRIGGER_RELATION]       = $formData['trigger_relation'];
            $workflow[Db_Workflow::TRIGGER_FILEIMPORT]     = $formData['trigger_fileimport'];
        }

        try {
            $workflowDaoImpl           = new Dao_Workflow();
            $workflowId                = $workflowDaoImpl->insertWorkflow($workflow);
            $workflow[Db_Workflow::ID] = $workflowId;

            if (!$workflowId) {
                throw new Exception_Workflow_InsertFailed();
            }

            if ($formData['trigger'] == 'activity') {
                try {
                    foreach ($formData as $key => $value) {
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
                                $workflowTrigger                                  = array();
                                $workflowTrigger[Db_WorkflowTrigger::WORKFLOW_ID] = $workflowId;
                                $workflowTrigger[Db_WorkflowTrigger::TYPE]        = $type;
                                $workflowTrigger[Db_WorkflowTrigger::MAPPING_ID]  = $mappingId;
                                $workflowTrigger[Db_WorkflowTrigger::METHOD]      = $method;
                                $workflowTriggerId                                = $workflowDaoImpl->insertWorkflowTrigger($workflowTrigger);
                                if (!$workflowTriggerId) {
                                    throw new Exception_Workflow_InsertFailed();
                                }
                            }

                        }
                        unset($type);
                    }
                    if ($formData['trigger_fileimport'] && isset($formData['fileimport_trigger_regex'])) {
                        $regex = json_decode($formData['fileimport_trigger_regex']);
                        if (isset($regex) && !is_null($regex) && is_array($regex)) {
                            $data                                       = array();
                            $data[Db_WorkflowTrigger::WORKFLOW_ID]      = $workflowId;
                            $data[Db_WorkflowTrigger::TYPE]             = Db_WorkflowTrigger::TYPE_FILEIMPORT;
                            $data[Db_WorkflowTrigger::METHOD]           = $formData['fileimport_trigger_method'];
                            $data[Db_WorkflowTrigger::FILEIMPORT_REGEX] = $formData['fileimport_trigger_regex'];
                            $workflowDaoImpl->insertWorkflowTrigger($data);
                        }
                    }
                } catch (Exception $e) {
                    throw new Exception_Workflow_InsertFailed($e);
                }
            }

            try {
                $workflowType = Util_Workflow_TypeFactory::create($formData['script_lang'], $workflow);
                $filename     = $workflow[Db_Workflow::NAME] . "." . $workflowType->getExtension();

                $workflowTask                              = array();
                $workflowTask[Db_WorkflowTask::NAME]       = $filename;
                $workflowTask[Db_WorkflowTask::SCRIPT]     = $filename;
                $workflowTask[Db_WorkflowTask::SCRIPTNAME] = $filename;
                $workflowTask[Db_WorkflowTask::IS_ASYNC]   = '0';

                $workflowTaskId = $workflowDaoImpl->insertWorkflowTask($workflowTask);


                $workflowTransition[Db_WorkflowTransition::WORKFLOW_ID]      = $workflowId;
                $workflowTransition[Db_WorkflowTransition::NAME]             = $filename;
                $workflowTransition[Db_WorkflowTransition::DESCRIPTION]      = $filename;
                $workflowTransition[Db_WorkflowTransition::NOTE]             = '';
                $workflowTransition[Db_WorkflowTransition::TRIGGER]          = 'AUTO';
                $workflowTransition[Db_WorkflowTransition::WORKFLOW_TASK_ID] = $workflowTaskId;

                $workflowTransitionId = $workflowDaoImpl->insertWorkflowTransition($workflowTransition);


                $workflowPlaceStart[Db_WorkflowPlace::WORKFLOW_ID] = $workflowId;
                $workflowPlaceStart[Db_WorkflowPlace::TYPE]        = 1;
                $workflowPlaceStart[Db_WorkflowPlace::NAME]        = 'start';
                $workflowPlaceStart[Db_WorkflowPlace::DESCRIPTION] = 'Start';

                $workflowPlaceStart = $workflowDaoImpl->insertWorkflowPlace($workflowPlaceStart);


                $workflowPlaceEnd[Db_WorkflowPlace::WORKFLOW_ID] = $workflowId;
                $workflowPlaceEnd[Db_WorkflowPlace::TYPE]        = 9;
                $workflowPlaceEnd[Db_WorkflowPlace::NAME]        = 'end';
                $workflowPlaceEnd[Db_WorkflowPlace::DESCRIPTION] = 'End';

                $workflowPlaceEnd = $workflowDaoImpl->insertWorkflowPlace($workflowPlaceEnd);


                $workflowArc1[Db_WorkflowArc::WORKFLOW_ID]            = $workflowId;
                $workflowArc1[Db_WorkflowArc::WORKFLOW_TRANSITION_ID] = $workflowTransitionId;
                $workflowArc1[Db_WorkflowArc::WORKFLOW_PLACE_ID]      = $workflowPlaceStart;
                $workflowArc1[Db_WorkflowArc::DIRECTION]              = 'IN';
                $workflowArc1[Db_WorkflowArc::TYPE]                   = 'SEQ';

                $workflowArc1 = $workflowDaoImpl->insertWorkflowArc($workflowArc1);


                $workflowArc2[Db_WorkflowArc::WORKFLOW_ID]            = $workflowId;
                $workflowArc2[Db_WorkflowArc::WORKFLOW_TRANSITION_ID] = $workflowTransitionId;
                $workflowArc2[Db_WorkflowArc::WORKFLOW_PLACE_ID]      = $workflowPlaceEnd;
                $workflowArc2[Db_WorkflowArc::DIRECTION]              = 'OUT';
                $workflowArc2[Db_WorkflowArc::TYPE]                   = 'SEQ';

                $workflowArc2 = $workflowDaoImpl->insertWorkflowArc($workflowArc2);

                $workflowType->saveScript($formData['script'], $user);
                $workflowType->saveTest($formData['script_test'], $user);

            } catch (Exception $e) {
                throw new Exception_Workflow_InsertFailed($e);
            }
            return $workflowId;
        } catch (Exception $e) {
            throw new Exception_Workflow_InsertFailed($e);
        }
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

    public function getCreateWorkflowForm()
    {
        $form = new Form_Workflow_Create($this->translator, $this->getAllUsers());
        return $form;
    }

}