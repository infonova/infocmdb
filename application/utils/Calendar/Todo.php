<?php

class Util_Calendar_Todo
{

    private $value;
    private $priority;
    private $status;
    private $ciId;
    private $id;

    public function __construct($value, $id, $priority = null)
    {
        $this->value    = $value;
        $this->id       = $id;
        $this->priority = $priority;
    }


    public function __toString()
    {
        return $this->value;
    }

    public function getCiId()
    {
        return $this->ciId;
    }

    public function setCiId($ciId)
    {
        $this->ciId = $ciId;
    }

    public function getCreateDate()
    {
        return $this->createDate;
    }

    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

}