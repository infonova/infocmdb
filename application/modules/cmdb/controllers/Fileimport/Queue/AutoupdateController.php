<?php
require_once APPLICATION_PATH . '/modules/cmdb/controllers/AbstractAppAction.php';

class Fileimport_Queue_AutoupdateController extends AbstractAppAction
{


    public function indexAction()
    {
        $page = $this->_getParam('page');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->update->auto->destination;

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getQueueItemPaginator($page, 'queue', $dir);

        $this->view->filePath  = APPLICATION_URL . '/_uploads/import/update/auto_validation/';
        $this->view->paginator = $paginator;
    }


    /**
     * show all historized files (success)
     */
    public function successAction()
    {
        $page = $this->_getParam('page');

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getHistorizedItems($page, Service_Fileimport_Get::QUEUE_UPDATE_AUTO, Service_Fileimport_Get::STATUS_SUCCESS);

        $this->view->filePath  = APPLICATION_URL . '/_uploads/import/update/auto_validation/';
        $this->view->paginator = $paginator;
    }


    /**
     * show all files that completed with errors
     */
    public function errorAction()
    {
        $page = $this->_getParam('page');

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getHistorizedItems($page, Service_Fileimport_Get::QUEUE_UPDATE_AUTO, Service_Fileimport_Get::STATUS_FAILED);

        $this->view->filePath  = APPLICATION_URL . '/_uploads/import/update/auto_validation/';
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
                $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/autoupdate/');
                break;
            case 'error':
                $serviceDelete->deleteFileImportHistory($historyId);
                $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/autoupdate/method/error');
                break;
            case 'success':
                $serviceDelete->deleteFileImportHistory($historyId);
                $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/autoupdate/method/success');
                break;
            default:
                // do nothing
                $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/autoupdate/');
                break;
        }
        $this->_redirect(APPLICATION_URL . 'fileimport/queue/type/autoupdate/');
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
        $dir    = $config->file->import->update->auto->destination;
        $path   = $dir . '/log/' . $file;

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        return $serviceGet->getLogContent($path);
    }
}