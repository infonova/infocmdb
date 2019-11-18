<?php

abstract class Process_Reporting_Output
{

    protected $extension = "csv";
    protected $file;
    protected $filename;
    protected $filepath;
    protected $logger;


    public function __construct()
    {
        $this->logger = Zend_Registry::get('Log');
    }

    public function getFile()
    {
        return $this->file;
    }

    protected function setFile($file)
    {
        $this->file = $file;
    }

    public function getPath()
    {
        return $this->filepath;
    }

    protected function setPath($filepath)
    {
        $this->filepath = $filepath;
    }

    public function process($reporting, $attributes, $data)
    {
        if ($this->validate($reporting, $attributes, $data)) {
            $this->processValid($reporting, $attributes, $data);
        } else {
            $this->processInvalid($reporting, $attributes, $data);
        }
    }

    private function validate($reporting, $attributes, $data)
    {
        if (!$reporting || !$attributes || !$data) {
            throw new Exception_Reporting_OutputInvalid();
        }

        if (count($attributes) <= 0 || count($data) <= 0) {
            return false;
        }

        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $defaultFolder = $config->file->upload->path->folder;
        $filePath      = APPLICATION_PUBLIC . $defaultFolder;
        $folder        = $config->file->upload->reporting->folder;

        if (!$folder) {
            $folder = "reporting";
        }

        $folder .='/'. 'reports/' . $reporting[Db_Reporting::ID];

        $destination = $filePath . $folder;
        if (!is_dir($destination)) {
            @mkdir($destination, 0777);
            chmod($destination, 0777);
        }

        $date     = date("YmdHis\_");
        $fileName = $date . $reporting[Db_Reporting::NOTE] . '.' . $this->extension;

        $this->setPath($destination);
        $this->setFile($fileName);

        return true;
    }


    /**
     * override this method
     */
    protected function processValid($reporting, $attributes, $data)
    {
        // do nothing
    }

    /**
     * override this method
     */
    protected function processInvalid($reporting, $attributes, $data)
    {
        // do nothing
    }
}