<?php

class Process_Reporting_Input_Script extends Process_Reporting_Input
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

        $folder = $config->file->upload->reporting->folder;
        $file   = $path .'/'. $folder .'/'. $reporting[Db_Reporting::SCRIPT];

        $script = Util_Thread::create($file);
        $data   = unserialize(base64_decode($script[1]));

        $scriptRes;
        while ($data->isActive()) {
            $scriptRes .= $data->listen();
        }

        $scriptRes = unserialize(base64_decode($scriptRes));
        $data->close();

        $attributes = array();
        if ($scriptRes && count($scriptRes) > 0) {
            foreach ($scriptRes[0] as $attribute => $val) {
                array_push($attributes, $attribute);
            }
        }

        $this->setAttributes($attributes);
        $this->setData($scriptRes);
    }
}