<?php
require_once APPLICATION_PATH . '/modules/cmdb/controllers/AbstractAppAction.php';

class Fileimport_Queue_InsertController extends AbstractAppAction
{


    public function indexAction()
    {
        $page = $this->_getParam('page');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->insert->destination;

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getQueueItemPaginator($page, 'queue', $dir);

        $this->view->filePath  = APPLICATION_URL . '/_uploads/import/insert/';
        $this->view->paginator = $paginator;
    }


    /**
     * show all historized files (success)
     */
    public function successAction()
    {
        $page = $this->_getParam('page');

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getHistorizedItems($page, Service_Fileimport_Get::QUEUE_INSERT, Service_Fileimport_Get::STATUS_SUCCESS);

        $this->view->filePath  = APPLICATION_URL . '/_uploads/import/insert/';
        $this->view->paginator = $paginator;
    }


    /**
     * show all files that completed with errors
     */
    public function errorAction()
    {
        $page = $this->_getParam('page');

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getHistorizedItems($page, Service_Fileimport_Get::QUEUE_INSERT, Service_Fileimport_Get::STATUS_FAILED);

        $this->view->filePath  = APPLICATION_URL . '/_uploads/import/insert/';
        $this->view->paginator = $paginator;
    }

    /**
     * used to delete files or import history entries
     */
    public function deleteAction()
    {
        $status    = $this->_getParam('status'); // define queue (error/idle/success) 
        $file      = $this->_getParam('file'); // idle queue only
        $historyId = $this->_getParam('id'); // error/success queue only

        $serviceDelete = new Service_Fileimport_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        switch ($status) {
            case 'idle':
                $serviceDelete->deleteActiveFileImport($file);
                $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/insert/');
                break;
            case 'error':
                $serviceDelete->deleteFileImportHistory($historyId);
                $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/insert/method/error');
                break;
            case 'success':
                $serviceDelete->deleteFileImportHistory($historyId);
                $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/insert/method/success');
                break;
            default:
                // do nothing
                $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/insert/');
                break;
        }
        $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/insert/');
    }


    /**
     * show logfile for given file
     * incl. auto-refresh
     */
    public function logAction()
    {
        $this->_helper->layout->setLayout('popup', false);
        $file = $this->_getParam('file');

        $data = $this->getLogData($file);

        $this->view->file = $file;
        $this->view->log  = $data;
    }


    public function ajaxlogAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $file = $this->_getParam('file');

        $data = $this->getLogData($file);

        echo $data;
        exit;
    }


    private function getLogData($file)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->insert->destination;
        $path   = $dir . '/log/' . $file;

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        return $serviceGet->getLogContent($path);
    }
}