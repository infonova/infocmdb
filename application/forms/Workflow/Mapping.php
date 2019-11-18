<?php

class Form_Workflow_Mapping extends Form_AbstractAppForm
{

    public function __construct($translator, $mapping, $mappingType, $options = array())
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        foreach ($mapping as $map) {
            $type = new Zend_Form_Element_MultiCheckbox($mappingType . '__' . $map['id']);
            $type->setMultiOptions(array('create' => '', 'update' => '', 'delete' => ''));
            $type->setLabel($map['description'])
                ->setSeparator(' ');

            if ($mappingType == 'ci_type_change')
                $type->setAttrib('disable', array('create', 'delete'));


            $this->addElements(array($type));
        }
        $this->setElementDecorators(array(
            'ViewHelper',
            array('HtmlTag', array('tag' => 'div', 'class' => 'radio_group')),
        ));
    }

}