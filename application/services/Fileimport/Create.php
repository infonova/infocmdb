<?php

/**
 *
 *
 *
 */
class Service_Fileimport_Create extends Service_Abstract
{


    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1102, $themeId);
    }


    public function getWizardFunctionForm()
    {
        return new Form_Fileimport_Wizard_Function($this->translator);
    }

    public function getWizardUploadForm()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->destination;
        return new Form_Fileimport_Wizard_Upload($this->translator, $dir);
    }

    public function getCurrentImportStatus($historyId, $modus = null, $start = false)
    {

        $importDao = new Dao_Import();
        $history   = $importDao->getFileHistory($historyId);

        if ($history[Db_ImportFileHistory::QUEUE] == 'idle') {
            $config          = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
            $src             = $config->file->import->tmp->destination;
            $new_destination = $config->file->import->idle->destination;
            $type_auto       = $config->file->import->validation->auto;
            $type_manual     = $config->file->import->validation->manual;
            $attribute       = $config->file->import->type->attribute;
            $relation        = $config->file->import->type->relation;
            $insert          = $config->file->import->type->insert;
            $update          = $config->file->import->type->update;
            $import          = $config->file->import->type->import;
            $method          = 'idle';

            $validation = 'auto';

            switch ($modus) {
                case 'importauto':
                    $destination = $new_destination . $type_auto . $import;
                    $validation  = 'auto';
                    $method      = Service_Queue_Message::METHOD_IMPORT;
                    break;
                case 'attribute':
                    $destination = $new_destination . $type_auto . $attribute;
                    $validation  = 'auto';
                    $method      = Service_Queue_Message::METHOD_ATTRIBUTE;
                    break;
                case 'relation':
                    $destination = $new_destination . $type_auto . $relation;
                    $validation  = 'auto';
                    $method      = Service_Queue_Message::METHOD_RELATION;
                    break;
                case 'importmanual':
                    $destination = $new_destination . $type_manual . $import;
                    $validation  = 'manual';
                    $method      = Service_Queue_Message::METHOD_IMPORT;
                    break;
                default:
                    throw new Exception_FileImport_ImportFailed(); // TODO
            }

            //update history
            if (!$start) {
                $method = "idle";
            }
            $data                                   = array();
            $data[Db_ImportFileHistory::VALIDATION] = $validation;
            $data[Db_ImportFileHistory::QUEUE]      = $method;
            $importDao->updateHistory($historyId, $data);

            if ($start) {
                // move
                $serv = new Service_Queue_Listener_File();
                $serv->insertQueueMessage($history[Db_ImportFileHistory::FILENAME], $validation, $method);
                $this->moveFileWithoutRename($history[Db_ImportFileHistory::FILENAME], $src, $destination);
            }

        }

        $failedResults = $importDao->getCountDetailFileImportHistory($historyId);
        $success       = 0;
        $failed        = $failedResults['cnt'];
        if ($history[Db_ImportFileHistory::LINES_PROCESSED]) {
            if ($failed && $failed != 0) {
                $success = intval($history[Db_ImportFileHistory::LINES_PROCESSED]) - intval($failed);
            } else {
                $success = $history[Db_ImportFileHistory::LINES_PROCESSED];
            }
        }

        $errorCount = $importDao->getCountDetailFileImportHistoryAll($historyId);

        $result                   = array();
        $result['processedLines'] = $history[Db_ImportFileHistory::LINES_PROCESSED];
        $result['totalLines']     = $history[Db_ImportFileHistory::LINES_TOTAL];
        $result['failedLines']    = $failed;
        $result['successLines']   = $success;
        $result['status']         = $history[Db_ImportFileHistory::STATUS];
        $result['errorCount']     = $errorCount['cnt'];
        $result['history_id']     = $history[Db_ImportFileHistory::ID];
        return $result;
    }


    public function handleWebUpload($formData, $userId)
    {
        $config      = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $src         = $config->file->import->destination;
        $file        = $formData['CreateForm']['upload'];
        $destination = $config->file->import->tmp->destination;

        $filename = $this->moveFile($file, $src, $destination);

        $lines = null;
        try {
            $lines = count(file($destination .'/'. $filename));
        } catch (Exception $e) {
            // ignore
        }

        $historyId = $this->historize($userId, 'idle', $filename, 'idle', "", $lines, 0);
        // TODO: create history entry!

        if (!$historyId)
            throw new Exception(); // TODO


        return array(
            'historyId' => $historyId,
            'fileName'  => $filename,
        );

        //return $historyId;
    }

    private function moveFile($file, $src, $destination)
    {
        // $date = date("YmdHms\_");
        // $newFile = $date.$file;     // To prevent import quueue probs (filename citype,project)
        $newFile = $date . $file;

        if (!rename($src .'/'. $file, $destination .'/'. $newFile)) {
            throw new Exception(); // TODO
        }
        chmod($destination .'/'. $newFile, 0777);

        return $newFile;
    }

    private function moveFileWithoutRename($file, $src, $destination)
    {
        if (!rename($src .'/'. $file, $destination .'/'. $file)) {
            throw new Exception(); // TODO
        }
        return true;
    }


    private function historize($userId, $status, $filename, $queue, $note = "", $totalLines = null, $processedLines = null)
    {
        $data                                        = array();
        $data[Db_ImportFileHistory::FILENAME]        = $filename;
        $data[Db_ImportFileHistory::USER_ID]         = $userId;
        $data[Db_ImportFileHistory::QUEUE]           = $queue;
        $data[Db_ImportFileHistory::STATUS]          = $status;
        $data[Db_ImportFileHistory::LINES_TOTAL]     = $totalLines;
        $data[Db_ImportFileHistory::LINES_PROCESSED] = $processedLines;
        $data[Db_ImportFileHistory::NOTE]            = $note;

        $importDao = new Dao_Import();
        return $importDao->historizeFileImport($data);
    }


    public function retryImport($id)
    {
        try {
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);

            $importDao = new Dao_Import();
            $file      = $importDao->getSingleImportHistory($id);
            $src       = "";
            $dest      = "";
            $filename  = $file[Db_ImportFileHistory::FILENAME];

            switch ($file[Db_ImportFileHistory::QUEUE]) {
                case 'insert':
                    $src  = $config->file->import->insert->error->destination;
                    $dest = $config->file->import->insert->destination;
                    break;
                case 'attribute':
                    $src  = $config->file->import->attribute->error->destination;
                    $dest = $config->file->import->attribute->destination;
                    break;
                case 'update_auto':
                    $src  = $config->file->import->update->auto->error->destination;
                    $dest = $config->file->import->update->auto->destination;
                    break;
                case 'update_manual':
                    $src  = $config->file->import->update->manual->error->destination;
                    $dest = $config->file->import->update->manual->destination;
                    break;
            }

            if (!rename($src .'/'. $id .'/'. $filename, $dest .'/'. $filename)) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::WARN);
            return false;
        }


    }

    public function getFileHistoryDetail($historyId)
    {
        $importDao = new Dao_Import();
        return $importDao->getDetailFileImportHistory($historyId);
    }

}