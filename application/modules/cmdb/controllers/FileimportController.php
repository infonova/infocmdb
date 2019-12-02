<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class FileimportController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/fileimport_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/fileimport_en.csv', 'en');
            parent::addUserTranslation('fileimport');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }


    /**
     * Übersicht über Importquellen
     */
    public function indexAction()
    {
        $serviceCreate = new Service_Fileimport_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form          = $serviceCreate->getWizardFunctionForm();
        $uploadform    = $serviceCreate->getWizardUploadForm();
        $cancel        = $this->_getParam('cancel');
        $upload        = $this->_getParam('upload');

        if (isset($cancel)) {
            $fileId = $this->_getParam('fileId');
            if (isset ($fileId)) {
                $importService = new Dao_Import();
                $file          = $importService->getFilenameByHistoryId($fileId);
                $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
                $dir           = $config->file->import->idle->destination;

                $file = $dir .'/'. $file[filename];

                try {
                    $notification = array();
                    $serviceGet   = new Service_Fileimport_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
                    $serviceGet->deleteActiveFileImport($file);
                    $serviceGet->deleteFileImportHistory($fileId);
                    $notification['success'] = $this->translator->translate('fileDeleteSuccess');
                } catch (Exception $e) {
                    $notification['error'] = $this->translator->translate('fileDeleteFailed');
                    $this->_helper->FlashMessenger($notification);
                }
                $this->_helper->FlashMessenger($notification);
            }
        }

        $page      = $this->_getParam('page');
        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $method = $this->_getParam('method');
        $queue  = $this->_getParam('queue');

        $failed = $this->_getParam('failed');


        $filter = null;
        if ($this->_hasParam('search')) {
            if (!$this->_getParam('search')) {
                $filterString = '';
            } else {
                $filterString = '/filter/' . $this->_getParam('search') . '/';
            }
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'citype/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $serviceFileimportGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());


        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($upload) {
                if ($uploadform->isValid($formData)) {
                    try {
                        $uploadValues = $serviceCreate->handleWebUpload($uploadform->getValues(), parent::getUserInformation()->getId());
                        $serviceCreate->getCurrentImportStatus($uploadValues['historyId'], $formData['modus']);

                        $this->view->filename = $uploadValues['fileName'];
                        $this->view->fileId   = $uploadValues['historyId'];

                        $fileType = end(explode(".", $uploadValues['fileName']));
                        $fileType = strtoupper($fileType);

                        $this->view->modus    = $formData['modus'];
                        $this->view->filetype = $fileType;

                        $form->populate($formData);
                    } catch (Exception $e) {
                        $this->logger->log($e, Zend_Log::WARN);
                        $notification          = array();
                        $notification['error'] = $this->translator->translate('fileimportUploadFailed');
                        $this->_helper->FlashMessenger($notification);
                        $this->_redirect(APPLICATION_URL . 'fileimport/index/');
                    }
                }
            } else {
                if ($form->isValid($formData)) {
                    //if a file is already uploaded and the back button was pressed --> show the filename
                    if (isset($fileId)) {
                        try {
                            $importService        = new Dao_Import();
                            $fileName             = $importService->getFilenameByHistoryId($fileId);
                            $this->view->filename = $fileName[filename];
                            $this->view->fileId   = $fileId;
                        } catch (Exception $e) {
                            $this->logger->log($e, Zend_Log::WARN);
                            $notification          = array();
                            $notification['error'] = $this->translator->translate('fileimportGetFilenameFailed');
                            $this->_helper->FlashMessenger($notification);
                        }
                    }
                    if ($this->_request->isPost()) {
                        $formData = $this->_request->getPost();
                        if ($form->isValid($formData)) {
                            try {
                                $uploadValues         = $serviceCreate->handleWebUpload($form->getValues(), parent::getUserInformation()->getId());
                                $this->view->filename = $uploadValues[fileName];
                                $this->view->fileId   = $uploadValues[historyId];
                            } catch (Exception $e) {
                                $this->logger->log($e, Zend_Log::WARN);
                                $notification          = array();
                                $notification['error'] = $this->translator->translate('fileimportUploadFailed');
                                $this->_helper->FlashMessenger($notification);
                            }
                        }
                    }
                }
            }
        }

        $this->view->upload     = $upload;
        $this->view->form       = $form;
        $this->view->uploadform = $uploadform;

        $viewHistory = new Zend_View();
        $viewHistory->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/fileimport/');

        $viewHistory->logPath     = $serviceFileimportGet->getLogPath();
        $viewHistory->historyPath = $serviceFileimportGet->getHistoryPath();
        $viewHistory->paginator   = $serviceFileimportGet->getHistoryPaginator($method, $queue, $failed, $page, $orderBy, $direction, $filter);
        $viewHistory->searchForm  = $serviceFileimportGet->getFilterForm($filter)->setAction($this->view->url(array('filter' => $filter, 'page' => null)));

        $this->view->history = $viewHistory->render('_history.phtml');


    }


    public function uploadAction()
    {
        $this->_helper->layout->setLayout('clean', false);

        $serviceCreate = new Service_Fileimport_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form          = $serviceCreate->getWizardUploadForm();

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                try {
                    $uploadValues         = $serviceCreate->handleWebUpload($form->getValues(), parent::getUserInformation()->getId());
                    $this->view->filename = $uploadValues['fileName'];
                    $this->view->fileId   = $uploadValues['historyId'];
                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::WARN);
                    $notification          = array();
                    $notification['error'] = $this->translator->translate('fileimportUploadFailed');
                    $this->_helper->FlashMessenger($notification);
                    $this->_redirect(APPLICATION_URL . 'fileimport/index/');
                }
            }
        }

        $this->view->form = $form;

    }

    public function ajaximportAction()
    {
        $fileId = $this->_getParam('fileId');
        $modus  = $this->_getParam('modus');
        $start  = ($this->_getParam('start')) ? ($this->_getParam('start')) : false;

        $serviceCreate = new Service_Fileimport_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result        = $serviceCreate->getCurrentImportStatus($fileId, $modus, $start);

        $data = $this->renderImportPage($serviceCreate, $result['processedLines'], $result['totalLines'], $result['failedLines'], $result['successLines'], $result['status'], $fileId, $result['errorCount'], $result['history_id']);
        echo $data;
        exit;
    }


    private function renderImportPage($serviceCreate, $processedLines, $totalLines, $failedLines, $successLines, $status, $fileId, $errorCount, $history_id)
    {
        $view = new Zend_View();
        $view->setEscape('htmlentities');
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/fileimport/');
        $view->setEncoding('UTF-8');
        $view->headMeta()->appendName('charset', "UTF-8");
        $view->doctype("HTML5");

        $view->processedLines = $processedLines;
        $view->totalLines     = $totalLines;
        $view->failedLines    = $failedLines;
        $view->successLines   = $successLines;
        $view->status         = $status;
        $view->fileId         = $fileId;
        $view->errorCount     = $errorCount;
        $view->history_id     = $history_id;


        if ($status == 'failed') {
            $detailedError = $serviceCreate->getFileHistoryDetail($fileId);
            //check if error is avaiable in db
            if (count($detailedError) > 0) {
                $view->detailedError = $detailedError;
            }
        }

        return $view->render('_result.phtml');
    }

    public function resultAction()
    {
        $fileId = $this->_getParam('fileId');

        $serviceCreate = new Service_Fileimport_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result        = $serviceCreate->getCurrentImportStatus($fileId);


        $failedLines = 0;

        if ($result['failedLines'])
            $failedLines = $result['failedLines'];

        $this->view->successLines = $result['successLines'];
        $this->view->totalLines   = $result['totalLines'];
        $this->view->failedLines  = $failedLines;
        $this->view->fileId       = $fileId;
        $this->view->status       = $result['status'];
        $this->view->logPath      =


            $this->_render('_result.phtml');
    }


    public function deletefileAction()
    {

        $fileId        = $this->_getParam('fileId');
        $importService = new Dao_Import();
        $file          = $importService->getFilenameByHistoryId($fileId);
        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir           = $config->file->import->tmp->destination;

        $file = $dir .'/'. $file[filename];

        try {
            $notification = array();
            $serviceGet   = new Service_Fileimport_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $serviceGet->deleteActiveFileImport($file);
            $serviceGet->deleteFileImportHistory($fileId);
            $notification['success'] = $this->translator->translate('fileDeleteSuccess');
        } catch (Exception $e) {
            $notification['error'] = $this->translator->translate('fileDeleteFailed');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect('fileimport/index/upload/1');
        }
        $this->_helper->FlashMessenger($notification);
        $this->_redirect('fileimport/index/upload/1');

    }

    /**
     * show selected queue by the given parameter
     *
     * @param type (attribute/idle/upload/insert) - define queue
     */
    public function queueAction()
    {
        $type   = $this->_getParam('type');
        $method = $this->_getParam('method'); // delete, start, etc

        if (!$type) // TODO: throw exception
            $type = "idle";

        if ($type == 'queue') {
            $type = 'fileimport_queue';
        } else {
            $type = 'fileimport_queue_' . $type;
        }

        if (!$method)
            $method = 'index';

        $this->_forward($method, $type);
    }


    public function deleteAction()
    {
        $file_history_id = $this->_getParam('file_history_id');

        if (is_null($file_history_id)) {
            throw new Exception_InvalidParameter();
        }

        // TODO: delete files in FS

        $fileHistoryDao = new Dao_Import();
        $statusCode     = $fileHistoryDao->deleteImportFileHistoryById($file_history_id);

        $notification = array();
        if ($statusCode) {
            switch ($statusCode) {
                case 1:
                    $notification['success'] = $this->translator->translate('historyfileDeleteSuccess');
                    break;
                default:
                    $notification['error'] = $this->translator->translate('historyfileDeleteFailed');
                    break;
            }
        } else {
            $notification['error'] = $this->translator->translate('historyfileDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('fileimport');
    }


    //creates a csv file containing all errorlines
    public function errorcsvAction()
    {

        $history_id = $this->_getParam('hid');

        $FileImportService = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ret               = $FileImportService->createErrorCSV($history_id);

        $filename     = $ret['fileName'];
        $downloadfile = $ret['fileLocation'];
        $filesize     = filesize($downloadfile);


        header("Content-Type: application/download");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header("Content-Length: $filesize");

        readfile($downloadfile);
        exit;


    }


    public function logAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $id                   = $this->_getParam('id');
        $isAsync              = $this->_getParam('async');
        $serviceFileimportGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());


        $destination = $serviceFileimportGet->getLogPath();

        $historyData = $serviceFileimportGet->getFileimportHistory($id);
        $file        = $historyData[Db_ImportFileHistory::FILENAME];

        $path = $destination .'/'. $id .'/'. $file . '.log';
        $data = $serviceFileimportGet->getLogContent($path);

        if ($isAsync) {
            echo $data;
            exit;
        }

        $this->view->file     = $file;
        $this->view->importId = $id;
        $this->view->log      = $data;
    }

}
