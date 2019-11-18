<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_IndividualOptions extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('options');
        $this->setAttrib('enctype', 'multipart/form-data');


        // Name
        $name = new Zend_Form_Element_Text('option');
        $name->setLabel('optionName');
        $this->addElement($name);


        $order = new Zend_Form_Element_Text('ordernumber');
        $order->setLabel('ordernumber');
        $order->setAttrib('size', '5');
        $order->setAttrib('maxlength', '3');
        $this->addElement($order);


        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));


        $submit = new Zend_Form_Element_Submit('create');
        $submit->setAttrib('class', 'standard_button');
        $submit->setAttrib('onClick', 'javascript:addOption();return false;');
        $submit->setLabel('optionAdd');

        $submit->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $this->addElement($submit);
    }

}