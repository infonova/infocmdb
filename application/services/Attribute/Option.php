<?php

/**
 *
 *
 *
 */
class Service_Attribute_Option extends Service_Abstract
{

    private static $attributeNamespace = 'AttributeController';
    const DELETED       = 1;
    const CANNOT_DELETE = 2;
    const ACTIVATED     = 3;
    const DEACTIVATED   = 4;


    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 105, $themeId);
    }


    public function activateOption($optionId)
    {
        try {
            $attributeDaoImpl = new Dao_Attribute();
            $attributeDaoImpl->activateAttributeDefaultValuesById($optionId);
        } catch (Exception $e) {
            throw new Exception_Attribute_ActivationFailed($e);
        }
    }

    public function insertNewOption($values, $attributeId)
    {
        try {
            $attributeDaoImpl = new Dao_Attribute();
            return $attributeDaoImpl->insertAttributeDefaultValuesById($values['optionName'], $attributeId, intval($values['ordernumber']));
        } catch (Exception $e) {
            throw new Exception_Attribute_InsertOptionFailed($e);
        }
    }

    public function insertNewOptionSession($values, $userId)
    {

        $sess   = new Zend_Session_Namespace(self::$attributeNamespace);
        $varOpt = $userId . 'AttributeOption';

        $storedAttributesOptions = $sess->$varOpt;

        array_push($storedAttributesOptions, array(Db_AttributeDefaultValues::VALUE => $values['name'], Db_AttributeDefaultValues::ORDER_NUMBER => $values['ordernumber']));
        $sess->$varOpt = $storedAttributesOptions;
    }

    public function removeOption($attributeId = null, $optionId = null)
    {

        if (is_null($attributeId) or is_null($optionId)) {
            throw new Exception_InvalidParameter();
        }

        try {
            // select all stored options
            $attributeDaoImpl = new Dao_Attribute();
            $option           = $attributeDaoImpl->getAttributeDefaultValue($optionId);

            $attribute = $attributeDaoImpl->getSingleAttributeWithType($attributeId);

            // check if option is used somewhere
            $class  = Util_AttributeType_Factory::get($attribute[Db_AttributeType::NAME]);
            $isUsed = $class->isOptionUsed($optionId, $attributeId);

            if (!$option[Db_AttributeDefaultValues::IS_ACTIVE]) { //delete if unused
                if (!$isUsed) {
                    $attributeDaoImpl->deleteAttributeDefaultValuesById($optionId);
                    return self::DELETED;
                } else
                    return self::CANNOT_DELETE; //can not delete because in use
            } else {
                if (!$isUsed) { // delete unused option
                    $attributeDaoImpl->deleteAttributeDefaultValuesById($optionId);
                    return self::DELETED;
                } // deactivate
                else {
                    $attributeDaoImpl->deactivateAttributeDefaultValuesById($optionId);
                    return self::DEACTIVATED;
                }
            }
        } catch (Exception $e) {
            throw new Exception_Attribute_RemoveOption($e);
        }
    }

    public function removeOptionSession($userId, $optionId)
    {

        if (is_null($optionId)) {
            throw new Exception_InvalidParameter();
        }

        $sess   = new Zend_Session_Namespace(self::$attributeNamespace);
        $varOpt = $userId . 'AttributeOption';

        $storedAttributesOptions = $sess->$varOpt;

        foreach ($storedAttributesOptions as $key => $opt) {
            if (str_replace(' ', '', $opt['value']) == $optionId) {
                unset($storedAttributesOptions[$key]);
                break;
            }
        }
        $sess->$varOpt = $storedAttributesOptions;
    }


}
