<?php

class Form_Customization_Mapping extends Form_AbstractAppForm
{

    public function __construct($translator, $mapping, $options = array())
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        foreach ($mapping as $map) {
            $type = new Zend_Form_Element_MultiCheckbox($map['id']);
            $type->setMultiOptions(array('create' => '', 'update' => '', 'delete' => ''));
            $type->setLabel($map['description'])
                ->setSeparator(' ');

            $this->addElements(array($type));
        }
        $this->setElementDecorators(array(
            'ViewHelper',
            array('HtmlTag', array('tag' => 'div', 'class' => 'radio_group')),
        ));

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');

        $this->addElement($submit);
    }

}