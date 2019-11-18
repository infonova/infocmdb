<?php
require_once 'AbstractAppAction.php';

/**
 *
 * TODO: this dashboard is currently unused. check if we can remove it
 *
 */
class DashboardController extends AbstractAppAction
{

    public function init()
    {
        parent::init();
        parent::setTranslatorLocal();
        /* Initialize action controller here */
        try {
            $this->translator->addTranslation($this->languagePath . '/de/dashboard_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/dashboard_en.csv', 'en');
            parent::addUserTranslation('dashboard');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function indexAction()
    {
        $day   = $this->_getParam('day');
        $month = $this->_getParam('month');
        $year  = $this->_getParam('year');

        $calendar             = Util_Calendar::get($year, $month, parent::getUserInformation()->getId(), parent::getCurrentProjectId(), true);
        $this->view->calendar = $calendar;

        if (!$year)
            $year = date('Y');

        if (!$month)
            $month = date('m');

        if (!$day)
            $day = date('d');

        $nextYear = $year;
        $prevYear = $year;

        $nextMonth = $month + 1;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        $prevMonth = $month - 1;
        if ($prevMonth <= 0) {
            $prevMonth = 12;
            $prevYear--;
        }

        $selectedDate = new Zend_Date();
        $selectedDate->setYear($year);
        $selectedDate->setMonth($month);
        $selectedDate->setDay($day);


        $stringMonth           = date('M', mktime(0, 0, 0, $month, 1, $year));
        $this->view->monthName = $stringMonth;
        $this->view->month     = $month;
        $this->view->year      = $year;

        $this->view->nextYear  = $nextYear;
        $this->view->prevYear  = $prevYear;
        $this->view->nextMonth = $nextMonth;
        $this->view->prevMonth = $prevMonth;

        $dashboardService = new Service_Dashboard_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        $this->view->todoList  = $dashboardService->getTodoList(parent::getUserInformation()->getId());
        $this->view->eventList = $dashboardService->getEventList(parent::getUserInformation()->getId(), parent::getCurrentProjectId());
        $this->view->timeList  = $dashboardService->getTimesForDay(parent::getUserInformation()->getId(), parent::getCurrentProjectId(), $year, $month, $day);

        $dateString               = $selectedDate->get(Zend_Date::WEEKDAY);
        $dateString               .= ', ' . $selectedDate->get(Zend_Date::DAY);
        $dateString               .= '. ' . $selectedDate->get(Zend_Date::MONTH_NAME);
        $dateString               .= ' ' . $selectedDate->get(Zend_Date::YEAR);
        $this->view->selectedDate = $dateString;

        $this->view->calendar = $this->view->render('dashboard/_calendar.phtml');
    }

    public function deleteAction()
    {
        $ciAttributeId = $this->_getParam('ciattribute');

        if (is_null($ciAttributeId)) {
            throw new Exception_InvalidParameter();
        }

        $dashboardService = new Service_Dashboard_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $statusCode       = $dashboardService->deleteTodoItem($ciAttributeId);

        $notification = array();
        if ($statusCode) {
            $notification['success'] = $this->translator->translate('todoItemDeleteSuccess');
        } else {
            $notification['error'] = $this->translator->translate('todoItemDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('dashboard/index/');
    }

    public function completeAction()
    {
        $ciAttributeId = $this->_getParam('ciattribute');

        if (is_null($ciAttributeId)) {
            throw new Exception_InvalidParameter();
        }

        $dashboardService = new Service_Dashboard_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $statusCode       = $dashboardService->completeTodoItem($ciAttributeId);

        $notification = array();
        if ($statusCode) {
            $notification['success'] = $this->translator->translate('todoItemUpdateSuccess');
        } else {
            $notification['error'] = $this->translator->translate('todoItemUpdateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('dashboard/index/');
    }

    public function changePrioAction()
    {
        $ciAttributeId = $this->_getParam('ciattribute');
        $prio          = $this->_getParam('prio');

        if (is_null($ciAttributeId) || is_null($prio)) {
            throw new Exception_InvalidParameter();
        }

        $dashboardService = new Service_Dashboard_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $statusCode       = $dashboardService->changePriority($ciAttributeId, $prio);

        $notification = array();
        if ($statusCode) {
            $notification['success'] = $this->translator->translate('todoItemUpdateSuccess');
        } else {
            $notification['error'] = $this->translator->translate('todoItemUpdateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('dashboard/index/');
    }

    public function calendarAction()
    {
        $day   = $this->_getParam('day');
        $month = $this->_getParam('month');
        $year  = $this->_getParam('year');

        $calendar             = Util_Calendar::get($year, $month, parent::getUserInformation()->getId(), parent::getCurrentProjectId(), true);
        $this->view->calendar = $calendar;

        if (!$year)
            $year = date('Y');

        if (!$month)
            $month = date('m');

        if (!$day)
            $day = date('d');

        $nextYear = $year;
        $prevYear = $year;

        $nextMonth = $month + 1;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        $prevMonth = $month - 1;
        if ($prevMonth <= 0) {
            $prevMonth = 12;
            $prevYear--;
        }

        $selectedDate = new Zend_Date();
        $selectedDate->setYear($year);
        $selectedDate->setMonth($month);
        $selectedDate->setDay($day);


        $stringMonth           = date('M', mktime(0, 0, 0, $month, 1, $year));
        $this->view->monthName = $stringMonth;
        $this->view->month     = $month;
        $this->view->year      = $year;

        $this->view->nextYear  = $nextYear;
        $this->view->prevYear  = $prevYear;
        $this->view->nextMonth = $nextMonth;
        $this->view->prevMonth = $prevMonth;

        $dashboardService = new Service_Dashboard_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        $this->view->todoList  = $dashboardService->getTodoList(parent::getUserInformation()->getId());
        $this->view->eventList = $dashboardService->getEventList(parent::getUserInformation()->getId(), parent::getCurrentProjectId());
        $this->view->timeList  = $dashboardService->getTimesForDay(parent::getUserInformation()->getId(), parent::getCurrentProjectId(), $year, $month, $day);

        $dateString               = $selectedDate->get(Zend_Date::WEEKDAY);
        $dateString               .= ', ' . $selectedDate->get(Zend_Date::DAY);
        $dateString               .= '. ' . $selectedDate->get(Zend_Date::MONTH_NAME);
        $dateString               .= ' ' . $selectedDate->get(Zend_Date::YEAR);
        $this->view->selectedDate = $dateString;

        $this->view->calendar = $this->view->render('dashboard/_calendar.phtml');
    }


}