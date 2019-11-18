<?php

class Import_File_Type_XLS implements Import_File_Type
{

    private $logger        = null;
    private $file          = null;
    private $historyId     = null;
    private $options       = array();
    private $totalLines    = null;
    private $currentLine   = null;
    private $attributeList = null;
    private $errors        = 0;


    public function __construct($logger, $file, $historyId, $options = array())
    {
        $this->logger    = $logger;
        $this->file      = $file;
        $this->historyId = $historyId;
        $this->options   = $options;

        try {

            $this->totalLines = count(file($this->file));
            Import_File_Util_History::updateHistoryLines($this->historyId, 0, $this->totalLines);
        } catch (Exception $e) {
            $this->logger->log("exception incsv", Zend_Log::CRIT);
        }
    }


    public function getTotalLines()
    {
        return $this->totalLines;
    }

    public function import($callback)
    {
        $callback = new $callback();
        $this->logger->log('[START] xls import', Zend_Log::CRIT);

        try {
            $objReader = new PHPExcel_Reader_Excel5();

            $objPHPExcel = $objReader->load($this->file);
            $objWriter   = new PHPExcel_Writer_Array($objPHPExcel);
            $fileData    = $objWriter->generateSheetData();
        } catch (Exception $e) {
            $this->logger->log('failed to import xlsx file!', Zend_Log::ERR);
            $this->logger->log($e, Zend_Log::ERR);
            return false;
        }


        $this->totalLines = count($fileData);
        Import_File_Util_History::updateHistoryLines($this->historyId, 0, $this->totalLines);


        try {
            $success = true;

            $this->currentLine = 1;

            foreach ($fileData as $line => $data) {
                $checkData = array_filter($data);
                if ($data && !empty($checkData)) { // check if line is empty
                    $result = null;
                    if ($this->currentLine == 1) {
                        // load attributes
                        $result              = $callback->getAttributeList($data, $this->logger);
                        $this->attributeList = $result['attributes'];
                    } else {
                        // load data
                        $this->options['line'] = $this->currentLine;
                        $result                = $callback->import($this->logger, $this->historyId, $data, $this->attributeList, $this->options);
                    }

                    if (!Import_File_Util_Error::checkReturnStatus($result, $this->historyId, $this->currentLine)) {
                        $success = false;
                        $this->errors++;

                        // skip complete file if there are no attributes
                        if (empty($this->attributeList)) {
                            break;
                        }
                    }
                } else {
                    $this->logger->log("line: " . $this->currentLine . " => Skipped empty line!", Zend_Log::INFO);
                }

                $this->currentLine++;
                Import_File_Util_History::updateHistoryLines($this->historyId, $this->currentLine);
            }

            // finalize
            $result = $callback->finalize($this->logger);
            if (!Import_File_Util_Error::checkReturnStatus($result, $this->historyId, 0)) {
                $success = false;
            }

            $this->logger->log('[END] xls import', Zend_Log::CRIT);
            $this->logger->log('XLS import finished with ' . $this->errors . ' errors', Zend_log::INFO);
            return $success;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            return false;
        }

    }
}