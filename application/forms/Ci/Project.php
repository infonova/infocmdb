<?php

class Form_Ci_Project extends Form_AbstractAppForm
{
    public function __construct($translator, $projectList, $ciProjectList, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('project');
        $this->setAttrib('enctype', 'multipart/form-data');

        // highlight attribute (yes/no)
        $highlightAttribute = new Zend_Form_Element_Checkbox('highlightAttribute');
        $highlightAttribute->setLabel('highlightAttribute');


        $submit = new Zend_Form_Element_Submit('project');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');

        $amount = count($projectList);


        foreach ($projectList as $project) {
            $tmp = new Zend_Form_Element_Checkbox($project[Db_Project::ID]);
            $tmp->removeDecorator('label');

            foreach ($ciProjectList as $ciProject) {
                if ($ciProject[Db_Project::ID] == $project[Db_Project::ID]) {
                    $tmp->setChecked(true);
                    break;
                }
            }

            $this->addElement($tmp);
        }


        $this->addElement($submit);
    }

}