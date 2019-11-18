<?php


class Util_Trigger
{

    private $logger;
    private $db;

    public function __construct($loggers)
    {
        $this->logger = $loggers;
        $this->db     = Zend_Registry::get('db');
    }

    // CI handling
    public function createCi($ciId, $userId)
    {
        $this->handleCi($ciId, $userId, 'create');
    }

    public function updateCi($ciId, $userId, $ciInfo = null)
    {
        $this->handleCi($ciId, $userId, 'update', $ciInfo);
    }

    public function deleteCi($ciId, $userId, $ciInfo = null)
    {
        $this->handleCi($ciId, $userId, 'delete', $ciInfo);
    }


    private function handleCi($ciId, $userId, $type, $ciInfo = null)
    {
        if (!$ciId) {
            $this->logger->log('failed to execute ci trigger workflow. $ciId was null', Zend_Log::ERR);
            return;
        }

        try {
            $ciDaoImpl = new Dao_Ci();
            $ci        = $ciDaoImpl->getCi($ciId);

            $workflowDaoImpl     = new Dao_Workflow();
            $workflowssToExecute = $workflowDaoImpl->getConfiguredWorkflowMappings('ci', $type, $ci[Db_Ci::CI_TYPE_ID]);

            $contextArray                = array();
            $contextArray['ciid']        = (int) $ciId;
            $contextArray['triggerType'] = 'ci_' . $type;

            if (isset($ciInfo['old'])) {
                $contextArray['data']['old'] = $ciInfo['old'];
            }

            if (isset($ciInfo['new'])) {
                $contextArray['data']['new'] = $ciInfo['new'];
            }

            $workflowUtil = new Util_Workflow($this->logger);
            foreach ($workflowssToExecute as $workflow) {
                $workflowUtil->startWorkflow($workflow[Db_Workflow::ID], $userId, $contextArray);
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }
    }


    public function CiTypeChange($ciId, $userId)
    {
        $this->handleCiTypeChange($ciId, $userId, 'update');
    }


    public function handleCiTypeChange($ciId, $userId, $type)
    {
        if (!$ciId) {
            $this->logger->log('failed to execute ci trigger workflow. $ciId was null', Zend_Log::ERR);
            return;
        }

        try {
            $ciDaoImpl = new Dao_Ci();
            $ci        = $ciDaoImpl->getCi($ciId);

            $workflowDaoImpl     = new Dao_Workflow();
            $workflowssToExecute = $workflowDaoImpl->getConfiguredWorkflowMappings('ci_type_change', $type, $ci[Db_Ci::CI_TYPE_ID]);

            $last_ci_type = $this->db->select();
            $last_ci_type->from(Db_History_Ci::TABLE_NAME, array(DB_History_Ci::CI_TYPE_ID));
            $last_ci_type->where(Db_History_Ci::ID . ' =?', $ci[DB_Ci::ID]);
            $last_ci_type->order(array(Db_History_Ci::VALID_TO . ' DESC'));
            $last_ci_type->limit(1);
            $last_ci_type = $this->db->fetchOne($last_ci_type);

            if (!empty($last_ci_type)) {
                $workflowssToExecute_lastCiType = $workflowDaoImpl->getConfiguredWorkflowMappings('ci_type_change', $type, $last_ci_type);
                $workflowssToExecute            = array_merge($workflowssToExecute, $workflowssToExecute_lastCiType);
                // begin multi-dimensional unique
                $arrayRewrite = array();
                $arrayHashes  = array();
                foreach ($workflowssToExecute as $key => $item) {
                    // Serialize the current element and create a md5 hash  
                    $hash = md5(serialize($item));
                    // If the md5 didn't come up yet, add the element to  
                    // to arrayRewrite, otherwise drop it  
                    if (!isset($arrayHashes[$hash])) {
                        // Save the current element hash  
                        $arrayHashes[$hash] = $hash;
                        // Add element to the unique Array  
                        if ($preserveKeys) {
                            $arrayRewrite[$key] = $item;
                        } else {
                            $arrayRewrite[] = $item;
                        }
                    }
                }
                $workflowssToExecute = $arrayRewrite;
                //end multi-dimensional uniuqe
            }

            $contextArray                = array();
            $contextArray['ciid']        = (int) $ciId;
            $contextArray['triggerType'] = 'ci_type_change_' . $type;

            $workflowUtil = new Util_Workflow($this->logger);
            foreach ($workflowssToExecute as $workflow) {
                $workflowUtil->startWorkflow($workflow[Db_Workflow::ID], $userId, $contextArray);
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }
    }


    // Attribute handling

    public function createAttribute($ciAttributeId, $userId)
    {
        $this->handleAttributes($ciAttributeId, $userId, 'create');
    }

    public function updateAttribute($oldCiAttributeId, $userId)
    {
        $this->handleAttributes($oldCiAttributeId, $userId, 'update');
    }

    public function deleteAttribute($ciAttributeId, $userId)
    {
        $this->handleAttributes($ciAttributeId, $userId, 'delete');
    }

    private function handleAttributes($ciAttributeId, $userId, $type)
    {
        if (!$ciAttributeId) {
            $this->logger->log('failed to execute attribute trigger workflow. attributeId was null', Zend_Log::ERR);
            return;
        }

        try {
            $ciDaoImpl   = new Dao_Ci();
            $ciAttribute = $ciDaoImpl->getCiAttributeByCiAttributeId($ciAttributeId);

            if (!is_array($ciAttribute)) {
                throw new Exception_Workflow_TriggerFailed(sprintf('Could not resolve ci_attribute row from ci_attribute-ID: %s', $ciAttributeId));
            }

            $workflowDaoImpl     = new Dao_Workflow();
            $workflowssToExecute = $workflowDaoImpl->getConfiguredWorkflowMappings('attribute', $type, $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID]);

            $contextArray                  = array();
            $contextArray['ciAttributeId'] = (int) $ciAttributeId;
            $contextArray['triggerType']   = 'ci_attribute_' . $type;
            $contextArray['ciid']          = (int) $ciAttribute[Db_CiAttribute::CI_ID];

            $workflowUtil = new Util_Workflow($this->logger);
            foreach ($workflowssToExecute as $workflow) {
                $workflowUtil->startWorkflow($workflow[Db_Workflow::ID], $userId, $contextArray);
            }
        } catch (Exception_Workflow_TriggerFailed $e) {
            $this->logger->log($e, Zend_Log::ERR);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }
    }


    // Relation handling

    public function createRelation($ciRelationId, $userId)
    {
        $this->handleRelations($ciRelationId, $userId, 'create');
    }

    public function deleteRelation($ciRelationId, $userId)
    {
        $this->handleRelations($ciRelationId, $userId, 'delete');
    }


    private function handleRelations($relationId, $userId, $type)
    {
        if (!$relationId) {
            $this->logger->log('failed to execute relationId trigger workflow. Relation was null', Zend_Log::ERR);
            return;
        }

        try {
            $ciRelationDaoImpl = new Dao_CiRelation();
            $ciRelation        = $ciRelationDaoImpl->getCiRelationById($relationId);
            // get relation type id

            $workflowDaoImpl     = new Dao_Workflow();
            $workflowssToExecute = $workflowDaoImpl->getConfiguredWorkflowMappings('relation', $type, $ciRelation[Db_CiRelation::CI_RELATION_TYPE_ID]);

            $contextArray                 = array();
            $contextArray['ciRelationId'] = (int) $relationId;
            $contextArray['triggerType']  = 'ci_relation_' . $type;

            $workflowUtil = new Util_Workflow($this->logger);
            foreach ($workflowssToExecute as $workflow) {
                $workflowUtil->startWorkflow($workflow[Db_Workflow::ID], $userId, $contextArray);
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }
    }


    // Project handling

    public function createProject($ciProjectId, $userId)
    {
        $this->handleProjects($ciProjectId, $userId, 'create');
    }

    public function deleteProject($ciProjectId, $userId)
    {
        $this->handleProjects($ciProjectId, $userId, 'delete');
    }


    private function handleProjects($ciProjectId, $userId, $type)
    {
        if (!$ciProjectId) {
            $this->logger->log('failed to execute ciProjectId trigger workflow. ProjectId was null', Zend_Log::ERR);
            return;
        }

        try {
            $projectDaoImpl = new Dao_Project();
            $ciProject      = $projectDaoImpl->getCiProjectById($ciProjectId);

            $workflowDaoImpl     = new Dao_Workflow();
            $workflowssToExecute = $workflowDaoImpl->getConfiguredWorkflowMappings('project', $type, $ciProject[Db_CiProject::PROJECT_ID]);

            $contextArray                = array();
            $contextArray['ciProjectId'] = (int) $ciProjectId;
            $contextArray['triggerType'] = 'ci_project_' . $type;

            $workflowUtil = new Util_Workflow($this->logger);
            foreach ($workflowssToExecute as $workflow) {
                $workflowUtil->startWorkflow($workflow[Db_Workflow::ID], $userId, $contextArray);
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }

    }

    // fileimport handling

    /**
     * Used to signalize a file import trigger. Either after or before can be specified by $method
     *
     * Executes the workflows with a file import trigger that have a regex that matches the specified filename
     * Workflows are executed synchronized since the file import should finish before another one is started
     *
     * @param $importedFileName      string name of the file to check the regex against
     * @param $method                string Db_WorkflowTrigger::METHOD_AFTER_IMPORT or Db_WorkflowTrigger::METHOD_BEFORE_IMPORT
     * @param $fileimport_history_id int history id of the file import which is passed to the workflow as context parameter
     */
    public function fileimportTrigger($importedFileName, $method, $fileimport_history_id)
    {
        $workflow_dao               = new Dao_Workflow();
        $workflow_util              = new Util_Workflow($this->logger);
        $workflows_with_file_import = $workflow_dao->getConfiguredWorkflowMappings(Db_WorkflowTrigger::TYPE_FILEIMPORT, $method, null);
        foreach ($workflows_with_file_import as $workflow) {
            $regex = json_decode($workflow[Db_WorkflowTrigger::FILEIMPORT_REGEX], true);
            foreach ($regex as $re) {
                $file_matches_regex = preg_match($re, $importedFileName);
                if ($file_matches_regex === 1) {
                    // fileimport should only continue if previous fileimport is finished which includes workflow execution
                    $context                        = array();
                    $context['triggerType']         = "fileimport_" . $method;
                    $context['fileImportHistoryId'] = (int) $fileimport_history_id;
                    $workflow_util->startWorkflow($workflow[Db_Workflow::ID], '0', $context);
                    break;
                }
            }

        }
    }
}