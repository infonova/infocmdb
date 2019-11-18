<?php

/**
 *
 *
 *
 */
class Service_Reporting_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2101, $themeId);
    }

    public function getReportingList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/reporting.ini', APPLICATION_ENV);

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['reporting'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        $reportingDaoImpl = new Dao_Reporting();
        $select           = $reportingDaoImpl->getReportingForPagination($orderBy, $direction, $filter);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial($scrollingControl);

        return array('paginator' => $paginator, 'reportingList' => $select);
    }


    /**
     * @param string $filter
     */
    public function getFilterForm($filter = null)
    {
        $form = new Form_Filter($this->translator);

        if ($filter) {
            $form->populate(array('search' => $filter));
        }
        return $form;
    }

    public function getReporting($reportingId)
    {
        $reportingDaoImpl = new Dao_Reporting();
        return $reportingDaoImpl->getReporting($reportingId);
    }

    public function getReportingData($reportingId)
    {
        $reportingDaoImpl = new Dao_Reporting();
        $reporting        = $reportingDaoImpl->getReporting($reportingId);

        $reporting['query']             = $reporting[Db_Reporting::STATEMENT];
        $reporting['scriptfilename']    = $reporting[Db_Reporting::SCRIPT];
        $reporting['scriptdescription'] = $reporting[Db_Reporting::DESCRIPTION];

        if ($reporting[Db_Reporting::INPUT] == 'extended') {
            if ($reporting[Db_Reporting::SCRIPT])
                $content = $this->getExtendedScriptContent($reporting[Db_Reporting::SCRIPT]);
            $reporting['script'] = $content;
        }

        if ($reporting[Db_Reporting::TRANSPORT] == 'mail') {
            $addresses = array();
            $dao       = new Dao_Notification();
            $data      = $dao->getReportingRecipients($reportingId);
            foreach ($data as $recipient)
                $addresses[] = $recipient[Db_Notification::ADDRESS];

            if (count($addresses) > 0)
                $reporting['mail'] = implode("\n", $addresses);
        }


        return $reporting;
    }

    private function getExtendedScriptContent($filename)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);

        $useDefaultPath = $config->file->upload->path->default;
        $defaultFolder  = $config->file->upload->path->folder;

        $path = "";
        if ($useDefaultPath) {
            $path = APPLICATION_PUBLIC . $defaultFolder;
        } else {
            $path = $config->file->upload->path->custom;
        }

        $filepath = $path . 'reporting/' . $filename;
        $content  = "";
        try {
            if (file_exists($filepath)) {
                $content = file_get_contents($filepath);
            }
        } catch (Exception $e) {
            // file not found!
        }

        return $content;
    }


    public function getReportingArchive($reportingId)
    {
        $reportingDaoImpl = new Dao_Reporting();
        return $reportingDaoImpl->getLatestReportingArchive($reportingId);
    }
}