<?php
require_once APPLICATION_PATH . '/modules/cmdb/controllers/AbstractAppAction.php';

class Fileimport_QueueController extends AbstractAppAction
{


    public function indexAction()
    {
        $page   = $this->_getParam('page');
        $filter = $this->_getParam('filter');

        $queueArray                                              = array();
        $queueArray['all']                                       = $this->translator->translate('queueAll');
        $queueArray[Service_Fileimport_Get::QUEUE_IDLE]          = $this->translator->translate('queueIdle');
        $queueArray[Service_Fileimport_Get::QUEUE_INSERT]        = $this->translator->translate('queueInsert');
        $queueArray[Service_Fileimport_Get::QUEUE_ATTRIBUTE]     = $this->translator->translate('queueAttribute');
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_AUTO]   = $this->translator->translate('queueUpdateAuto');
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_MANUAL] = $this->translator->translate('queueUpdateManual');
        $filterForm                                              = new Form_Fileimport_Filter($this->translator, $queueArray);

        $filter = null;
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $filter   = $formData['search'];
            $this->_redirect('fileimport/queue/type/queue/filter/' . $filter . '/page/' . $page);
        } elseif ($this->_getParam('filter')) {
            $filter = $this->_getParam('filter');
        } else {
            $this->_redirect('fileimport/queue/type/queue/filter/all/page/' . $page);
        }
        $filterForm->populate(array('search' => $filter));

        $config                                               = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dirList                                              = array();
        $dirList[Service_Fileimport_Get::QUEUE_IDLE]          = $config->file->import->idle->destination;
        $dirList[Service_Fileimport_Get::QUEUE_INSERT]        = $config->file->import->insert->destination;
        $dirList[Service_Fileimport_Get::QUEUE_ATTRIBUTE]     = $config->file->import->attribute->destination;
        $dirList[Service_Fileimport_Get::QUEUE_UPDATE_AUTO]   = $config->file->import->update->auto->destination;
        $dirList[Service_Fileimport_Get::QUEUE_UPDATE_MANUAL] = $config->file->import->update->manual->destination;

        if ($filter && $filter != 'all') {
            foreach ($dirList as $key => $dir) {
                if ($filter != $key) {
                    unset($dirList[$key]);
                }
            }
        }

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getQueueItemPaginator($page, 'queue', $dirList);

        $this->view->result     = $this->handleQueue($paginator);
        $this->view->paginator  = $paginator;
        $this->view->filter     = $filterForm;
        $this->view->page       = $page;
        $this->view->filterterm = $filter;
    }


    public function ajaxAction()
    {
        $page   = $this->_getParam('page');
        $filter = $this->_getParam('filter');

        $config                                               = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dirList                                              = array();
        $dirList[Service_Fileimport_Get::QUEUE_IDLE]          = $config->file->import->idle->destination;
        $dirList[Service_Fileimport_Get::QUEUE_INSERT]        = $config->file->import->insert->destination;
        $dirList[Service_Fileimport_Get::QUEUE_ATTRIBUTE]     = $config->file->import->attribute->destination;
        $dirList[Service_Fileimport_Get::QUEUE_UPDATE_AUTO]   = $config->file->import->update->auto->destination;
        $dirList[Service_Fileimport_Get::QUEUE_UPDATE_MANUAL] = $config->file->import->update->manual->destination;

        if ($filter && $filter != 'all') {
            foreach ($dirList as $key => $dir) {
                if ($filter != $key) {
                    unset($dirList[$key]);
                }
            }
        }

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getQueueItemPaginator($page, 'queue', $dirList);

        echo utf8_decode($this->handleQueue($paginator));
        exit;
    }


    private function handleQueue($paginator)
    {
        $view = new Zend_View();
        $view->setEscape('htmlentities');
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/fileimport/queue/');
        $view->setEncoding('UTF-8');
        $view->headMeta()->appendName('charset', "UTF-8");
        $view->doctype("HTML5");

        $view->paginator = $paginator;
        return $view->render('_result.phtml');
    }

    /**
     * show all historized files (success)
     */
    public function successAction()
    {
        $page   = $this->_getParam('page');
        $filter = $this->_getParam('filter');

        $queueArray                                              = array();
        $queueArray['all']                                       = $this->translator->translate('queueAll');
        $queueArray[Service_Fileimport_Get::QUEUE_INSERT]        = $this->translator->translate('queueInsert');
        $queueArray[Service_Fileimport_Get::QUEUE_ATTRIBUTE]     = $this->translator->translate('queueAttribute');
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_AUTO]   = $this->translator->translate('queueUpdateAuto');
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_MANUAL] = $this->translator->translate('queueUpdateManual');
        $filterForm                                              = new Form_Fileimport_Filter($this->translator, $queueArray);

        $filter = null;
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $filter   = $formData['search'];
            $this->_redirect('fileimport/queue/type/queue/method/success/filter/' . $filter . '/page/' . $page);
        } elseif ($this->_getParam('filter')) {
            $filter = $this->_getParam('filter');
        } else {
            $this->_redirect('fileimport/queue/type/queue/method/success/filter/all/page/' . $page);
        }
        $filterForm->populate(array('search' => $filter));

        $queueArray                                              = array();
        $queueArray[Service_Fileimport_Get::QUEUE_INSERT]        = Service_Fileimport_Get::QUEUE_INSERT;
        $queueArray[Service_Fileimport_Get::QUEUE_ATTRIBUTE]     = Service_Fileimport_Get::QUEUE_ATTRIBUTE;
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_AUTO]   = Service_Fileimport_Get::QUEUE_UPDATE_AUTO;
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_MANUAL] = Service_Fileimport_Get::QUEUE_UPDATE_MANUAL;

        if ($filter && $filter != 'all') {
            foreach ($queueArray as $key => $dir) {
                if ($filter != $key) {
                    unset($queueArray[$key]);
                }
            }
        }

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getHistorizedItems($page, $queueArray, Service_Fileimport_Get::STATUS_SUCCESS);

        $this->view->paginator = $paginator;
        $this->view->filter    = $filterForm;
    }


    /**
     * show all files that completed with errors
     */
    public function errorAction()
    {
        $page   = $this->_getParam('page');
        $filter = $this->_getParam('filter');

        $queueArray                                              = array();
        $queueArray['all']                                       = $this->translator->translate('queueAll');
        $queueArray[Service_Fileimport_Get::QUEUE_INSERT]        = $this->translator->translate('queueInsert');
        $queueArray[Service_Fileimport_Get::QUEUE_ATTRIBUTE]     = $this->translator->translate('queueAttribute');
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_AUTO]   = $this->translator->translate('queueUpdateAuto');
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_MANUAL] = $this->translator->translate('queueUpdateManual');
        $filterForm                                              = new Form_Fileimport_Filter($this->translator, $queueArray);

        $filter = null;
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $filter   = $formData['search'];
            $this->_redirect('fileimport/queue/type/queue/method/error/filter/' . $filter . '/page/' . $page);
        } elseif ($this->_getParam('filter')) {
            $filter = $this->_getParam('filter');
        } else {
            $this->_redirect('fileimport/queue/type/queue/method/error/filter/all/page/' . $page);
        }
        $filterForm->populate(array('search' => $filter));

        $queueArray                                              = array();
        $queueArray[Service_Fileimport_Get::QUEUE_INSERT]        = Service_Fileimport_Get::QUEUE_INSERT;
        $queueArray[Service_Fileimport_Get::QUEUE_ATTRIBUTE]     = Service_Fileimport_Get::QUEUE_ATTRIBUTE;
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_AUTO]   = Service_Fileimport_Get::QUEUE_UPDATE_AUTO;
        $queueArray[Service_Fileimport_Get::QUEUE_UPDATE_MANUAL] = Service_Fileimport_Get::QUEUE_UPDATE_MANUAL;

        if ($filter && $filter != 'all') {
            foreach ($queueArray as $key => $dir) {
                if ($filter != $key) {
                    unset($queueArray[$key]);
                }
            }
        }

        $serviceGet = new Service_Fileimport_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator  = $serviceGet->getHistorizedItems($page, $queueArray, Service_Fileimport_Get::STATUS_FAILED);

        $this->view->paginator = $paginator;
        $this->view->filter    = $filterForm;
    }


    public function retryAction()
    {
        $id = $this->_getParam('id');

        $serviceCreate = new Service_Fileimport_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $notification  = array();
        if ($serviceCreate->retryImport($id)) {
            $notification['success'] = $this->translator->translate('retrySuccess');
        } else {
            $notification['error'] = $this->translator->translate('retryFailed');
        }
        $this->_helper->FlashMessenger($notification);
        $this->_redirect('fileimport/queue/type/queue/filter/all/page/');
    }
}