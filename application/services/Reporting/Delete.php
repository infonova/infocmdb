<?php

/**
 *
 *
 *
 */
class Service_Reporting_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2104, $themeId);
    }

    public function deleteReporting($reportingId)
    {
        try {
            $reportingDaoImpl      = new Dao_Reporting();
            $countReportingHistory = $reportingDaoImpl->countHistoryRoleByReportingId($reportingId);
            $countReportingMapping = $reportingDaoImpl->countHistoryMappingByReportingId($reportingId);
            $statusCode            = 0;
            if ($countReportingHistory['cnt'] != 0 || $countReportingMapping['cnt'] != 0) {
                $reportingDaoImpl->deactivateReporting($reportingId);
                $reportingDaoImpl->deleteReportingCronjob($reportingId);
                $statusCode = 2;
            } else {
                $reportingDaoImpl->deleteReportingMapping($reportingId);
                $reportingDaoImpl->deleteNotificationMapping($reportingId);
                $reportingDaoImpl->deleteHistoryMapping($reportingId);
                $reportingDaoImpl->deleteReporting($reportingId);
                $reportingDaoImpl->deleteReportingCronjob($reportingId);
                $statusCode = 1;
            }
        } catch (Exception $e) {
            throw new Exception_Reporting_DeleteFailed($e);
            $statusCode = 0;
        }
        return $statusCode;
    }

    public function removeSingleArchive($archiveId)
    {
        $reportingDaoImpl = new Dao_Reporting();
        $archive          = $reportingDaoImpl->getSingleArchiveId($archiveId);


        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $defaultFolder = $config->file->upload->path->folder;
        $filePath      = APPLICATION_PUBLIC . $defaultFolder;
        $folder        = $config->file->upload->reporting->folder;

        if (!$folder) {
            $folder = "reporting";
        }

        $folder = $folder . '/reports';
        $folder = $folder .'/'. $archive[Db_ReportingHistory::REPORTING_ID];

        $destination = $filePath . $folder;
        $file        = $destination .'/'. $archive[Db_ReportingHistory::FILENAME];

        try {
            if (file_exists($file)) {
                unlink($file);
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::WARN);
        }

        $reportingDaoImpl->deleteSingleArchive($archiveId);

    }

    public function activateReporting($reportingId)
    {
        try {
            $reportingDaoImpl = new Dao_Reporting();
            $activating       = $reportingDaoImpl->activateReporting($reportingId);
        } catch (Exception $e) {
            throw new Exception_Reporting_ActivateFailed($e);
        }
    }

}