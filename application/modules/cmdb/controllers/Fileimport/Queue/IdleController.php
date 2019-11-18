<?php
require_once APPLICATION_PATH . '/modules/cmdb/controllers/AbstractAppAction.php';

class Fileimport_Queue_IdleController extends AbstractAppAction
{

    /**
     * show current queue
     */
    public function indexAction()
    {
        $page = $this->_getParam('page');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->idle->destination;

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getQueueItemPaginator($page, 'queue', $dir);

        $this->view->filePath  = APPLICATION_URL . '/_uploads/import/idle/';
        $this->view->paginator = $paginator;
        $this->view->type      = $type;
    }


    public function successAction()
    {
        // no success items in idle queue
    }

    public function errorAction()
    {
        // no error items in idle queue
    }

    public function importAction()
    {
        $queue = $this->_getParam('folder');
        $file  = $this->_getParam('file');

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->moveIdleFileToQueue($file, $queue);

        $this->_redirect(APPLICATION_URL . '/fileimport/queue/type/queue');
    }


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
        $dir    = $config->file->import->idle->destination;
        $path   = $dir . '/log/' . $file;

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        return $serviceGet->getLogContent($path);
    }


    public function deleteAction()
    {
        $file = $this->_getParam('file');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->idle->destination;

        $file = $dir .'/'. $file;

        try {
            $notification = array();

            $serviceGet = new Service_Fileimport_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $serviceGet->deleteActiveFileImport($file);
            $notification['success'] = $this->translator->translate('queueIdleDeleteSuccess');
        } catch (Exception $e) {
            $notification['success'] = $this->translator->translate('queueIdleDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('fileimport/queue/type/queue/filter/all/page/');
    }
}