<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_IndividualMultiselect extends Zend_Form_SubForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('multiselect');
        $this->setAttrib('enctype', 'multipart/form-data');


        //width
        $textfieldWidth = new Zend_Form_Element_Text('textfieldWidth');
        $textfieldWidth->setLabel('textfieldWidth');
        $textfieldWidth->setAttrib('maxlength', 4);
        $textfieldWidth->setValue(180);
        $textfieldWidth->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $textfieldWidth->setAttrib('title', $translator->translate('attributeTextfieldWidthTitle'));
        $this->addElement($textfieldWidth);

        $autocomplete = new Zend_Form_Element_Select('autocomplete');
        $autocomplete->setDescription($translator->translate('attributeAutocompleteWarning'));
        $autocomplete->setLabel('sqlAutocomplete');
        $autocomplete->setAttrib('title', $translator->translate('sqlAutocomplete'));
        $autocomplete->setMultiOptions(array(
            'select_one'                         => $translator->translate('attributeAutocompleteSelectOne'),
            'autocomplete_one'                   => $translator->translate('attributeAutocompleteOne'),
            'autocomplete_multiple'              => $translator->translate('attributeAutocompleteMultiple'),
            'autocomplete_multiple_with_counter' => $translator->translate('attributeAutocompleteMultipleWithCounter'),
        ));
        $this->addElement($autocomplete);

        // option separator
        $ciTypeOptionSeparator = new Zend_Form_Element_Select('displayStyle');
        $ciTypeOptionSeparator->setMultiOptions(array(
            'optionList' => $translator->translate('attributeStyleOptionList'),
            'oneRow'     => $translator->translate('attributeStyleOneRow'),
        ));
        $ciTypeOptionSeparator->setLabel('attributeDisplayStyle');
        $this->addElement($ciTypeOptionSeparator);

        // project restriction
        $projectRestriction = new Zend_Form_Element_Checkbox('ProjectRestriction');
        $projectRestriction->setLabel('ProjectRestriction');
        $projectRestriction->setAttrib('title', $translator->translate('attributeProjectRestrictionTitle'));
        $this->addElement($projectRestriction);


        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            new Form_Decorator_MyTooltip(),
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