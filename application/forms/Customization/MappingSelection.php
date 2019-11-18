<?php

class Form_Customization_MappingSelection extends Form_AbstractAppForm
{

    public function __construct($translator, $options = array())
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        $ci = new Zend_Form_Element_Checkbox('ci_active');
        $ci->setOptions(array('ci_active' => 'ci_active'));
        $ci->setLabel('ci_active');

        $attribute = new Zend_Form_Element_Checkbox('attribute_active');
        $attribute->setOptions(array('attribute_active' => 'attribute_active'));
        $attribute->setLabel('attribute_active');

        $relation = new Zend_Form_Element_Checkbox('relation_active');
        $relation->setOptions(array('relation_active' => 'relation_active'));
        $relation->setLabel('relation_active');

        $project = new Zend_Form_Element_Checkbox('project_active');
        $project->setOptions(array('project_active' => 'project_active'));
        $project->setLabel('project_active');

        $this->addElements(array($ci, $attribute, $relation, $project));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Label',
            array('HtmlTag', array('tag' => 'td')),
        ));
    }

}