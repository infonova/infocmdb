<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class DownloadController extends AbstractAppAction
{

    public function init()
    {
        parent::init();
        parent::setTranslatorLocal();
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);
        $this->_forward('file');
    }

    public function reportAction()
    {
        $report_id = $this->_getParam("id");
        $file_name = $this->_getParam("file");

        $redirect = "reporting/detail/reportingId/" . $report_id;

        // will throw permission denied exception if user has insufficient permissions
        try {
            $reportServiceGet = new Service_Reporting_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        } catch (Exception $exception) {
            $notification          = array();
            $notification['error'] = $this->translator->translate('globalFilePermissionDenied');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect($redirect);
        }

        $fileType = "reporting/reports";
        $redirect = "reporting/detail/reportingId/" . $report_id;

        $path = APPLICATION_PUBLIC . '/_uploads/' . $fileType .'/'. $report_id .'/'. $file_name;

        $this->downloadFile($file_name, $path, $redirect);
    }

    public function ciAction()
    {
        $ci_attribute_id = $this->_getParam("ciattributeid");
        $file_name       = $this->_getParam("file");

        $file_type = "attachment";

        // user permissions on ci are checked with the if statement so resource permission
        // is not checked with theme id here to prevent obsolete try and catch
        $ci_service_get = new Service_Ci_Get($this->translator, $this->logger, 0);
        $ci_attribute   = $ci_service_get->getCiAttributeById($ci_attribute_id);

        $ci_id        = $ci_attribute[Db_CiAttribute::CI_ID];
        $attribute_id = $ci_attribute[Db_CiAttribute::ATTRIBUTE_ID];

        $redirect = "ci/detail/ciid/" . $ci_id;

        if (!$ci_service_get->checkPermission($ci_id, parent::getUserInformation()->getId(), $attribute_id)) {
            $notification['error'] = $this->translator->translate('globalFilePermissionDenied');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect($redirect);
        }
        $path = APPLICATION_PUBLIC . '/_uploads/' . $file_type .'/'. $ci_id .'/'. $file_name;

        $this->downloadFile($file_name, $path, $redirect);
    }

    public function fileimportAction()
    {
        $file_import_id = $this->_getParam("id");
        // show filename in URL for user - not used in code
        $file_name   = $this->_getParam("file");
        $log_request = $this->_getParam("log") === 'true';

        $redirect = "fileimport";
        $config   = new Util_Config('import.ini', APPLICATION_ENV);
        $dest     = $config->getValue('file.import.destination', APPLICATION_PUBLIC . "_uploads/import/");

        $file_history_dao = new Dao_Import();
        $hist_file        = $file_history_dao->getFileHistory($file_import_id);

        $file_name = $hist_file[Db_ImportFileHistory::FILENAME];
        $status    = $hist_file[Db_ImportFileHistory::STATUS];

        if ($log_request) {
            $redirect  = "fileimport/log/id/" . $file_import_id;
            $file_name .= ".log";
            $path      = $dest . $config->getValue('file.import.log', "log/") .'/'. $file_import_id .'/'. $file_name;
        } else {
            if ($status == 'idle' || $status == 'in_progress') {
                if ($hist_file[Db_ImportFileHistory::QUEUE] == 'idle') {
                    $path = $config->getValue('file.import.tmp.destination', APPLICATION_PUBLIC . "_uploads/import/tmp/") . $file_name;
                } else {
                    $validation_type = $hist_file[Db_ImportFileHistory::VALIDATION];
                    $vt_default      = $validation_type . "_validation/";

                    $queue_status = $hist_file[Db_ImportFileHistory::QUEUE];
                    $qs_default   = $queue_status . "/";

                    $path = $config->getValue('file.import.idle.destination', APPLICATION_PUBLIC . "_uploads/import/queue/") .
                        $config->getValue('file.import.validation.' . $validation_type, $vt_default) .
                        $config->getValue('file.import.type' . $queue_status, $qs_default) .
                        $file_name;
                }
            } else {
                $path = $dest . $config->getValue('file.import.history', "history/") .'/'. $file_import_id .'/'. $file_name;
            }
        }

        $this->downloadFile($file_name, $path, $redirect);
    }


    /**
     * @param $filename
     * @param $path
     * @param $notification
     * @param $redirect
     *
     * @return null exits execution or redirects on error
     */
    private function downloadFile($filename, $path, $redirect)
    {
        if (file_exists($path) && is_readable($path)) {
            $size          = filesize($path);
            $safe_filename = preg_replace("/[^a-zA-Z0-9_.@\-]/", '', $filename);

            $response = $this->getResponse();

            // fix for IE catching or PHP bug issue
            $response->setHeader('Pragma', 'public', true)
                ->setHeader('Expires', '0', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                // force download dialog
                ->setHeader('Content-Type', 'application/force-download')
                ->setHeader('Content-Type', 'application/octet-stream')
                ->setHeader('Content-Type', 'application/download')
                // force the browser to display the save dialog.
                ->setHeader('Content-Disposition', 'attachment; filename="' . $safe_filename . '"; size: ' . $size, true)
                ->setHeader('Content-Transfer-Encoding', 'binary', true)
                ->setHeader('Content-Length', $size, true);

            // push our headers
            $response->sendHeaders();

            // turn of output buffering to handle large files
            @ob_end_flush();

            // disable exeception throwing for headers because we allready pushed those
            $response->headersSentThrowsException = false;

            // read our file
            @readfile($path);

            // and die to avoid any further trouble (checksum)
            exit();
        } else {
            $notification          = array();
            $notification['error'] = $this->translator->translate('globalFileNotFound');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect($redirect);
            //throw new Exception_File_NotFound();
        }

        exit;
    }

}