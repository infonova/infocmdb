<?php

class Import_File_Type_CSV implements Import_File_Type
{

    private $logger        = null;
    private $file          = null;
    private $historyId     = null;
    private $fileHistoryId = null;
    private $options       = array();
    private $totalLines    = null;
    private $currentLine   = null;
    private $attributeList = null;
    private $userId        = null;
    private $errors        = 0;


    public function __construct($logger, $file, $historyId, $options = array())
    {
        $this->logger        = $logger;
        $this->file          = $file;
        $this->fileHistoryId = $historyId;
        $this->options       = $options;

        try {
            $this->totalLines = 1;

            $handle = fopen($this->file, "r");
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                while (($data = fgetcsv($handle, 0, ';', '"', '"')) !== false) {
                    $this->totalLines++;
                }
                fclose($handle);
            }


            Import_File_Util_History::updateHistoryLines($this->fileHistoryId, 0, $this->totalLines);
            $this->userId = Import_File_Util_History::getImportUser($this->fileHistoryId);
        } catch (Exception $e) {
            $this->logger->log("exception parsing the csv file", Zend_Log::CRIT);
            $this->logger->log($e, Zend_Log::CRIT);
        }
    }


    public function getTotalLines()
    {
        return $this->totalLines;
    }

    public function import($callback)
    {
        $callback = new $callback();
        $this->logger->log('[START] csv import', Zend_Log::CRIT);


        try {
            $handle  = fopen($this->file, "r");
            $success = true;

            if (flock($handle, LOCK_EX | LOCK_NB)) { // do an exclusive lock
                $this->currentLine = 1;
                gc_enable();//enable garbage collection 
                while (($data = fgetcsv($handle, 0, ';', '"', '"')) !== false) {
                    $checkData = array_filter($data);
                    if ($data && !empty($checkData)) { // check if line is empty
                        $result = null;
                        if ($this->currentLine == 1) {
                            // load attributes
                            $result              = $callback->getAttributeList($data, $this->logger);
                            $this->attributeList = $result['attributes'];

                        } else {
                            // load data
                            $this->options['line']   = $this->currentLine;
                            $this->options['userId'] = $this->userId;
                            $result                  = $callback->import($this->logger, $this->historyId, $data, $this->attributeList, $this->options);

                            if (!$this->historyId && $result['historyId']) {
                                $this->historyId = $result['historyId'];
                            }

                        }


                        if (!Import_File_Util_Error::checkReturnStatus($result, $this->fileHistoryId, $this->currentLine)) {
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
                    Import_File_Util_History::updateHistoryLines($this->fileHistoryId, $this->currentLine, null);


                    gc_collect_cycles();//force garbage collection
                }
                fclose($handle);
            }

            // finalize
            $result = $callback->finalize($this->logger);
            if (!Import_File_Util_Error::checkReturnStatus($result, $this->fileHistoryId, 0)) {
                $success = false;
            }

            $this->logger->log('[END] csv import', Zend_Log::CRIT);
            $this->logger->log('CSV import finished with ' . $this->errors . ' errors', Zend_log::INFO);


            return $success;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            fclose($handle);
            // TODO: handle Exception

            return false;
        }

    }
}