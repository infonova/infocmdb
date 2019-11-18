<?php

abstract class Process_Reporting_Transport
{


    public function process($reporting, $file, $path, $userId)
    {
        if ($this->validate($reporting, $file, $path, $userId)) {
            $this->createReportingHistory($reporting, $file, $userId);
            $this->processValid($reporting, $file, $path);
        } else {
            $this->processInvalid($reporting, $file, $path);
        }
    }


    private function validate($reporting, $file, $path, $userId)
    {
        if (!$reporting || is_null($userId)) {
            throw new Exception_Reporting_TransportInvalid();
        }

        if (!$reporting[Db_Reporting::ID]) {
            return false;
        }

        if (!$file || !$path) {
            // assume scriptold? 
            $this->createReportingHistory($reporting, 'none', $userId);
            return false;
        }

        return true;
    }


    private function createReportingHistory($reporting, $file, $userId)
    {
        $reportingHistory                                    = array();
        $reportingHistory[Db_ReportingHistory::USER_ID]      = $userId;
        $reportingHistory[Db_ReportingHistory::REPORTING_ID] = $reporting[Db_Reporting::ID];
        $reportingHistory[Db_ReportingHistory::FILENAME]     = $file;

        $reportingDaoImpl = new Dao_Reporting();
        $reportingDaoImpl->insertReportingHistory($reportingHistory);
    }


    /**
     * override this method
     */
    protected function processValid($reporting, $file, $path)
    {
        // do nothing
    }

    /**
     * override this method
     */
    protected function processInvalid($reporting, $file, $path)
    {
        // do nothing
    }
}