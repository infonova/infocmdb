<?php

/**
 *
 *
 *
 */
class Service_Reporting_Create extends Service_Abstract
{

    private static $reportingNamespace = 'ReportingController';

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2102, $themeId);
    }

    public function getReportingCreateForm()
    {
        $form = new Form_Reporting_Create($this->translator);
        return $form;
    }

    public function createReporting($formData)
    {
        try {
            $report                             = array();
            $report[Db_Reporting::NAME]         = $formData['name'];
            $report[Db_Reporting::DESCRIPTION]  = $formData['description'];
            $report[Db_Reporting::NOTE]         = $formData['note'];
            $report[Db_Reporting::INPUT]        = $formData['input'];
            $report[Db_Reporting::OUTPUT]       = $formData['output'];
            $report[Db_Reporting::TRANSPORT]    = $formData['transport'];
            $report[Db_Reporting::TRIGGER]      = $formData['trigger'];
            $report[Db_Reporting::IS_ACTIVE]    = '1';
            $report[Db_Reporting::MAIL_CONTENT] = $formData['mail_content'];


            switch ($formData['input']) {
                case 'sql':
                case 'cql':
                    $report[Db_Reporting::STATEMENT] = $formData['query'];
                    break;
                case 'script':
                case 'scriptold':
                    if (!$formData['scriptfilename']) {
                        $script                       = $this->saveScriptToFile($formData['scriptname'], $formData['scriptcontent']);
                        $report[Db_Reporting::SCRIPT] = $script['filename'];
                    } else {
                        $report[Db_Reporting::SCRIPT] = $formData['scriptfilename'];
                    }
                    break;
                case 'extended':
                    $report[Db_Reporting::STATEMENT] = $formData['query'];
                    $script                          = $this->saveScriptToFile('script', $formData['script']);
                    $report[Db_Reporting::SCRIPT]    = $script['filename'];
                    break;
                case 'gui':
                default:
                    // TODO:
                    break;
            }


            if ($formData['trigger'] == 'time') {
                $report[Db_Reporting::EXECUTION_TIME] = Service_Cron_Get::getExecutionTimeAsString($formData);
            }

            $reportingDaoImpl = new Dao_Reporting();
            $reportingId      = $reportingDaoImpl->insertReporting($report);


            if ($formData['transport'] == 'mail') {
                // save mail mapping
                $string = $formData['mail'];
                $string = nl2br($string);
                $list   = explode('<br />', $string);

                foreach ($list as $mailAddress) {
                    $notification                                     = array();
                    $notification[Db_Notification::NOTIFICATION_ID]   = $reportingId;
                    $notification[Db_Notification::NOTIFICATION_TYPE] = 'reporting';
                    $notification[Db_Notification::TYPE]              = 'mail';
                    $notification[Db_Notification::ADDRESS]           = trim($mailAddress);

                    $reportingDaoImpl->addNotification($notification);
                }
            }


            if (!$reportingId) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            throw new Exception_Reporting_Unknown($e);
        }
    }

    private function saveScriptToFile($filename, $script)
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
        /* replacing windows line endings with unix line endings */
        $script = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $script);

        $prefix   = date("YmdHms\_");
        $filepath = $path . 'reporting/' . $prefix . $filename;

        $file       = fopen($filepath, 'w');
        $sFileWrite = fwrite($file, trim($script));

        if ($sFileWrite === false) {
            // Unable to write data to file

            // exception??
            return false;
        }

        fclose($file);
        return array('filename' => $prefix . $filename, 'description' => $filename);
    }


    public function getReportingInputForm($type)
    {

        $render = "";
        switch ($type) {
            case 'sql':
                $render = 'wizardInputSql';
                $form   = new Form_Reporting_InputSql($this->translator);
                break;
            case 'cql':
                $render = 'wizardInputCql';
                $form   = new Form_Reporting_InputCql($this->translator);
                break;
            case 'script':
            case 'scriptold':
                $render = 'wizardInputScript';
                $form   = new Form_Reporting_InputScript($this->translator);
                break;
            case 'extended':
                $render = 'wizardInputExtended';
                $form   = new Form_Reporting_InputExtended($this->translator);
                break;
            case 'gui':
                $render = 'wizardInputGui';
                break;
            default:
                // gui
                $render = 'wizardInputGui';
                $form   = new Form_Reporting_InputGui($this->translator);
        }

        return array('form' => $form, 'render' => $render);
    }


    public function createReportingInput($formData)
    {
        try {
            $reportInput = array();
            $reportInput = $formData; // find a better way!
            // TODO implement me!

            $var        = $userId . 'ReportingInput';
            $sess       = new Zend_Session_Namespace(self::$reportingNamespace);
            $sess->$var = $reportInput;

            return true;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            throw new Exception_Reporting_Unknown($e);
        }
    }


    public function getReportingTriggerForm($type = 'time')
    {
        switch ($type) {
            case 'time':
                return new Form_Reporting_Cron($this->translator);
                break;
            default:
                // gui
                return new Form_Reporting_Cron($this->translator);
        }
    }


    public function finalizeReporting()
    {
        $reporting = $this->getSessionReporting();
        $input     = $this->getSessionReportingInput();
        $versand   = $this->getSessionReportingVersand();
        $trigger   = $this->getSessionReportingTrigger();


        $report                             = array();
        $report[Db_Reporting::NAME]         = $reporting['name'];
        $report[Db_Reporting::DESCRIPTION]  = $reporting['description'];
        $report[Db_Reporting::NOTE]         = $reporting['note'];
        $report[Db_Reporting::INPUT]        = $reporting['input'];
        $report[Db_Reporting::OUTPUT]       = $reporting['output'];
        $report[Db_Reporting::TRANSPORT]    = $reporting['versand'];
        $report[Db_Reporting::TRIGGER]      = $reporting['trigger'];
        $report[Db_Reporting::IS_ACTIVE]    = '1';
        $report[Db_Reporting::MAIL_CONTENT] = $reporting['mail_content'];

        switch ($reporting['input']) {
            case 'sql':
            case 'cql':
                $report[Db_Reporting::STATEMENT] = $input['query'];
                break;
            case 'script':
            case 'scriptold':
                $report[Db_Reporting::SCRIPT] = $input['scriptfilename'];
                break;
            case 'extended':
                $report[Db_Reporting::STATEMENT] = $input['query'];
                $report[Db_Reporting::SCRIPT]    = $input['scriptfilename'];
                break;
            case 'gui':
            default:
                // TODO:
                break;
        }


        if ($reporting['trigger'] == 'time') {
            $report[Db_Reporting::EXECUTION_TIME] = $trigger[executionTime];
        }

        $reportinDaoImpl = new Dao_Reporting();
        $reportingId     = $reportinDaoImpl->insertReporting($report);

        if ($reporting['versand'] == 'mail') {
            // save mail mapping

            $string = $versand['mail'];
            $string = nl2br($string);
            $list   = explode('<br />', $string);

            foreach ($list as $mailAddress) {
                $notification                                     = array();
                $notification[Db_Notification::NOTIFICATION_ID]   = $reportingId;
                $notification[Db_Notification::NOTIFICATION_TYPE] = 'reporting';
                $notification[Db_Notification::TYPE]              = 'mail';
                $notification[Db_Notification::ADDRESS]           = trim($mailAddress);

                $reportinDaoImpl->addNotification($notification);
            }
        }

    }

}