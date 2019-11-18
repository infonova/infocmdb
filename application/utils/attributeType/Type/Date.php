<?php

class Util_AttributeType_Type_Date extends Util_AttributeType_Type_Abstract
{

    const ATTRIBUTE_VALUES_ID = 'date';
    const ATTRIBUTE_TYPE_ID   = 7;

    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        return new Form_Attribute_IndividualDate($translator);
    }


    public function normalizeValue($attributeId, $value)
    {
        if (!is_array($value)) {
            // special handling!
            $date = date('Y-m-d', strtotime($value));
        } else {
            $date = $value['yyyy'] . '-' . $value['mm'] . '-' . $value['dd'];
        }

        if ($value === '00000000')
            $date = '00000000';

        return $date;
    }

    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElementsForSingleEdit($ciAttribute)
     */
    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {

        $date_picker = new Zend_Form_Element_Text('date');
        $date_picker->setAttrib('class', 'datetime-picker');

        $hint = $ciAttribute[Db_Attribute::HINT];

        if ($hint) {
            $date_picker->setDescription($this->prepareHintForTooltip($hint));
        }

        $date_picker->setValue($ciAttribute[Db_CiAttribute::VALUE_DATE]);
        return array($date_picker);
    }


    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        list($year, $month, $dayPrev) = explode('-', $attribute['value_date']);
        list($day, $t) = explode(' ', $dayPrev);

        $attribute['value_date'] = $year . '-' . $month . '-' . $day;
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
        $hint                 = $ciAttribute[Db_Attribute::HINT];

        $label = new Zend_Form_Element_Hidden($attributeName . 'label');
        $label->setLabel($attributeDescription);
        $label->clearDecorators();

        $date_picker = new Zend_Form_Element_Text($attributeName);
        $date_picker->setLabel('Date:');
        $date_picker->setAttrib('class', 'datetime-picker');

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


    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $data                             = array();
        $data[Db_CiAttribute::VALUE_DATE] = $currentVal;
        return $data;
    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Type/Util_AttributeType_Type_Abstract#addFormData($formData, $attribute, $values, $storedIDs)
     */
    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        $data = null;
        $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_DATE];

        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId'] . 'hidden'] = $values[$storedIDs[0]]['ciAttributeId'];
        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId']]            = $data;

        return $formData;
    }

    public function returnFormData($values, $attributeId = null)
    {
        if (isset($values['value'])) {
            $value = $values['value'];
        } elseif (isset($values['date'])) {
            $value = $values['date'];
        } elseif (isset($values['datetime'])) {
            $value = $values['datetime'];
        }

        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = null;
        $data[Db_CiAttribute::VALUE_DATE] = date('Y-m-d H:i:s', strtotime($value));

        return $data;
    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Type/Util_AttributeType_Type_Abstract#getCurrentAttributeValue($values, $attribute, $key)
     */
    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];
        if (is_null($currentVal) || $currentVal == '0000-00-00' || $currentVal == '--') {
            $currentVal = "";
        }
        return array(
            'value' => $currentVal,
        );
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key = $attribute['genId'];

        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        if (is_null($currentVal) || $currentVal == '0000-00-00' || $currentVal == '--') {
            $currentVal = null;
        }

        if (!$currentVal)
            return null;

        $data                             = array();
        $data[Db_CiAttribute::VALUE_DATE] = $currentVal;
        return $data;
    }


    public function getString(&$form, $attribute)
    {

        if (!($attribute[Db_AttributeRole::PERMISSION_READ] === '1' || $attribute[Db_AttributeRole::PERMISSION_WRITE] === '1')) {
            return "";
        }

        $varName = $attribute['name'] . $attribute['genId'] . 'label';

        //Admin-Mode: show attribute-name
        if (Zend_Registry::get('adminMode') === true) {
            $attribute['note'] = $attribute['description'];
            $link              = "<a href='" . APPLICATION_URL . "attribute/edit/attributeId/" . $attribute['id'] . "'><img class='image' src='" . APPLICATION_URL . "images/navigation/settings.png'></a>";
            $form->$varName->setLabel($link . ' ' . $attribute['name']);
        }

        $form->$varName->clearDecorators();
        $form->$varName->setDecorators(array('Errors', array('Label', array('tag' => '<div>', 'title' => $attribute['note'], 'escape' => false))));


        if ($attribute[Db_AttributeRole::PERMISSION_READ] && !$attribute[Db_AttributeRole::PERMISSION_WRITE]) {

            $date = $attribute['name'] . $attribute['genId'];

            $ciId                                  = $form->ciid->getValue();
            $attribute[Db_CiAttribute::VALUE_DATE] = $form->$date->getValue();
            $retArray                              = $this->setAttributeValue($attribute, $ciId);

            return '<td>' . $form->$varName . '</td><td>' . $retArray[Db_CiAttribute::VALUE_DATE] . '</td>';

        }

        $returnString = "<td>";


        $returnString .= $form->$varName;
        $returnString .= "</td>
						<td>";

        $varName = $attribute['name'] . $attribute['genId'];
        $form->$varName->setDecorators(array(array('viewHelper', array('tag' => '<div>')), 'Errors'));

        $returnString .= $form->$varName;
        if ($attribute['hint']) {
            $returnString .= $form->$varName->setDecorators(array(new Form_Decorator_MyTooltip()));
        }
        $returnString .= "</td>";
        return $returnString;
    }
}