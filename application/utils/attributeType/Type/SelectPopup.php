<?php

class Util_AttributeType_Type_SelectPopup extends Util_AttributeType_Type_Abstract
{

    const ATTRIBUTE_VALUES_ID = 'ci';
    const ATTRIBUTE_TYPE_ID   = 22;


    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        return new Form_Attribute_IndividualFilter($translator);
    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#setAttributeValue($attribute, $path)
     */
    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        $id                                    = $attribute[Db_CiAttribute::VALUE_CI];
        $value                                 = $attribute['valueNote'];
        $attribute[Db_CiAttribute::VALUE_TEXT] = '<a href="' . APPLICATION_URL . '/ci/detail/ciid/' . $id . '">' . $value . '</a>';
        $attribute['noEscape']                 = true;
        return $attribute;
    }


    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::ID] . $key . 'ci'];
        return array(
            'value' => $currentVal,
        );
    }

    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElementsForSingleEdit($ciAttribute)
     */
    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {
        $genId       = '0';
        $description = new Zend_Form_Element_Hidden($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] . 'description');
        $description->setLabel('ciDescription');
        $description->setDecorators(array('ViewHelper', 'Errors'));
        $description->setValue($ciAttribute[Db_CiAttribute::NOTE]);

        $fileName = new Zend_Form_Element_Hidden($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] . 'ci');
        $fileName->setLabel('ciId');
        $fileName->setDecorators(array('ViewHelper', 'Errors'));
        $fileName->setValue($ciAttribute[Db_CiAttribute::VALUE_CI]);

        $link = new Zend_Form_Element_Image($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID]);
        $link->setImage(APPLICATION_URL . 'images/icon/upload.png');
        $link->setLabel($ciAttribute[Db_Attribute::DESCRIPTION]);
        $link->setAttrib('title', "upload");
        $link->setAttrib('linkname', 'upload');
        $link->setAttrib('class', "tu_iframe_800x600 pseudoDropdownButton");
        $link->setAttrib('tabindex', '-1');

        $link->setAttrib('href', APPLICATION_URL . 'ci/filterpagination/genId//attributeId/' . $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] . '/ciid/' . $ciAttribute[Db_CiAttribute::CI_ID]);
        $link->setAttrib('toptions', "effect = clip, layout = flatlook");
        $link->setDecorators(array('ViewHelper', 'Errors', 'Label', array(array('data' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element'))));


        $hint = $ciAttribute[Db_Attribute::HINT];

        if ($hint) {
            $link->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($description, $fileName);
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


        $description = new Zend_Form_Element_Hidden($attributeId . $key . 'description');
        $description->setLabel('ciDescription');
        if ($maxLength)
            $description->setAttrib('maxlength', $maxLength);

        $fileName = new Zend_Form_Element_Hidden($attributeId . $key . 'ci');
        $fileName->setLabel('ciId');
        if ($maxLength)
            $fileName->setAttrib('maxlength', $maxLength);

        $link = new Zend_Form_Element_Button($attributeId . $key);

        //$link->setImage(APPLICATION_URL.'images/icon/dropdown.png');
        $link->setLabel($attributeDescription);
        $link->setAttrib('title', "upload");
        $link->setAttrib('linkname', 'upload');
        $link->setAttrib('class', "tu_iframe_800x600 pseudoDropdownButton");
        $link->setAttrib('tabindex', '-1');
        $link->setAttrib('href', APPLICATION_URL . 'ci/filterpagination/attributeId/' . $attributeId . '/genId/' . $key . '/ciid/' . $ciId);
        $link->setAttrib('toptions', "effect = clip, layout = flatlook");
        $link->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'script-row'))));


        if ($isUnique) {
            $description->addValidator(new Form_Validator_UniqueConstraint($attributeId, $ciId));
            $link->setLabel('(u) ' . $attributeDescription);
        }

        if ($notNull) {
            $fileName->setRequired(true);
            $fileName->setAutoInsertNotEmptyValidator(true);
        }

        if (!$write) {
            $link->setAttrib('class', 'disabled');
            $description->setAttrib('class', 'disabled');
            $fileName->setAttrib('class', 'disabled');
        }

        if ($attributeNote) {
            $link->removeDecorator('description');
            $link->setDescription($attributeNote);
            $link->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $link->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($link, $description, $fileName);
    }


    public function returnFormData($values, $attributeId = null)
    {
        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $values[$attributeId . 'ci'];
        $data[Db_CiAttribute::NOTE]     = $values[$attributeId . 'description'];
        return $data;
    }

    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        $formData[$attribute[Db_Attribute::ID] . $attribute['genId'] . 'ci']          = $values[$storedIDs[0]][Db_CiAttribute::VALUE_CI];
        $formData[$attribute[Db_Attribute::ID] . $attribute['genId'] . 'description'] = $values[$storedIDs[0]][Db_CiAttribute::NOTE];
        $formData[$attribute[Db_Attribute::NAME] . 'hidden']                          = $values[$storedIDs[0]]['ciAttributeId'];

        return $formData;
    }

    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Type/Util_AttributeType_Type_Abstract#getCiEditData($values, $attribute, $key, $currentVal)
     */
    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $ciId = $values[$attribute[Db_Attribute::ID] . $key . 'ci'];
        $note = $values[$attribute[Db_Attribute::ID] . $key . 'description'];

        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $ciId;
        $data[Db_CiAttribute::NOTE]     = $note;

        return $data;
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key        = $attribute['genId'];
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        $ciId = $values[$attribute[Db_Attribute::ID] . $key . 'ci'];
        $note = $values[$attribute[Db_Attribute::ID] . $key . 'description'];

        if (!$ciId)
            return null;

        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $ciId;
        $data[Db_CiAttribute::NOTE]     = $note;
        return $data;
    }


    public function getString(&$form, $attribute)
    {
        $varName            = $attribute['id'] . $attribute['genId'];
        $descriptionVarName = $attribute['id'] . $attribute['genId'] . 'description';
        $ciVar              = $attribute['id'] . $attribute['genId'] . 'ci';
        $class              = "";

        //Admin-Mode: show attribute-name
        if (Zend_Registry::get('adminMode') === true) {
            $attribute['note'] = $attribute['description'];
            $form->$varName->setLabel($attribute['name']);
        }

        if ($form->$ciVar->isRequired()) {
            $class = "required";
        }

        $returnString = "<td>
							<label title=\"" . htmlspecialchars($attribute['note']) . "\" class='" . $class . "'>" . $form->$varName->getLabel() . "</label>
						</td>
						<td>";


        $value = "";

        $returnString .= $form->$descriptionVarName->setDecorators(array('Errors', array('ViewHelper', array('tag' => '<div>'))));

        if ($form->getValue($descriptionVarName)) {
            $value .= $form->getValue($descriptionVarName);
        }

        $returnString .= $form->$ciVar->setDecorators(array('Errors', array('ViewHelper', array('tag' => '<div>'))));

        if ($form->getValue($ciVar)) {
            $value .= $form->getValue($ciVar);
        }

        $returnString .= '<input id="' . $attribute['id'] . $attribute['genId'] . 'ciupload" class="ui-autocomplete-input ui-widget ui-widget-content ui-corner-left" autocomplete="off" role="textbox" aria-autocomplete="list" aria-haspopup="true" ';
        $returnString .= 'value="' . $value . '"';
        $returnString .= '</input>';


        $link = APPLICATION_URL . 'ci/filterpagination/genId/' . $attribute['genId'] . '/attributeId/' . $attribute['id'];

        $returnString .= '<button id="' . $varName . '" title="upload" linkname="upload" href="' . $link . '" toptions="effect = clip, layout = flatlook" class="tu_iframe_800x600 ui-button ui-widget ui-state-default ui-button-icon-only ui-corner-right ui-button-icon" role="button" aria-disabled="false" title="&nbsp;" style="left: -1px;"><span class="ui-button-icon-primary ui-icon ui-icon-triangle-1-s"></span><span class="ui-button-text">&nbsp;</span></button>';

        //$returnString .= $form->$varName->setDecorators(array('Errors', array('ViewHelper', array('tag'=>'<div>'))));
        if ($attribute['hint']) {
            $returnString .= $form->$ciVar->setDecorators(array(new Form_Decorator_MyTooltip()));
        }
        $returnString .= "</td>";

        return $returnString;
    }
}