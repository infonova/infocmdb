<?php

class Import_File extends Service_Queue_Processor
{

    const STATUS_SUCCESS     = 'success';
    const STATUS_FAILED      = 'failed';
    const STATUS_IDLE        = 'idle';
    const STATUS_IN_PROGRESS = 'in_progress';


    public function __construct()
    {
        parent::__construct('import_file');
    }


    public function process()
    {
        try {

            // get message properties
            $properties = $this->message->getArgs();
            list($fileName, $validation, $method, $historyId) = $properties;

            $this->logger->log(sprintf('processing File import: %s', $fileName), Zend_Log::INFO);
            // load fileimport config and destination for error/success-files
            $config   = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
            $filepath = $this->getFilePath($fileName, $validation, $method, $config);


            $file = $filepath .'/'. $fileName;
            if (!is_file($file)) {
                // TODO: update file import history
                $this->logger->log('[ERROR] FILE import! Unable to find file ' . $file, Zend_Log::ERR);
                $this->finalizeImport(self::STATUS_FAILED);
                return false;
            }

            /*
             *  Create lockfile for multifile imports
             *  bc__1__
             *  abc__2__
             *  Lockfile = lock_<filename> (without __D__)
             */

            $lockFile = preg_replace('/[^a-zA-Z]/', '', $fileName);
            if(is_null($lockFile) || $lockFile === "") {
                $log->log(sprintf('Filename for Importfile consits only of numbers. filename:\'%s\'', $fileName), Zend_Log::WARN);
                $lockFile = "only_numbers_file";
            }
            $lockFile = APPLICATION_DATA . '/lock_' . $lockFile;
            // get filetype from file name

            preg_match('/\.([^\.]\w*)$/', $fileName, $fileext_match);
            $fileType = strtoupper($fileext_match[1]);

            $this->logger->log('Locking file: ' . $lockFile, Zend_Log::DEBUG);

            if (file_exists($lockFile) === true) {
                // Lockfile exists and is older than 60 minutes, start logging
                if(time() - filemtime($lockFile) > 3600) {
                    $this->logger->log('Lockfile exists, >60 minutes, stale? (' . $lockFile . ')', Zend_Log::WARN);
                } else {
                    $this->logger->log('Lockfile exists, <60 minutes, requeing. (' . $lockFile . ')', Zend_Log::INFO);
                }
                $this->finalizeImport(self::STATUS_IDLE);
                return (false);
            }

            $lockfile_r = fopen($lockFile, "w+");
            if ($lockfile_p = flock($lockfile_r, LOCK_EX)) { // exklusive Sperre
                $this->logger->log('Locking file ' . $lockFile . ' OK', Zend_Log::DEBUG);
            } else {
                $this->logger->log('[ERROR] FILE import! Unable lock file ' . $lockFile, Zend_Log::ERR);
                $this->finalizeImport(self::STATUS_IDLE);
                return false;
            };

            // TODO: recreate HISTORYY!!!!
            if (!$historyId) {
                $historyId = Import_File_Util_History::createHistory(self::STATUS_IN_PROGRESS, $fileName, 'idle');
            } else {
                // update history status
                Import_File_Util_History::updateHistoryStatus($historyId, self::STATUS_IN_PROGRESS);
            }

            $trigger = new Util_Trigger($this->logger);
            $trigger->fileimportTrigger($fileName, Db_WorkflowTrigger::METHOD_BEFORE_IMPORT, $historyId);

            $logPath = $config->file->import->destination;
            $logPath .= $config->file->import->log;
            $logPath .= $historyId . '/';

            // create logfolder
            if (!is_dir($logPath)) {
                @mkdir($logPath, 0777);
                chmod($logPath, 0777);
            }

            // create log file
            if (!file_exists($logPath . $fileName . '.log')) {
                fopen($logPath . $fileName . '.log', 'a');
                chmod($logPath . $fileName . '.log', 0777);
            }

            $writer = new Zend_Log_Writer_Stream($logPath . $fileName . '.log');
            $log    = new Zend_Log($writer);

            $log->log(sprintf('filename: %s', $fileName), Zend_Log::INFO);
            $log->log(sprintf('$validation: %s', $validation), Zend_Log::INFO);
            $log->log(sprintf('$method: %s', $method), Zend_Log::INFO);
            $log->log(sprintf('$historyId: %s', $historyId), Zend_Log::INFO);

            // create options array
            $options              = array();
            $options['separator'] = $config->file->import->project->separator;
            $options['filename']  = $fileName;

            // TODO: define options!! $options
            $methodClass = Import_File_Factory::getMethod($method, $validation, $historyId, $options, $log);
            if ($methodClass === false) {
                $this->logger->log('Method create failed', Zend_Log::CRIT);
                $this->finalizeImport(self::STATUS_FAILED);
                Import_File_Util_History::updateHistoryStatus($historyId, self::STATUS_FAILED);
            } else {
                $options     = $methodClass['parameter'];
                $methodClass = $methodClass['class'];
            }

            $typeClass = Import_File_Factory::getType($fileType, $log, $file, $historyId, $options);

            if (!$typeClass->import($methodClass)) {
                // TODO: error
                $this->finalizeImport(self::STATUS_FAILED);
                $this->finalizeValidation($options);
                Import_File_Util_History::updateHistoryStatus($historyId, self::STATUS_FAILED);
            } else {
                $this->finalizeImport(self::STATUS_SUCCESS);
                $this->finalizeValidation($options);
                Import_File_Util_History::updateHistoryStatus($historyId, self::STATUS_SUCCESS);
                $success = true;
            }

            if (isset($success)) {
                $trigger = new Util_Trigger($this->logger);
                $trigger->fileimportTrigger($fileName, Db_WorkflowTrigger::METHOD_AFTER_IMPORT, $historyId);
            }
            $this->logger->log('Release Lock and delete Lock' . $lockFile, Zend_Log::DEBUG);
            flock($lockfile_r, LOCK_UN); // Gib Sperre frei
            fclose($lockfile_r);
            unlink($lockFile);

            $this->moveFile($file, $fileName, $historyId, $config);

        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            $this->finalizeImport(self::STATUS_FAILED);
            $this->finalizeValidation($options);
            Import_File_Util_History::updateHistoryStatus($historyId, self::STATUS_FAILED);
            $this->moveFile($file, $fileName, $historyId, $config);
        }
    }


    // TODO: implement me!!
    private function getFilePath($fileName, $validation, $method, $config)
    {
        $dir = $config->file->import->destination;
        $dir .= $config->file->import->queue;
        $dir .= $config->file->import->validation->$validation;
        $dir .= $config->file->import->type->$method;

        return $dir;
    }


    private function finalizeValidation($parameter)
    {
        try {
            if ($parameter && $parameter['validationId'])
                Import_File_Util_History::checkFinalizeValidation($parameter['validationId']);

            if ($parameter && $parameter['validationIdInsert'])
                Import_File_Util_History::checkFinalizeValidation($parameter['validationIdInsert']);


            if ($parameter && $parameter['validationIdUpdate'])
                Import_File_Util_History::checkFinalizeValidation($parameter['validationIdUpdate']);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::WARN);
            return;
        }
        return;
    }

    private function finalizeImport($status)
    {
        if ($status == self::STATUS_SUCCESS) {
            Service_Queue_Handler::finalize($this->message->getId());
        } elseif ($status == self::STATUS_IDLE) {
            Service_Queue_Handler::resetStatus($this->message->getId());
        } else {
            Service_Queue_Handler::failed($this->message->getId());
        }
    }

    private function moveFile($file, $fileName, $historyId, $config)
    {
        $destination = $config->file->import->destination;
        $destination .= $config->file->import->history;
        $destination .= $historyId;

        try {
            // check if requested path is available
            if (!is_dir($destination)) {
                @mkdir($destination, 0777);
                chmod($destination, 0777);
            }

            // move file to new destination
            if (!rename($file, $destination .'/'. $fileName)) {
                $this->logger->log('$file, $destination.'/'.$fileName', Zend_Log::CRIT);
                throw new Exception_FileImport_MoveFileFailed();
            }

        } catch (Exception $e) {
            $this->logger->log('[ERROR] Unexpected exception! Unable to move file to target destination. ', Zend_Log::CRIT);
            $this->logger->log($e, Zend_Log::CRIT);
        }
    }
}