<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class ReportingController extends AbstractAppAction
{
    private static $reportingNamespace = 'ReportingController';

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/reporting_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/reporting_en.csv', 'en');
            parent::addUserTranslation('reporting');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function itemsperpageAction()
    {
        $itemCountPerPageSession                                = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['reporting'] = $this->_getParam('rowCount');
        $this->_redirect('reporting/index');
        exit;
    }

    public function indexAction()
    {

        $this->setupItemsCountPerPage('reporting');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->reportingPage)) {
            $page = $pageSession->reportingPage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                       = $this->_getParam('page');
                $pageSession->reportingPage = $page;
            }
        } else {
            $page                       = $this->_getParam('page');
            $pageSession->reportingPage = $page;
        }

        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $filter = null;
        if ($this->_hasParam('search')) {
            if (!$this->_getParam('search')) {
                $filterString = '';
            } else {
                $filterString = '/filter/' . $this->_getParam('search') . '/';
            }
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'reporting/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $reportingService = new Service_Reporting_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result           = $reportingService->getReportingList($page, $orderBy, $direction, $filter);

        $this->view->searchForm   = $reportingService->getFilterForm($filter);
        $this->view->page         = $page;
        $this->view->orderBy      = $orderBy;
        $this->view->direction    = $direction;
        $this->view->filter       = $filter;
        $this->view->paginator    = $result['paginator'];
        $this->view->receiverList = $result['reportingList'];
    }


    public function createAction()
    {
        $this->logger->log('createAction page invoked', Zend_Log::DEBUG);
        $reportingServiceCreate = new Service_Reporting_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form                   = $reportingServiceCreate->getReportingCreateForm();

        if ($this->_request->isPost() || $isBack) {
            $formData = $this->_request->getPost();

            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $reportingId             = $reportingServiceCreate->createReporting($formData);
                    $notification['success'] = $this->translator->translate('reportingCreateSuccess');
                } catch (Exception_Reporting_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new Report', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('TODO');
                }
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('reporting/index');
            } else {
                $form->populate($formData);
            }
        }
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce.js');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce_init_' . $this->translator->getLocale() . '.js');

        $this->view->cronform = $reportingServiceCreate->getReportingTriggerForm();
        $this->view->form     = $form;
    }

    public function editAction()
    {
        $this->logger->log('editAction page invoked', Zend_Log::DEBUG);
        $reportingId            = $this->_getParam('reportingId');
        $this->elementId        = $reportingId;
        $reportingServiceUpdate = new Service_Reporting_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form                   = $reportingServiceUpdate->getReportingUpdateForm($reportingId);

        $reportingServiceGet = new Service_Reporting_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $dbData              = $reportingServiceGet->getReportingData($reportingId);
        $this->elementId     = $dbData[Db_Reporting::NAME];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $check = $reportingServiceUpdate->updateReporting($reportingId, $formData, $dbData);
                    if ($check) $notification['success'] = $this->translator->translate('reportingUpdateSuccess');
                } catch (Exception_Reporting_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new Report', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('TODO');
                }
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('reporting/index');
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($dbData);
        }

        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce.js');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce_init_' . $this->translator->getLocale() . '.js');

        $this->view->cronform  = $reportingServiceUpdate->getReportingTriggerForm($dbData[Db_Reporting::EXECUTION_TIME]);
        $this->view->reporting = $dbData;
        $this->view->form      = $form;
    }

    public function inputwizardAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $type        = $this->_getParam('type');
        $reportingId = $this->_getParam('reportingId');

        $reportingServiceCreate = new Service_Reporting_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result                 = $reportingServiceCreate->getReportingInputForm($type);

        $form   = $result['form'];
        $render = $result['render'];

        if ($reportingId) {
            $reportingServiceGet = new Service_Reporting_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $reporting           = $reportingServiceGet->getReportingData($reportingId);
            $form->populate($reporting);
        }

        $this->view->type = $type;
        $this->view->form = $form;
        $this->render($render);
    }


    public function inputAction()
    {
        $type             = $this->_getParam('type');
        $reportingId      = $this->_getParam('reportingId');
        $this->view->type = $type;

        $reportingServiceUpdate = new Service_Reporting_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $result                 = $reportingServiceUpdate->getReportingInputUpdateForm($type);

        $form   = $result['form'];
        $render = $result['render'];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();

            if ($form->isValid($formData)) {
                $notification = array();
                try {
                    $reportingServiceUpdate->updateReporting($formData, $reportingId);
                } catch (Exception_Reporting_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown error while creating new Report', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('TODO');

                    $this->_helper->FlashMessenger($notification);
                    $this->_redirect('reporting/index');
                }

                $this->_redirect('reporting/index');
            } else {
                $form->populate($formData);
            }
        } else {
            $reportingServiceGet = new Service_Reporting_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $formData            = $reportingServiceGet->getReportingForInputUpdate($reportingId);
            $form->populate($formData);
        }

        $this->view->form = $form;
        $this->render($render);
    }


    public function detailAction()
    {
        $this->logger->log('detailAction', Zend_Log::DEBUG);
        $reportingId     = $this->_getParam('reportingId');
        $this->elementId = $reportingId;

        $reportingServiceGet = new Service_Reporting_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $reporting           = $reportingServiceGet->getReporting($reportingId);
        $archive             = $reportingServiceGet->getReportingArchive($reportingId);
        $this->elementId     = $reporting[Db_Reporting::NAME];

        $url                   = APPLICATION_URL . "download/report/id/" . $reportingId;
        $this->view->reporting = $reporting;
        $this->view->archive   = $archive;
        $this->view->filepath  = $url;
    }

    public function removearchiveAction()
    {
        $reportingId = $this->_getParam('reportingId');
        $archiveId   = $this->_getParam('archiveId');

        $reportingServiceDelete = new Service_Reporting_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $reportingServiceDelete->removeSingleArchive($archiveId);

        $this->_redirect(APPLICATION_URL . 'reporting/detail/reportingId/' . $reportingId);
    }


    public function deleteAction()
    {
        $reportingId = $page = $this->_getParam('reportingId');
        if (!$reportingId) {
            throw new Exception_InvalidParameter();
        }
        $notification = array();
        try {
            $reportingService = new Service_Reporting_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $statusCode       = $reportingService->deleteReporting($reportingId);
            if ($statusCode) {
                switch ($statusCode) {
                    case 1:
                        $notification = array('success' => $this->translator->translate('reportingDeleteSuccess'));
                        break;
                    case 2:
                        $notification = array('success' => $this->translator->translate('reportingDeactivationSuccess'));
                        break;
                    default:
                        $notification = array('success' => $this->translator->translate('reportingDeleteFailed'));
                        break;
                }
            } else {
                $notification = array('success' => $this->translator->translate('reportingDeleteFailed'));
            }

        } catch (Exception $e) {
            $notification['error'] = $this->translator->translate('reportingDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('reporting/index');
    }

    public function executeAction()
    {
        $reportingId = $this->_getParam('reportingId');
        $userId      = parent::getUserInformation()->getId();

        $reportingService = new Service_Reporting_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $reporting        = $reportingService->getReporting($reportingId);

        $message = new Service_Queue_Message();
        $message->setPriority(1);
        $message->setQueueId(4);
        $message->setStatus('idle');
        $message->setUserId($userId);
        $message->setArgs(array(
            'reportingIg'   => $reportingId,
            'reportingName' => $reporting[db_Reporting::NAME],
            'userId'        => $userId,
        ));


        $notification = array();
        try {
            Service_Queue_Handler::add($message);
            $notification['success'] = $this->translator->translate('reportStartSuccess');
        } catch (Exception $e) {
            $notification['error'] = $this->translator->translate('reportAlreadyStarted');
        }
        $this->_helper->FlashMessenger($notification);
        $this->_redirect('reporting/detail/reportingId/' . $reportingId);
    }

    public function activateAction()
    {
        $reportingId = $this->_getParam('reportingId');

        $notification = array();
        try {
            $reportingService        = new Service_Reporting_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $reporting               = $reportingService->activateReporting($reportingId);
            $notification['success'] = $this->translator->translate('reportingActivateSuccess');
        } catch (Exception $e) {
            $notification['error'] = $this->translator->translate('reportingActivatingFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('reporting/index/');
    }
}