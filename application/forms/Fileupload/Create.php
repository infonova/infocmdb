<?php

class Form_Fileupload_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $fileType, $attributeId, $filePath, $folder, $maxfilesize, $options = null)
    {
        parent::__construct($translator, $options);

        $this->setName('fileUpload');
        $this->setAttrib('enctype', 'multipart/form-data');

        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('filename');


        $doc_file = new Zend_Form_Element_File('filePath');
        $doc_file->setMaxFileSize($maxfilesize);

        // check if requested path is available
        if (!is_dir($filePath . $folder)) {
            @mkdir($filePath . $folder, 0777);
            chmod($filePath . $folder, 0777);
        }

        $dir = $filePath . $folder;
        //chmod($dir, 0777);

        $doc_file->setDestination($dir);
        $doc_file->setLabel('filepath')
            ->setRequired(true);

        $hiddenAttributeNr = new Zend_Form_Element_Hidden('attributeNr');
        $hiddenAttributeNr->setValue($attributeId);

        $arrayVal                = array();
        $arrayVal['attributeNr'] = $attributeId;
        $this->populate($arrayVal);

        // creating object for submit button
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('submit');
        $submit->setAttrib('class', 'standard_button');
        $submit->setRequired(true);


        // adding elements to form Object

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));


        $description->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $hiddenAttributeNr->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $doc_file->setDecorators(array(
            'Errors',
            'File',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));


        $submit->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));


        $this->addElements(array($description, $doc_file, $hiddenAttributeNr, $submit));
    }

}