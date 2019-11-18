<?php

class Form_Fileupload_Edit extends Form_AbstractAppForm
{
    public function __construct($translator, $isEditor = false, $options = null)
    {
        parent::__construct($translator, $options);

        $this->setName('editScript');
        $this->setAttrib('enctype', 'multipart/form-data');

        $description = new Zend_Form_Element_Textarea('file');
        $description->setLabel('file');

        if ($isEditor)
            $description->setAttrib('class', 'tinymc_textarea');

        // creating object for submit button
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('submit');
        $submit->setAttrib('class', 'standard_button');
        $submit->setRequired(true);


        $this->addElements(array($description, $submit));
    }

}