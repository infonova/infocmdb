<?php

class Import_File_Method_Auto_Attribute extends Import_File_Method_Abstract implements Import_File_Method
{

    private $content = array();


    public function import(&$logger, $historyId, &$row, $attributeList, $parameter = array())
    {
        $status           = array();
        $status['status'] = true;
        $status['errors'] = array();

        array_push($this->content, $row);

        return $status;
    }


    public function getAttributeList($data, $logger)
    {
        array_push($this->content, $data);
        $status               = array();
        $status['status']     = true;
        $status['errors']     = array();
        $status['attributes'] = array();
        return $status;
    }


    public function finalize($logger)
    {
        $logger->log('finalize', Zend_Log::CRIT);
        $status           = array();
        $status['status'] = true;
        $status['errors'] = array();

        try {

            foreach ($this->content as $skey => $row) {
                foreach ($row as $key => $item) {

                    try {

                        $adao                  = new Dao_Attribute();
                        $attributeGroupDaoImpl = new Dao_AttributeGroup();

                        $attribute                            = array();
                        $attribute[Db_Attribute::NAME]        = $this->content[0][$key];
                        $attribute[Db_Attribute::DESCRIPTION] = $this->content[1][$key];

                        $class                                      = Util_AttributeType_Factory::get($this->content[2][$key]);
                        $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] = $class::ATTRIBUTE_TYPE_ID;

                        $attributeGroupId                            = $attributeGroupDaoImpl->getAttributeGroupByName($this->content[3][$key]);
                        $attribute[Db_Attribute::ATTRIBUTE_GROUP_ID] = $attributeGroupId[Db_AttributeGroup::ID];
                        $attribute[Db_Attribute::ORDER_NUMBER]       = $this->content[4][$key];
                        $attribute[Db_Attribute::NOTE]               = $this->content[5][$key];
                        $attribute[Db_Attribute::IS_UNIQUE]          = '0';
                        $attribute[Db_Attribute::INPUT_MAXLENGTH]    = '0';
                        $attribute[Db_Attribute::TEXTAREA_COLS]      = '0';
                        $attribute[Db_Attribute::TEXTAREA_ROWS]      = '0';
                        $attribute[Db_Attribute::IS_BOLD]            = '0';
                        $attribute[Db_Attribute::IS_ACTIVE]          = '1';

                        $attributeId = $adao->insertAttribute($attribute);

                        if (!$attributeId) {
                            $status['status']    = false;
                            $status['errors'][0] = Import_File_Code::ERROR_ATTRIBUTE_INSERT_FAILED;
                            return $status;
                        }
                        switch ($class::ATTRIBUTE_VALUES_ID) {
                            case 'default':
                                $listArray = array();
                                foreach ($this->content as $mkey => $container) {
                                    if ($mkey > 5) {
                                        if (!in_array($container[$key], $listArray)) {
                                            array_push($listArray, $container[$key]);
                                        }
                                    }
                                    // add $container[$key]
                                }

                                foreach ($listArray as $content) {
                                    $adao->insertAttributeDefaultValuesById($content, $attributeId);
                                }
                                break;
                        }

                    } catch (Exception $e) {
                        $logger->log($e, Zend_Log::CRIT);
                        $status['status']       = false;
                        $status['errors'][$key] = Import_File_Code::ERROR_ATTRIBUTE_SINGLE_FAILED;
                    }
                }

                break;
            }

        } catch (Exception $e) {
            $logger->log($e, Zend_Log::CRIT);
            $status['status']    = false;
            $status['errors'][0] = Import_File_Code::ERROR_ATTRIBUTE_ALL_FAILED;
        }
        return $status;
    }
}