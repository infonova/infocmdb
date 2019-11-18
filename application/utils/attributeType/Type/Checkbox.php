<?php

class Util_AttributeType_Type_Checkbox extends Util_AttributeType_Type_Abstract
{

    const ATTRIBUTE_VALUES_ID = 'text';
    const ATTRIBUTE_TYPE_ID   = 5;


    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        $form = new Form_Attribute_IndividualOptions($translator);

        return $form;
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

        $checkbox = new Zend_Form_Element_MultiCheckbox('value');
        $checkbox->setLabel($ciAttribute[Db_Attribute::DESCRIPTION]);
        $checkbox->addMultiOptions($selection);
        $checkbox->setValue(explode(',', $ciAttribute[Db_CiAttribute::VALUE_TEXT]));

        if ($hint) {
            $checkbox->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($checkbox);
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
            $defaults         = $attributeDaoImpl->getAttributeDefaultValues($attribute[Db_Attribute::ID], false);
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

        // CHECK BOX ->option check
        $checkbox = new Zend_Form_Element_MultiCheckbox($attributeName);
        $checkbox->setLabel($attributeDescription);
        $checkbox->addMultiOptions($selection);

        if ($maxLength)
            $checkbox->setAttrib('maxlength', $maxLength);
        $checkbox->setSeparator('<br>');

        if ($notNull) {
            $checkbox->setRequired(true);
            $checkbox->setAutoInsertNotEmptyValidator(true);
        }

        if (!$write) {
            $checkbox->setAttrib('disabled', true);
            $checkbox->setAttrib('class', 'disabled');
        }

        if ($attributeNote) {
            $checkbox->removeDecorator('description');
            $checkbox->setDescription($attributeNote);
            $checkbox->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $checkbox->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($checkbox);
    }


    public function returnFormData($values, $attributeId = null)
    {
        $data = array();

        if (is_array($values['value'])) {
            $data[Db_CiAttribute::VALUE_TEXT] = implode(', ', $values['value']);
        } else {
            $data[Db_CiAttribute::VALUE_TEXT] = $values['value'];
        }

        return $data;
    }

    /**
     * retriebe Data to update (for ci edit)
     *
     * @param unknown_type $values
     * @param unknown_type $attribute
     * @param unknown_type $key
     * @param unknown_type $currentVal
     */
    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $data = array();

        if (is_array($currentVal)) {
            $data[Db_CiAttribute::VALUE_TEXT] = implode(', ', $currentVal);
        } else {
            $data[Db_CiAttribute::VALUE_TEXT] = $currentVal;
        }

        return $data;
    }

    public function isOptionUsed($optionId, $attributeId)
    {
        $attributeDaoImpl = new Dao_Attribute();
        if ($attributeDaoImpl->getCiAttributeUsingAttributeDefaultValueForCheckbox($optionId, $attributeId))
            return true;
        else
            return false;
    }

    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_TEXT];

        $valueArray = explode(',', $data);

        // just to make sure...
        foreach ($valueArray as $key => $value) {
            $value            = trim($value);
            $valueArray[$key] = $value;
        }

        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId']]            = $valueArray;
        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId'] . 'hidden'] = $values[$storedIDs[0]]['ciAttributeId'];
        return $formData;
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key = $attribute['genId'];

        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        if (is_array($currentVal))
            $currentVal = implode(', ', $currentVal);

        if (!$currentVal)
            return null;

        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = $currentVal;
        return $data;
    }


    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        if (is_array($currentVal))
            $currentVal = implode(', ', $currentVal);

        if (!$currentVal)
            return null;

        return array(
            'value' => trim($currentVal),
        );
    }
}