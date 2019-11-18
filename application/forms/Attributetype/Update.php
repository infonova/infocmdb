<?php

/**
 * This class is used to create the menu update form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attributetype_Update extends Form_AbstractAppForm
{
    public function __construct($translator, $config)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        $orderNumber = new Zend_Form_Element_Text('order');
        $orderNumber->setLabel('order');
        $orderNumber->setAttrib('onKeyPress', 'return numbersonly(this, event);');

        $submit = new Zend_Form_Element_Submit('create');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');

        $cancel = new Zend_Form_Element_Submit('cancel');
        $cancel->setLabel('cancel');
        $cancel->setAttrib('class', 'standard_button');
        $cancel->setAttrib('onClick', "javascript:window.location.href='/attributetype/index';return false;");

        $this->addElements(array($name, $description, $note, $orderNumber, $submit, $cancel));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));


        $this->getElement('create')->removeDecorator('Label');
        $this->getElement('cancel')->removeDecorator('Label');

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));

    }

}