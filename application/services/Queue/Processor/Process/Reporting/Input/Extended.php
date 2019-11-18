<?php

class Process_Reporting_Input_Extended extends Process_Reporting_Input
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

        $folder   = $config->file->upload->reporting->folder;
        $filePath = $path .'/'. $folder . '/';
        $script   = $filePath . $reporting[Db_Reporting::SCRIPT];


        // TODO: implement me!
        $statement = $reporting[Db_Reporting::STATEMENT];


        $reportingDaoImpl = new Dao_Reporting();
        $result           = $reportingDaoImpl->executeStatement($statement);


        $attributes = array();
        if ($result && count($result) > 0) {
            foreach ($result[0] as $attribute => $val) {
                array_push($attributes, $attribute);
            }
        }


        // start a new subprocess
        $date        = date("Hms\_");
        $newFileName = $date . $reporting[Db_Reporting::SCRIPT] . '.csv';
        $newFile     = $filePath . 'tmp/' . $newFileName;

        $fp = fopen($newFile, 'w') or die("can't open file");
        foreach ($result as $fields) {
            fputcsv($fp, $fields, ';');
        }
        fclose($fp);

        chmod($newFile, '0755');

        $exec = $script . ' ' . $newFile;
        $this->logger->log('Process_Reporting_Input_Extended executing script: ' . $exec, Zend_Log::INFO);
        $data = Util_Thread::create($exec, 'perl', false);

        $scriptRes;
        while ($data && $data->isActive()) {
            $scriptRes .= $data->listen();
        }
        $data->close();

        //TODO: needed?
        //$scriptRes = unserialize (base64_decode ($scriptRes));
        if ($scriptRes && count($scriptRes) > 0) {

            $lines = explode(';|;', $scriptRes);

            $res   = array();
            $first = true;
            foreach ($lines as $key => $val) {
                if ($first) {
                    $attTemp = explode(';', $val);
                    foreach ($attTemp as $key => $attr) {
                        $attributes[$key] = $attr;
                    }

                    $first = false;
                } else {
                    $res[$key - 1] = explode(';', $val);
                }
            }

            $result = array();
            foreach ($res as $key => $row) {

                foreach ($row as $cKey => $col) {
                    $result[$key][$attributes[$cKey]] = $col;
                }
            }

            $this->setAttributes($attributes);
            $this->setData($result);

            unlink($newFile);

        } else {
            $this->logger->log('Process_Reporting_Input_Extended no content returned', Zend_Log::CRIT);
            $this->setAttributes($attributes);
            $this->setData(array());
        }


    }
}