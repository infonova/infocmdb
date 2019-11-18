<?php

class Import_File_Util_History
{

    const QUEUE_IDLE      = 'idle';
    const QUEUE_ATTRIBUTE = 'attribute';
    const QUEUE_INSERT    = 'insert';
    const QUEUE_UPDATE    = 'update';
    const QUEUE_IMPORT    = 'import';
    const QUEUE_RELATION  = 'relation';

    const VALIDATION_AUTO   = 'auto';
    const VALIDATION_MANUAL = 'manual';

    public static function createHistory($status, $filename, $queue, $note = "", $totalLines = null, $processedLines = null)
    {
        $data                                        = array();
        $data[Db_ImportFileHistory::FILENAME]        = $filename;
        $data[Db_ImportFileHistory::QUEUE]           = $queue;
        $data[Db_ImportFileHistory::STATUS]          = $status;
        $data[Db_ImportFileHistory::LINES_TOTAL]     = $totalLines;
        $data[Db_ImportFileHistory::LINES_PROCESSED] = $processedLines;
        $data[Db_ImportFileHistory::NOTE]            = $note;

        $importDao = new Dao_Import();
        return $importDao->historizeFileImport($data);
    }

    public static function updateHistoryQueue($historyId, $queue)
    {
        $data                              = array();
        $data[Db_ImportFileHistory::QUEUE] = $queue;
        $importDao                         = new Dao_Import();
        $importDao->updateHistory($historyId, $data);
    }

    public static function updateHistoryValidation($historyId, $validation)
    {
        $data                                   = array();
        $data[Db_ImportFileHistory::VALIDATION] = $validation;
        $importDao                              = new Dao_Import();
        $importDao->updateHistory($historyId, $data);
    }

    public static function updateHistoryStatus($historyId, $status)
    {
        $data                               = array();
        $data[Db_ImportFileHistory::STATUS] = $status;
        $importDao                          = new Dao_Import();
        $importDao->updateHistory($historyId, $data);
    }

    public static function updateHistoryLines($historyId, $currentLine, $totalLines = null)
    {
        $data = array();
        if ($currentLine)
            $data[Db_ImportFileHistory::LINES_PROCESSED] = $currentLine;

        if ($totalLines)
            $data[Db_ImportFileHistory::LINES_TOTAL] = $totalLines;
        $importDao = new Dao_Import();
        $importDao->updateHistory($historyId, $data);
        $importDao   = null;
        $data        = null;
        $currentLine = null;
        $totalLines  = null;
    }


    public static function addErrorHistory($historyId, $line, $column, $message)
    {
        $data                                                     = array();
        $data[Db_ImportFileHistoryDetail::IMPORT_FILE_HISTORY_ID] = $historyId;
        $data[Db_ImportFileHistoryDetail::LINE]                   = $line;
        $data[Db_ImportFileHistoryDetail::COLUMN]                 = $column;
        $data[Db_ImportFileHistoryDetail::MESSAGE]                = $message;
        $importDao                                                = new Dao_Import();
        $importDao->insertImportFileErrorHistory($data);
        $importDao = null;
    }


    public static function generateValidationId($type, $filename)
    {
        $data                                  = array();
        $data[Db_ImportFileValidation::TYPE]   = $type;
        $data[Db_ImportFileValidation::NAME]   = $filename;
        $data[Db_ImportFileValidation::STATUS] = 'in_progress';
        $importDao                             = new Dao_Import();
        return $importDao->insertValidation($data);
    }

    public static function checkFinalizeValidation($validationId)
    {
        $validationDaoImpl = new Dao_Validation();
        $attributes        = $validationDaoImpl->getValidationAttribtuesCheck($validationId);

        if (count($attributes) > 0) {
            return;
        } else {
            $validationDaoImpl->matchImportFile($validationId);
        }
    }


    public static function getImportUser($historyId)
    {
        try {
            $importDao = new Dao_Import();
            $res       = $importDao->getFileHistory($historyId);

            if ($res && $res[Db_ImportFileHistory::USER_ID]) {
                return $res[Db_ImportFileHistory::USER_ID];
            } else {
                return '0';
            }

        } catch (Exception $e) {
            return '0';
        }
    }
}