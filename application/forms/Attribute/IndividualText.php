<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_IndividualText extends Zend_Form_SubForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('text');
        $this->setAttrib('enctype', 'multipart/form-data');

        $attributeGroupConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);

        // input length
        $inputLength = new Zend_Form_Element_Text('inputLength');
        $inputLength->setLabel('inputLength');
        $inputLength->setValue(0);
        $inputLength->setAttrib('maxlength', 4);
        $inputLength->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $inputLength->setAttrib('title', $translator->translate('attributeInputLengthTitle'));

        if ($attributeGroupConfig->attribute->inputLength->validators->strlen->enabled) {
            $inputLength->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attribute', 'inputLength'), true);
        }
        if ($attributeGroupConfig->attribute->inputLength->validators->regex->enabled) {
            $inputLength->addValidator($this->createRegexValidator($attributeGroupConfig, 'attribute', 'inputLength'), true);
        }
        $this->addElement($inputLength);

        // textfield height
        $textfieldHeight = new Zend_Form_Element_Text('textfieldHeight');
        $textfieldHeight->setLabel('textfieldHeight');
        $textfieldHeight->setAttrib('maxlength', 4);
        $textfieldHeight->setValue(3);
        $textfieldHeight->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $textfieldHeight->setAttrib('title', $translator->translate('attributeTextfieldHeightTitle'));

        if ($attributeGroupConfig->attribute->textfieldHeight->validators->strlen->enabled) {
            $textfieldHeight->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attribute', 'textfieldHeight'), true);
        }
        if ($attributeGroupConfig->attribute->textfieldHeight->validators->regex->enabled) {
            $textfieldHeight->addValidator($this->createRegexValidator($attributeGroupConfig, 'attribute', 'textfieldHeight'), true);
        }
        $this->addElement($textfieldHeight);

        // textfield width
        $textfieldWidth = new Zend_Form_Element_Text('textfieldWidth');
        $textfieldWidth->setLabel('textfieldWidth');
        $textfieldWidth->setAttrib('maxlength', 4);
        $textfieldWidth->setValue(30);
        $textfieldWidth->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $textfieldWidth->setAttrib('title', $translator->translate('attributeTextfieldWidthTitle'));

        if ($attributeGroupConfig->attribute->textfieldWidth->validators->strlen->enabled) {
            $textfieldHeight->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attribute', 'textfieldWidth'), true);
        }
        if ($attributeGroupConfig->attribute->textfieldWidth->validators->regex->enabled) {
            $textfieldHeight->addValidator($this->createRegexValidator($attributeGroupConfig, 'attribute', 'textfieldWidth'), true);
        }
        $this->addElement($textfieldWidth);

        // regex
        $regex = new Zend_Form_Element_Text('regex');
        $regex->setLabel('regex');
        $regex->setAttrib('maxlength', 200);
        $regex->setAttrib('title', $translator->translate('attributeRegexTitle'));
        $this->addElement($regex);

        // is numeric
        $isNumeric = new Zend_Form_Element_Checkbox('isNumeric');
        $isNumeric->setLabel('isNumeric');
        $isNumeric->setAttrib('title', $translator->translate('attributeIsNumericTitle'));
        $this->addElement($isNumeric);

        // unique constraint (yes/no)
        $uniqueConstraint = new Zend_Form_Element_Checkbox('uniqueConstraint');
        $uniqueConstraint->setLabel('uniqueConstraint');
        $uniqueConstraint->setAttrib('title', $translator->translate('attributeUniqueConstraintTitle'));
        $uniqueConstraint->setDescription($translator->translate('unique_desc'));
        $uniqueConstraint->addValidator(new Form_Validator_AttributeUnique($options['attributeID']));
        $this->addElement($uniqueConstraint);

        // unique check
        $uniqueCheck = new Zend_Form_Element_Checkbox('uniqueCheck');
        $uniqueCheck->setLabel('todoItem');
        $uniqueCheck->setAttrib('title', $translator->translate('attributeTodoItemTitle'));
        $this->addElement($uniqueCheck);

        $defaultvalue = new Zend_Form_Element_Textarea('defaultvalue');
        $defaultvalue->setLabel('defaultvalue');
        $defaultvalue->setAttrib('rows', '10');
        $defaultvalue->setAttrib('cols', '40');
        $defaultvalue->setAttrib('title', $translator->translate('defaultvalueTitle'));
        $this->addElement($defaultvalue);

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));
    }

}