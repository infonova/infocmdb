<?php

abstract class Process_Reporting_Input
{

    protected $data       = array();
    protected $attributes = array();
    protected $logger;
    protected $userId;


    public function __construct()
    {
        $this->logger = Zend_Registry::get('Log');
    }

    public function getData()
    {
        return $this->data;
    }

    protected function setData($data)
    {
        $this->data = $data;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    protected function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }


    public function process($reporting, $userId)
    {
        $this->userId = $userId;
        if ($this->validate($reporting)) {
            $this->processValid($reporting);
        } else {
            $this->processInvalid($reporting);
        }
    }


    private function validate($reporting)
    {
        if (!$reporting) {
            throw new Exception_Reporting_InputInvalid();
        }

        if (!$reporting[Db_Reporting::ID]) {
            return false;
        }

        return true;
    }


    /**
     * override this method
     */
    protected function processValid($reporting)
    {
        // do nothing
    }

    /**
     * override this method
     */
    protected function processInvalid($reporting)
    {
        // do nothing
    }
}