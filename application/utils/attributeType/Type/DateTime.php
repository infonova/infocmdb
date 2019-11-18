<?php

class Util_AttributeType_Type_DateTime extends Util_AttributeType_Type_Date
{

    const ATTRIBUTE_TYPE_ID = 8;

    public function normalizeValue($attributeId, $value)
    {
        if (!is_array($value)) {
            return date('Y-m-d H:i:s', strtotime($value));
        }

        return $value;
    }

    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElementsForSingleEdit($ciAttribute)
     */
    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {
        $hint        = $ciAttribute[Db_Attribute::HINT];
        $date_picker = new Zend_Form_Element_Text('datetime');
        $date_picker->setAttrib('class', 'datetime-picker');
        $date_picker->setAttrib('data-enabletime', 'true');

        $hint = $ciAttribute[Db_Attribute::HINT];

        if ($hint) {
            $date_picker->setDescription($this->prepareHintForTooltip($hint));
        }

        $date_picker->setValue($ciAttribute[Db_CiAttribute::VALUE_DATE]);
        return array($date_picker);
    }


    public function setAttributeValue($attribute, $ciId = null, $path = null)
    {
        return $attribute;
    }

    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElements($ciAttribute)
     */
    public function getFormElements($ciAttribute, $key = null, $ciId = null, $isValidate = false, $userId = null)
    {
        $hint                 = $ciAttribute[Db_Attribute::HINT];
        $attributeName        = $ciAttribute[Db_Attribute::NAME];
        $attributeDescription = $ciAttribute[Db_Attribute::DESCRIPTION];
        $notNull              = $ciAttribute[Db_CiTypeAttribute::IS_MANDATORY];
        $regex                = $ciAttribute['regex'];
        $write                = $ciAttribute['permission_write'];
        $hint                 = $ciAttribute[Db_Attribute::HINT];
        $attributeNote        = $ciAttribute[Db_Attribute::NOTE];

        $label = new Zend_Form_Element_Hidden($attributeName . 'label');
        $label->setLabel($attributeDescription);
        $label->clearDecorators();

        $date_picker = new Zend_Form_Element_Text($attributeName);
        $date_picker->setLabel('Date:');
        $date_picker->setAttrib('class', 'datetime-picker');
        $date_picker->setAttrib('data-enabletime', 'true');


        if ($notNull) {
            $date_picker->setRequired(true);
            $date_picker->setAutoInsertNotEmptyValidator(true);

            $label->setRequired(true);
            $label->setAutoInsertNotEmptyValidator(false);
        }

        if (!$write) {
            $date_picker->setAttrib('disabled', true);
            $date_picker->setAttrib('class', 'disabled');
        }

        if ($regex) {
            $date_picker->addValidator('regex', false, array($regex));
        }

        if ($attributeNote) {
            $label->removeDecorator('description');
            $label->setDescription($attributeNote);
            $label->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $date_picker->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($label, $date_picker);

    }


    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        return parent::addFormData($formData, $attribute, $values, $storedIDs);
    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Type/Util_AttributeType_Type_Abstract#getCurrentAttributeValue($values, $attribute, $key)
     */
    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        $check = strtotime($currentVal);

        if (!$check) {
            // check invalid
            $currentVal = null;
        } else if (!$currentVal || $currentVal == '0000-00-00 00:00:00') {
            $currentVal = null;
        } else if (strpos($currentVal, '0000-00-00') !== false || strpos($currentVal, '-- :00') !== false) {
            $currentVal = null;
        }

        return array(
            'value' => $currentVal,
        );
    }

    public function addCi($values, $attribute, $ciId)
    {
        $key = $attribute['genId'];

        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        if (!$currentVal) {
            return null;
        }

        if ($currentVal == '0000-00-00 00:00:00') {
            return null;
        }

        if (strpos($currentVal, '--') !== false) {
            return null;
        }

        $data                             = array();
        $data[Db_CiAttribute::VALUE_DATE] = $currentVal;
        return $data;
    }


    public function getString(&$form, $attribute)
    {
        return parent::getString($form, $attribute);
    }
}