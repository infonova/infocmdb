<?php

class Service_Queue_Listener_File implements Service_Queue_Listener
{

    private $logger;

    public function listen()
    {
        $this->logger = Zend_Registry::get('Log');

        $config  = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $baseDir = $config->file->import->destination;
        $baseDir .= $config->file->import->queue;

        try {
            $this->listenAttribute($baseDir, $config);
            $this->listenInsertAuto($baseDir, $config);
            $this->listenInsertManual($baseDir, $config);
            $this->listenUpdateAuto($baseDir, $config);
            $this->listenUpdateManual($baseDir, $config);

            $this->listenImportAuto($baseDir, $config);
            $this->listenImportManual($baseDir, $config);

            $this->listenRelationAuto($baseDir, $config);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::WARN);
        }
    }

    private function listenAttribute($baseDir, $config)
    {
        $validationDir = $config->file->import->validation->auto;
        $methodDir     = $config->file->import->type->attribute;

        $validation = 'auto';
        $dir        = $baseDir . $validationDir . $methodDir;
        $fileList   = $this->retrieveFileList($dir);

        foreach ($fileList as $file) {
            $this->insertQueueMessage($file, $validation, Service_Queue_Message::METHOD_ATTRIBUTE);
        }
    }

    private function listenInsertAuto($baseDir, $config)
    {
        $validationDir = $config->file->import->validation->auto;
        $methodDir     = $config->file->import->type->insert;

        $validation = 'auto';
        $dir        = $baseDir . $validationDir . $methodDir;
        $fileList   = $this->retrieveFileList($dir);

        foreach ($fileList as $file) {
            $this->insertQueueMessage($file, $validation, Service_Queue_Message::METHOD_INSERT);
        }
    }

    private function listenInsertManual($baseDir, $config)
    {
        $validationDir = $config->file->import->validation->manual;
        $methodDir     = $config->file->import->type->insert;

        $validation = 'manual';
        $dir        = $baseDir . $validationDir . $methodDir;
        $fileList   = $this->retrieveFileList($dir);

        foreach ($fileList as $file) {
            $this->insertQueueMessage($file, $validation, Service_Queue_Message::METHOD_INSERT);
        }
    }

    private function listenUpdateAuto($baseDir, $config)
    {
        $validationDir = $config->file->import->validation->auto;
        $methodDir     = $config->file->import->type->update;

        $validation = 'auto';
        $dir        = $baseDir . $validationDir . $methodDir;
        $fileList   = $this->retrieveFileList($dir);

        foreach ($fileList as $file) {
            $this->insertQueueMessage($file, $validation, Service_Queue_Message::METHOD_UPDATE);
        }
    }

    private function listenUpdateManual($baseDir, $config)
    {
        $validationDir = $config->file->import->validation->manual;
        $methodDir     = $config->file->import->type->update;
        $validation    = 'manual';
        $dir           = $baseDir . $validationDir . $methodDir;

        $fileList = $this->retrieveFileList($dir);

        foreach ($fileList as $file) {
            $this->insertQueueMessage($file, $validation, Service_Queue_Message::METHOD_UPDATE);
        }
    }

    private function listenImportAuto($baseDir, $config)
    {
        $validationDir = $config->file->import->validation->auto;
        $methodDir     = $config->file->import->type->import;

        $validation = 'auto';
        $dir        = $baseDir . $validationDir . $methodDir;
        $fileList   = $this->retrieveFileList($dir);

        foreach ($fileList as $file) {
            $this->insertQueueMessage($file, $validation, Service_Queue_Message::METHOD_IMPORT);
        }
    }

    private function listenImportManual($baseDir, $config)
    {
        $validationDir = $config->file->import->validation->manual;
        $methodDir     = $config->file->import->type->import;

        $validation = 'manual';
        $dir        = $baseDir . $validationDir . $methodDir;
        $fileList   = $this->retrieveFileList($dir);

        foreach ($fileList as $file) {
            $this->insertQueueMessage($file, $validation, Service_Queue_Message::METHOD_IMPORT);
        }
    }


    private function listenRelationAuto($baseDir, $config)
    {
        $validationDir = $config->file->import->validation->auto;
        $methodDir     = $config->file->import->type->relation;

        $validation = 'auto';
        $dir        = $baseDir . $validationDir . $methodDir;
        $fileList   = $this->retrieveFileList($dir);

        foreach ($fileList as $file) {
            $this->insertQueueMessage($file, $validation, Service_Queue_Message::METHOD_RELATION);
        }
    }


    private function retrieveFileList($dir)
    {
        $fileList = array();

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_file($dir .'/'. $file) && substr($file, 0, 1) != '.') {
                        array_push($fileList, $file);
                    }
                }
                closedir($dh);
            }
        }

        return $fileList;
    }


    // TODO refactor me!
    // $fileName, $validation, $method, $historyId
    public function insertQueueMessage($file, $validation, $method)
    {
        $importDao   = new Dao_Import();
        $fileHistory = $importDao->getImportFileHistoryForIdSearch($file, $validation, $method, 'idle');
        $historyId   = $fileHistory[Db_ImportFileHistory::ID];

        $args               = array();
        $args['filename']   = $file;
        $args['validation'] = $validation;
        $args['method']     = $method;

        if ($historyId)
            $args['historyId'] = $historyId;

        $message = new Service_Queue_Message();
        $message->setQueueId(Service_Queue_Message::QUEUE_FILE);
        $message->setArgs($args);
        try {
            Service_Queue_Handler::add($message);
        } catch (Exception $e) {
            // catch alreadyQueued Exceptions
        }
    }
}