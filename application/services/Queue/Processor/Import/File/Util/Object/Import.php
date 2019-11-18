<?php

class Import_File_Util_Object_Import
{

    private $isInsert;

    private $status;
    private $attributeList;
    private $currentAttributeList;
    private $data;

    private $ciType;
    private $project;

    public function setProject($project)
    {
        $this->project = $project;
    }

    public function getProject()
    {
        return $this->project;
    }

    public function getCiType()
    {
        return $this->ciType;
    }

    public function setCiType($ciType)
    {
        $this->ciType = $ciType;
    }

    public function setIsInsert($insert)
    {
        $this->isInsert = $insert;
    }

    public function isInsert()
    {
        return $this->isInsert;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setAttributeList(&$attributeList)
    {
        $this->attributeList = $attributeList;
    }

    public function getAttributeList()
    {
        return $this->attributeList;
    }

    public function setCurrentAttributeList(&$currentAttributeList)
    {
        $this->currentAttributeList = $currentAttributeList;
    }

    public function getCurrentAttributeList()
    {
        return $this->currentAttributeList;
    }

    public function setData(&$data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    /*gibt auf basis eines strings mit Namen von DefaultValues und einer attribute id
     * durch ,getrennt 
     * deren 
     * ids in einem string durch , getrennt zur�ck
     */

    public function getdefaultValuesbyName(&$names, $attribute_id)
    {

        $ret           = null;
        $importDaoImpl = new Dao_Import();
        $name_array    = explode(',', $names);
        $i             = 0;


        foreach ($name_array as $name) {

            $default_value_ids = $importDaoImpl->getDefaultValueIdByName($attribute_id, $name);

            if ($default_value_ids) {

                if ($i > 0) {

                    $ret .= ',' . $default_value_ids[Db_AttributeDefaultValues::ID];

                } else {

                    $ret = $default_value_ids[Db_AttributeDefaultValues::ID];

                }

                $i++;

            } else {

                $ret = null;

            }
        }
        return $ret;
    }

    public function getdefaultValuesbyID($defaultvalues_id)
    {

        $ret              = null;
        $importDaoImpl    = new Dao_Import();
        $defaultids_array = explode(',', $defaultvalues_id);
        $i                = 0;

        foreach ($defaultids_array as $default_id) {

            $default_value_names = $importDaoImpl->getDefaultValueNameById($default_id);

            if ($default_value_names) {

                if ($i > 0) {

                    $ret .= ',' . $default_value_ids[Db_AttributeDefaultValues::ID];

                } else {

                    $ret = $default_value_ids[Db_AttributeDefaultValues::ID];

                }

                $i++;

            } else {

                $ret = null;

            }

        }

    }
}