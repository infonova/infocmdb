<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_OptionNew extends Form_AbstractAppForm
{
    public function __construct($translator, $attributeId, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAction(APPLICATION_URL . 'attribute/optionwizard/attributeId/' . $attributeId . '/isNew/1');


        // Name
        $name = new Zend_Form_Element_Text('optionName');
        $name->setLabel('optionName');

        $order = new Zend_Form_Element_Text('ordernumber');
        $order->setLabel('ordernumber');
        $order->setAttrib('size', '5');
        $order->setAttrib('maxlength', '3');

        $submit = new Zend_Form_Element_Submit('create');
        $submit->setAttrib('class', 'standard_button');
        $submit->setLabel('optionAdd');

        $this->addElements(array($name, $order, $submit));

    }

}