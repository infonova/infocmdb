<?php

/**
 *
 *
 *
 */
class Service_Fileimport_Get extends Service_Abstract
{

    const STATUS_SUCCESS = "success";
    const STATUS_FAILED  = "failed";

    const QUEUE_IDLE          = "idle";
    const QUEUE_INSERT        = "insert";
    const QUEUE_ATTRIBUTE     = "attribute";
    const QUEUE_UPDATE_AUTO   = "update_auto";
    const QUEUE_UPDATE_MANUAL = "update_manual";

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1101, $themeId);
    }

    public function getUpdateQueueItemPaginator($page, $type, $dir1, $dir2)
    {
        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/import.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->$type->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->$type->itemsPerPage;
        $scrollingStyle    = $config->pagination->$type->scrollingStyle;
        $scrollingControl  = $config->pagination->$type->scrollingControl;

        $fileList  = $this->retrieveFileList($dir1);
        $fileList2 = $this->retrieveFileList($dir2);

        foreach ($fileList2 as $file) {
            array_push($fileList, $file);
        }


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($fileList));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return $paginator;
    }

    public function getQueueItemPaginator($page, $type, $dirList = array())
    {
        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/import.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->$type->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->$type->itemsPerPage;
        $scrollingStyle    = $config->pagination->$type->scrollingStyle;
        $scrollingControl  = $config->pagination->$type->scrollingControl;

        $fileList = array();
        foreach ($dirList as $queue => $dir) {
            $list = $this->retrieveFileList($dir);
            foreach ($list as $l) {
                $l[Db_ImportFileHistory::QUEUE] = $queue;

                if ($queue != self::QUEUE_IDLE) {
                    // TODO: lookup for history entry
                    $importDao = new Dao_Import();
                    $history   = $importDao->searchActiveHistoryEntry($l[Db_ImportFileHistory::FILENAME], $queue);

                    if ($history) {
                        $l = $history;
                    }
                }
                array_push($fileList, $l);
            }
        }

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($fileList));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return $paginator;
    }


    private function retrieveFileList($dir)
    {
        $fileList = array();

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_file($dir .'/'. $file) && substr($file, 0, 1) != '.') {
                        $mod = date("Y-m-d, H:i:s", filemtime($dir .'/'. $file));
                        array_push($fileList, array(Db_ImportFileHistory::FILENAME => $file, Db_ImportFileHistory::CREATED => $mod));
                    }
                }
                closedir($dh);
            }
        }

        return $fileList;
    }


    public function getHistorizedItems($page, $queue, $status)
    {
        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/import.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->queue->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->queue->itemsPerPage;
        $scrollingStyle    = $config->pagination->queue->scrollingStyle;
        $scrollingControl  = $config->pagination->queue->scrollingControl;

        $importDao = new Dao_Import();
        $select    = $importDao->getImportFileHistoryForPagination($queue, $status);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return $paginator;
    }


    public function getLogContent($path)
    {
        try {
            if (file_exists($path) && is_readable($path)) {
                $data = file_get_contents($path);

                if (!$data)
                    return "";

                return $data;
            } else {
                return "";
            }
        } catch (Exception $e) {
            // TODO: handle me!
            $this->logger->log($e, Zend_Log::WARN);
            return "";
        }
    }


    public function moveIdleFileToQueue($filename, $queue)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->idle->destination;


        $destinationDir;
        switch ($queue) {
            case 'attribute':
                $destinationDir = $config->file->import->attribute->destination;
                break;
            case 'insert':
                $destinationDir = $config->file->import->insert->destination;
                break;
            case 'autoupdate':
                $destinationDir = $config->file->import->update->auto->destination;
                break;
            case 'manualupdate':
                $destinationDir = $config->file->import->update->manual->destination;
                break;
            default:
                // invalid queue
                return false;
                break;
        }

        if (!$destinationDir)
            return false; // invalid queue


        if (!rename($dir .'/'. $filename, $destinationDir .'/'. $filename)) {
            // TODO: throw exception??
            return false;
        }

        return true;
    }

    //creates a csv file containig all error lines based on its history_id

    public function createErrorCSV($history_id)
    {

        try {

            $config       = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
            $fold         = $config->file->import->destination;
            $history_fold = $fold . 'history/' . $history_id . '/';

            $import_dao = new Dao_Import();
            $history    = $import_dao->getFileHistory($history_id);
            // invalid history_id / file does not exist -> exception
            if (!$history || !file_exists($history_fold . $history[Db_ImportFileHistory::FILENAME])) {
                throw new Exception_File_NotFound('filenotfount', 1);
            }
            // filename from db
            $filename = $history[Db_ImportFileHistory::FILENAME];
            // name of file without extension
            $origFilename = pathinfo($filename, PATHINFO_FILENAME);
            // name the error csv gets
            $errorFileName = $origFilename . "_errors.csv";


            //contains all lines with errors (array)
            $lines = $import_dao->getErrorLinesHistory($history_id);
            // gets all errors in header line
            $headerErrors      = $import_dao->getHeaderErrorHistory($history_id);
            $countHeaderErrors = count($headerErrors);
            $currentline       = 1;

            // append errors to file
            $errors = $import_dao->getDetailFileImportHistory($history_id);

            // iterate through each error and append errorArray
            $errorArray = array();
            foreach ($errors as $error) {
                $errorMessage                                         = $this->translator->translate($error[Db_ImportFileHistoryDetail::MESSAGE]);
                $errorText                                            = "Column " . $error[Db_ImportFileHistoryDetail::COLUMN] . " : " . $errorMessage;
                $errorArray[$error[Db_ImportFileHistoryDetail::LINE]] = $errorText;
            }

            //read old csv file and write a new one only containing errorlines || all lines if there is an error in the header
            if (($handle = fopen($history_fold . $filename, "r")) !== false) {
                if (($fp = fopen($history_fold . $errorFileName, "w")) !== false) {
                    while (($data = fgets($handle)) !== false) {
                        if ($currentline == 1) {
                            // remove unwanted chars from last 5 chars of header (data could end with newline or csv delimiter)
                            $data = rtrim($data);
                            // add column starting with #ERROR -> ignored on import
                            $data .= ";#ERROR";
                            if (array_key_exists($currentline, $errorArray)) {
                                $data .= " " . $errorArray[$currentline];
                            }
                            // add back newline
                            $data .= "\r\n";

                            fputs($fp, $data);
                        } else {
                            // if there are errors in the header -> add everything to error.csv 
                            if ($countHeaderErrors > 0) {
                                fputs($fp, $data);
                            } else if (array_key_exists($currentline, $errorArray)) {
                                // check if line has error -> append error to datastring

                                // remove unwanted chars from last 5 chars of data (data could end with newline or csv delimiter)
                                $data = rtrim($data);
                                $data .= ";" . $errorArray[$currentline] . "\r\n";
                                fputs($fp, $data);
                            }
                        }
                        $currentline++;
                    }

                    fclose($fp);
                }


                fclose($handle);
            }


            return array(
                'fileName'     => $errorFileName,
                'fileLocation' => $history_fold . $errorFileName,
            );


        } catch (Exception $e) {

            throw new Exception_File_NotFound('filenotfount', 1);


        }


    }


    public function getHistoryPaginator($method, $queue, $failed, $page, $orderBy, $direction, $filter)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/import.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->queue->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->queue->itemsPerPage;
        $scrollingStyle    = $config->pagination->queue->scrollingStyle;
        $scrollingControl  = $config->pagination->queue->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }


        $fileHistoryDao = new Dao_Import();

        // $method, $queue, $failed, $page, $orderBy, $direction, $filter
        $select = $fileHistoryDao->getFileHistoryForPagination($page);


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return $paginator;
    }

    public function getFilterForm($filter = null)
    {
        $form = new Form_Filter($this->translator);
        if ($filter) {
            $form->populate(array('search' => $filter));
        }
        return $form;
    }


    public function getLogPath()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dest   = $config->file->import->destination;
        $fold   = $config->file->import->log;

        return $dest . $fold;
    }

    public function getHistoryPath()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dest   = $config->file->import->destination;
        $fold   = $config->file->import->history;

        return $dest . $fold;
    }

    public function getFileimportHistory($id)
    {
        $fileHistoryDao = new Dao_Import();
        return $fileHistoryDao->getFileHistory($id);
    }
}