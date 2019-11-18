<?php

class Process_Reporting_Input_Scriptold extends Process_Reporting_Input
{


    protected function processValid($reporting)
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

        $folder  = $config->file->upload->reporting->folder;
        $command = $path .'/'. $folder .'/'. $reporting[Db_Reporting::SCRIPT];

        // get reporting mail addresses
        $reportingDaoImpl = new Dao_Reporting();
        $addressses       = $reportingDaoImpl->getReportingMailAddresses($reporting[Db_Reporting::ID]);
        if ($addressses)
            foreach ($addressses as $address) {
                $command .= ' ' . $address[Db_Notification::ADDRESS];
            }

        $command .= ' &';
        system($command);

        $this->createReportingHistory($reporting, $this->userId);
    }


    private function createReportingHistory($reporting, $userId)
    {
        $reportingHistory                                    = array();
        $reportingHistory[Db_ReportingHistory::USER_ID]      = $userId;
        $reportingHistory[Db_ReportingHistory::REPORTING_ID] = $reporting[Db_Reporting::ID];
        $reportingHistory[Db_ReportingHistory::FILENAME]     = 'none';

        $reportingDaoImpl = new Dao_Reporting();
        $reportingDaoImpl->insertReportingHistory($reportingHistory);
    }
}