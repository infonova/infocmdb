<?php
require_once APPLICATION_PATH . '/modules/cmdb/controllers/AbstractAppAction.php';

class Fileimport_Queue_UpdateController extends AbstractAppAction
{


    /**
     * show auto and manual validation files
     */
    public function indexAction()
    {
        $page = $this->_getParam('page');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->update->auto->destination;
        $dir2   = $config->file->import->update->manual->destination;

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getUpdateQueueItemPaginator($page, 'queue', $dir, $dir2);

        $this->view->filePath  = APPLICATION_URL . '/_uploads/import/update/'; // TODO: welche queue??
        $this->view->paginator = $paginator;
        $this->view->type      = $type;
    }

    public function successAction()
    {
        // no success items in update queue
    }

    public function errorAction()
    {
        // no error items in update queue
    }
}