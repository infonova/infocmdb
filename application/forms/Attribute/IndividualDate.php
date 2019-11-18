<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_IndividualDate extends Zend_Form_SubForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('date');
        $this->setAttrib('enctype', 'multipart/form-data');


        // date event
        $dateEvent = new Zend_Form_Element_Checkbox('dateEvent');
        $dateEvent->setLabel('attributeDateEvent');
        $dateEvent->setAttrib('title', $translator->translate('attributeDateEventTitle'));
        $this->addElement($dateEvent);


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