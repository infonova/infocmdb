<?php

/**
 *
 *
 *
 */
class Service_Reporting_Update extends Service_Abstract
{


    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2103, $themeId);
    }

    public function getReportingInputUpdateForm($type)
    {

        $render = "";
        switch ($type) {
            case 'sql':
                $render = 'inputQuery';
                $form   = new Form_Reporting_InputSql($this->translator);
                $form->setUpdateButton();
                break;
            case 'cql':
                $render = 'inputQuery';
                $form   = new Form_Reporting_InputCql($this->translator);
                $form->setUpdateButton();
                break;
            case 'script':
            case 'scriptold':
                $render = 'inputScript';
                $form   = new Form_Reporting_InputScript($this->translator);
                $form->setUpdateButton();
                break;
            case 'gui':
                $render = 'inputGui';
                $form   = new Form_Reporting_InputGui($this->translator);
                $form->setUpdateButton();
                break;
            case 'extended':
                $render = 'inputExtended';
                $form   = new Form_Reporting_InputExtended($this->translator);
                $form->setUpdateButton();
                break;
            default:
                // gui
                $render = 'inputGui';
                $form   = new Form_Reporting_InputGui($this->translator);
                $form->setUpdateButton();
        }

        return array('form' => $form, 'render' => $render);
    }


    public function getReportingUpdateForm($reportingId)
    {
        return new Form_Reporting_Update($this->translator, $reportingId);
    }

    public function getReportingTriggerForm($executionTimeString = '* * * * *', $type = 'time')
    {
        switch ($type) {
            case 'time':
                return new Form_Reporting_Cron($this->translator, $executionTimeString);
                break;
            default:
                // gui
                return new Form_Reporting_Cron($this->translator, $executionTimeString);
        }
    }

    public function updateReporting($reportingId, $formData, $dbData)
    {
        try {
            $dbUpdate                               = false;
            $formData[Db_Reporting::EXECUTION_TIME] = Service_Cron_Get::getExecutionTimeAsString($formData);

            foreach ($formData as $key => $value) {
                if ($formData[$key] != $dbData[$key])
                    $updateData[$key] = $value;
            }

            $report = array();
            if ($updateData['name'] !== null)
                $report[Db_Reporting::NAME] = $updateData['name'];
            if ($updateData['description'] !== null)
                $report[Db_Reporting::DESCRIPTION] = $updateData['description'];
            if ($updateData['note'] !== null)
                $report[Db_Reporting::NOTE] = $updateData['note'];
            if ($updateData['output'] !== null)
                $report[Db_Reporting::OUTPUT] = $updateData['output'];
            if ($updateData['transport'] !== null) {
                $report[Db_Reporting::TRANSPORT] = $updateData['transport'];
                if ($updateData['transport'] != 'mail') {
                    $dao = new Dao_Reporting();
                    $dao->deleteNotificationMapping($reportingId);
                }
            }
            if ($updateData['execution_time'] !== null)
                $report[Db_Reporting::EXECUTION_TIME] = $updateData['execution_time'];
            if ($updateData['input'] !== null)
                $report[Db_Reporting::INPUT] = $updateData['input'];
            if ($updateData['trigger'] !== null) {
                $report[Db_Reporting::TRIGGER] = $updateData['trigger'];
                if ($updateData['trigger'] != 'time')
                    $report[Db_Reporting::EXECUTION_TIME] = null;
            }
            if ($updateData['mail_content'] != null)
                $report[Db_Reporting::MAIL_CONTENT] = $updateData['mail_content'];

            switch ($formData['input']) {
                case 'sql':
                case 'cql':
                    if ($updateData['query'] !== null)
                        $report[Db_Reporting::STATEMENT] = $updateData['query'];
                    break;
                case 'script':
                case 'scriptold':
                    if ($updateData['scriptfilename'] !== null) {
                        $report[Db_Reporting::SCRIPT] = $updateData['scriptfilename'];
                    } elseif ($updateData['scriptname'] !== null && $updateData['scriptcontent'] !== null) {
                        $script                       = $this->saveScriptToFile($updateData['scriptname'], $updateData['scriptcontent']);
                        $report[Db_Reporting::SCRIPT] = $updateData['filename'];
                    }
                    break;
                case 'extended':
                    if ($updateData['query'] !== null)
                        $report[Db_Reporting::STATEMENT] = $updateData['query'];
                    if ($updateData['script'] !== null) {
                        $script                       = $this->saveScriptToFile('script', $updateData['script']);
                        $report[Db_Reporting::SCRIPT] = $script['filename'];
                    }
                    break;
                case 'gui':
                default:
                    // TODO:
                    break;
            }


            if (!empty($report)) {
                $dao = new Dao_Reporting();
                $dao->updateReporting($report, $reportingId);
                $dbUpdate = true;
            }


            if ($formData['transport'] == 'mail' && $updateData['mail'] != null) {

                $this->logger->log('ichbinda');

                $dao = new Dao_Reporting();
                $dao->deleteNotificationMapping($reportingId);
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

                    $dao->addNotification($notification);
                }
                $dbUpdate = true;
            }

            return $dbUpdate;
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

        $prefix   = date("YmdHms\_");
        $filepath = $path . 'reporting/' . $prefix . $filename;
        /* replacing windows line endings with unix line endings */
        $script = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $script);

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


}