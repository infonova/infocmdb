<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_IndividualInfo extends Zend_Form_SubForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('info');
        $this->setAttrib('enctype', 'multipart/form-data');


        // attribute Info
        $attributeInfo = new Zend_Form_Element_Textarea('attributeInfo');
        $attributeInfo->setLabel('attributeInfo');
        $attributeInfo->setAttrib('cols', 35);
        $attributeInfo->setAttrib('rows', 4);
        $attributeInfo->setAttrib('title', $translator->translate('attributeAttributeInfoTitle'));
        $attributeInfo->setAttrib('class', 'tinymce');
        $this->addElement($attributeInfo);


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