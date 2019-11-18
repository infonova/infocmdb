<?php

/**
 *
 * this processor is used to cleanup cmdb instances. not required, but will speedup the system
 *
 *
 *
 */
class Process_Cleanup extends Service_Queue_Processor
{

    public function __construct()
    {
        parent::__construct('cleanup');
    }

    public function process()
    {
        $this->logger->log('process Cleanup!', Zend_Log::DEBUG);

        $this->handleQueue();
        $this->cleanupFileImports();
        $this->cleanupWorkflows();
        $this->cleanupImportFileValidations();
    }

    private function handleQueue()
    {
        $queueDaoImpl = new Dao_Queue();
        $queueDaoImpl->deleteOldMessages();
        $queueDaoImpl->deleteOldSearchResults();
        $queueDaoImpl->deleteOldSessions();
        $queueDaoImpl->deleteOldApiSessions();
        $queueDaoImpl->deleteOldPasswordResetRequests();

    }

    /** #START# HANDLE FILEIMPORT CLEANUP **/

    /**
     *
     * @return void
     */
    private function cleanupFileImports()
    {
        $this->logger->log("Cleanup ImportFile: STARTED", Zend_Log::INFO);
        $queueDaoImpl = new Dao_Queue();
        try {
            // config for import rotation
            $configImport = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        } catch (Exception $ex) {
            $this->logger->log("Cleanup ImportFile: error opening config file: " . $ex, Zend_Log::ERR);
            return;
        }


        // variables for paths of files
        $history     = $configImport->file->import->history;
        $log         = $configImport->file->import->log;
        $destination = $configImport->file->import->destination;

        if (!$history) {
            $history = "history/";
        }
        if (!$log) {
            $log = "log/";
        }
        $historyPath = $destination . $history;
        $logPath     = $destination . $log;

        // rules to apply to the imports
        $rulesConf = $configImport->file->import->rotation;
        // no rules set -> stop execution
        if (!$rulesConf) {
            $this->logger->log("Cleanup ImportFile: no rules set", Zend_Log::NOTICE);
            return;
        }
        // get rules from ini as array()
        $rules = $rulesConf->toArray();

        // unset default Rule from rules array, and add it at the end [ -> default rule needs to be executed last ]
        if (isset($rules['default'])) {
            $default = $rules['default'];
            unset($rules['default']);
            $rules['default'] = $default;
        }

        // iterating through each rule and getting it's values from config
        foreach ($rules as $key => $rule) {
            $this->logger->log("Cleanup ImportFile starting for rule " . $key, Zend_Log::DEBUG);

            // regex key does not exist in array -> set regex to null
            if (!array_key_exists('regex', $rule)) {
                $rule['regex'] = null;
            }

            // if the rule does not have a regex -> return (unless the rule is the "default" rule)
            if ($key !== 'default' && !$rule['regex']) {
                $this->logger->log("Cleanup ImportFile key: " . $key . " has no regex! Skipping", Zend_Log::WARN);
                continue;
            }
            // maxAge is set -> execute query to get all entries that need to be deleted and call deleteImport with resultset of entries
            if (isset($rule['max_age']) && ($rule['max_age'])) {
                $result = $queueDaoImpl->getImportTooOld($rule['regex'], $rule['max_age']);
                $this->deleteFileImport($result, $historyPath, $logPath);
            }

            // maxCount is set -> execute query to get all entries that need to be deleted and call deleteImport with resultset of entries                
            if (isset($rule['max_count']) && ($rule['max_count'])) {
                $result = $queueDaoImpl->getImportTooMany($rule['regex'], $rule['max_count']);
                $this->deleteFileImport($result, $historyPath, $logPath);
            }
            $this->logger->log("Cleanup ImportFile finished for rule " . $key, Zend_Log::DEBUG);
        }
        $this->logger->log("Cleanup ImportFile: ENDED", Zend_Log::INFO);
    }

    /**
     *
     * @param array  $data
     * @param string $historyPath
     * @param string $logPath
     */
    private function deleteFileImport($data, $historyPath, $logPath)
    {
        //$this->logger->log(json_encode($data), 3);
        $queueDaoImpl = new Dao_Queue();

        // iterate through each DbRow
        foreach ($data as $entry) {
            // entry does not have id column -> return;
            if (!$entry[Db_ImportFileHistory::ID]) {
                return;
            }
            $id = $entry[Db_ImportFileHistory::ID];

            try {
                // delete history and log directory for this entry with ID
                $this->deleteDir($historyPath . $id);
                $this->deleteDir($logPath . $id);
                // delete database entries for this ID (import_file_history && import_file_history_detail)
                $queueDaoImpl->deleteImportFileHistory($id);
            } catch (Exception $ex) {
                $this->logger->log("Cleanup ImportFile FATAL ERROR: " . $ex, Zend_Log::CRIT);
            }
        }

    }
    /** #END# HANDLE FILEIMPORT CLEANUP **/

    /**
     * Deleting directory and all it's contents
     *
     * @see http://stackoverflow.com/a/3349792
     *
     * @param string $dirPath
     *
     * @return void
     */
    public static function deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            $logger = Zend_Registry::get('Log');
            $logger->log($dirPath . " is not a directory!", Zend_Log::WARN);
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    protected function cleanupWorkflows()
    {
        $queueDaoImpl    = new Dao_Queue();
        $workflowDaoImpl = new Dao_Workflow();

        $config = new Util_Config('delete.ini', APPLICATION_ENV);
        $rules  = $config->getValue('workflow.instance.rotation', array(), Util_Config::ARR);

        // move default config to the end, we want to handle custom rules first
        if (isset($rules['default'])) {
            $default = $rules['default'];
            unset($rules['default']);
            $rules['default'] = $default;
        }

        // iterating through each rule and getting it's values from config
        foreach ($rules as $key => $rule) {
            // regex key does not exist in array -> set regex to null
            if (!array_key_exists('regex', $rule)) {
                $rule['regex'] = null;
            }

            // if the rule does not have a regex -> return (unless the rule is the "default" rule)
            if ($key !== 'default' && !$rule['regex']) {
                $this->logger->log("Cleanup cleanupWorkflows key: " . $key . " has no regex! Skipping", Zend_Log::WARN);
                continue;
            }

            $result = $queueDaoImpl->getTooOldWorkflowCases($rule['max_age'], $rule['regex']);
            $this->logger->log("Cleanup cleanupWorkflows deleting " . count($result) . " workflow instance entries", Zend_Log::INFO);
            foreach ($result as $row) {
                $workflowDaoImpl->deleteWorkflowInstance($row['workflow_id'], $row['workflow_case_id']);
            }


            $this->logger->log("Cleanup cleanupWorkflows finished for rule " . $key, Zend_Log::DEBUG);
        }

    }


    protected function cleanupImportFileValidations()
    {
        $queueDaoImpl = new Dao_Queue();

        $config = new Util_Config('delete.ini', APPLICATION_ENV);
        $maxAge = $config->getValue('import.file.validation.rotation.default.max_age', '', Util_Config::STRING);

        if (!empty($maxAge)) {
            $queueDaoImpl->deleteOldImportFileValidations($maxAge);
        }

    }
}