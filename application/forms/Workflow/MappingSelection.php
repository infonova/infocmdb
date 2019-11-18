<?php

class Form_Workflow_MappingSelection extends Form_AbstractAppForm
{

    public function __construct($translator, $options = array())
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        $ci = new Zend_Form_Element_Checkbox('trigger_ci');
        $ci->setOptions(array('trigger_ci' => 'trigger_ci'));
        $ci->setLabel('trigger_ci');
        $ci->setAttrib('title', $translator->translate('workflowCiTriggerTitle'));

        $ci_type_change = new Zend_Form_Element_Checkbox('trigger_ci_type_change');
        $ci_type_change->setOptions(array('trigger_ci_type_change' => 'trigger_ci_type_change'));
        $ci_type_change->setLabel('trigger_ci_type_change');
        $ci_type_change->setAttrib('title', $translator->translate('workflowCiTypeChangeTriggerTitle'));


        $attribute = new Zend_Form_Element_Checkbox('trigger_attribute');
        $attribute->setOptions(array('trigger_attribute' => 'trigger_attribute'));
        $attribute->setLabel('trigger_attribute');
        $attribute->setAttrib('title', $translator->translate('workflowAttributeTriggerTitle'));

        $relation = new Zend_Form_Element_Checkbox('trigger_relation');
        $relation->setOptions(array('trigger_relation' => 'trigger_relation'));
        $relation->setLabel('trigger_relation');
        $relation->setAttrib('title', $translator->translate('workflowRelationTriggerTitle'));

        $project = new Zend_Form_Element_Checkbox('trigger_project');
        $project->setOptions(array('trigger_project' => 'trigger_project'));
        $project->setLabel('trigger_project');
        $project->setAttrib('title', $translator->translate('workflowProjectTriggerTitle'));

        $file_import = new Zend_Form_Element_Checkbox('trigger_fileimport');
        $file_import->setOptions(array('trigger_fileimport' => 'trigger_fileimport'));
        $file_import->setLabel('trigger_fileimport');
        $file_import->setAttrib('title', $translator->translate('workflowFileimportTriggerTitle'));

        $this->addElements(array($ci, $ci_type_change, $attribute, $relation, $project, $file_import));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
        ));
    }

}