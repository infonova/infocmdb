<?php

class Import_File_Util_Object_Update
{

    private $status;
    private $attributeList;
    private $currentAttributeList;
    private $data;


    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setAttributeList($attributeList)
    {
        $this->attributeList = $attributeList;
    }

    public function getAttributeList()
    {
        return $this->attributeList;
    }

    public function setCurrentAttributeList($currentAttributeList)
    {
        $this->currentAttributeList = $currentAttributeList;
    }

    public function getCurrentAttributeList()
    {
        return $this->currentAttributeList;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}