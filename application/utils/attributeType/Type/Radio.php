<?php

class Util_AttributeType_Type_Radio extends Util_AttributeType_Type_Abstract
{

    protected $_formElements;

    const ATTRIBUTE_VALUES_ID = 'default';
    const ATTRIBUTE_TYPE_ID   = 6;


    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        $form = new Form_Attribute_IndividualOptions($translator);

        return $form;
    }


    /**
     * modifies the attribute value to be displayed
     *
     * @param unknown_type $attribute
     * @param unknown_type $path
     */
    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        if ($attribute[Db_CiAttribute::VALUE_TEXT]) {
            $attributeDaoImpl = new Dao_Attribute();
            $defaults         = $attributeDaoImpl->getAttributeDefaultValues($attribute[Db_Attribute::ID]);
            $actuals          = explode(',', $attribute[Db_CiAttribute::VALUE_TEXT]);

            $attributeIdList = '';
            foreach ($defaults as $att) {
                if (in_array((int)$att['id'], $actuals))
                    $attributes[] = $att['value'];
            }
            if (is_array($attributes))
                $attribute[Db_CiAttribute::VALUE_TEXT] = implode(', ', $attributes);
        }
        return $attribute;
    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElementsForSingleEdit($ciAttribute)
     */
    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {
        $hint             = $ciAttribute[Db_Attribute::HINT];
        $attributeDaoImpl = new Dao_Attribute();
        $sel              = $attributeDaoImpl->getAttributeDefaultValues($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID]);

        $selection = array();
        foreach ($sel as $row) {
            $selection[$row[Db_AttributeDefaultValues::ID]] = $row[Db_AttributeDefaultValues::VALUE];
        }

        $radio = new Zend_Form_Element_Radio('value');
        $radio->setLabel($ciAttribute[Db_Attribute::DESCRIPTION]);
        $radio->addMultiOptions($selection);

        if ($ciAttribute[Db_CiAttribute::VALUE_DEFAULT])
            $radio->setValue($ciAttribute[Db_CiAttribute::VALUE_DEFAULT]);

        if ($hint) {
            $radio->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($radio);

    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElements($ciAttribute)
     */
    public function getFormElements($ciAttribute, $key = null, $ciId = null, $isValidate = false, $userId = null)
    {
        $attributeId          = $ciAttribute[Db_Attribute::ID];
        $attributeName        = $ciAttribute[Db_Attribute::NAME];
        $attributeDescription = $ciAttribute[Db_Attribute::DESCRIPTION];
        $attributeNote        = $ciAttribute[Db_Attribute::NOTE];
        $attributeType        = $ciAttribute['type'];
        $attributeValue       = $ciAttribute['value'];
        $notNull              = $ciAttribute[Db_CiTypeAttribute::IS_MANDATORY];
        $isUnique             = $ciAttribute[Db_Attribute::IS_UNIQUE];
        $regex                = $ciAttribute['regex'];
        $write                = $ciAttribute['permission_write'];
        $maxLength            = $ciAttribute[Db_Attribute::INPUT_MAXLENGTH];
        $cols                 = $ciAttribute[Db_Attribute::TEXTAREA_COLS];
        $rows                 = $ciAttribute[Db_Attribute::TEXTAREA_ROWS];
        $hint                 = $ciAttribute[Db_Attribute::HINT];


        $attributeDaoImpl = new Dao_Attribute();
        $sel              = $attributeDaoImpl->getAttributeDefaultValues($attributeId);

        $selection = array();
        foreach ($sel as $row) {
            $selection[$row[Db_AttributeDefaultValues::ID]] = $row[Db_AttributeDefaultValues::VALUE];
        }

        // RADIO ->option radio
        $radio = new Zend_Form_Element_Radio($attributeName);
        $radio->setLabel($attributeDescription . ': ');
        $radio->addMultiOptions($selection);

        if ($maxLength)
            $radio->setAttrib('maxlength', $maxLength);
        $radio->setAttrib('class', 'radiobutton');
        $radio->setSeparator('<br>');

        if ($notNull) {
            $radio->setRequired(true);
            $radio->setAutoInsertNotEmptyValidator(true);
        }

        if (!$write) {
            $radio->setAttrib('disabled', true);
            $radio->setAttrib('class', 'disabled');
        }

        if ($attributeNote) {
            $radio->removeDecorator('description');
            $radio->setDescription($attributeNote);
            $radio->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $radio->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($radio);
    }


    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        $data = null;
        $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_DEFAULT];

        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId']]            = $data;
        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId'] . 'hidden'] = $values[$storedIDs[0]]['ciAttributeId'];
        return $formData;
    }


    public function returnFormData($values, $attributeId = null)
    {
        $data                                = array();
        $data[Db_CiAttribute::VALUE_DEFAULT] = $values['value'];
        return $data;
    }


    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $data                                = array();
        $data[Db_CiAttribute::VALUE_DEFAULT] = $currentVal;
        return $data;
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key        = $attribute['genId'];
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        if (!$currentVal)
            return null;

        if ($currentVal == '0') {
            return null;
        }

        $data                                = array();
        $data[Db_CiAttribute::VALUE_DEFAULT] = $currentVal;
        return $data;
    }

}