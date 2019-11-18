<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Fileimport_Wizard_Upload extends Zend_Form_SubForm
{
    public function __construct($translator, $filePath, $options = null)
    {

        parent::__construct($translator, $options);
        $this->setName('CreateForm');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAction(APPLICATION_URL . 'fileimport/index/upload/1');


        $upload = new Zend_Form_Element_File('upload');
        $upload->setLabel('webFormUpload');
        $upload->setDestination($filePath);
        $upload->setRequired(true);
        $upload->setAutoInsertNotEmptyValidator(true);
        //$upload->addValidator(new Form_Validator_FileimportType());
        $upload->addValidator('Extension', false, 'csv');
        //$upload->setAttrib('id', 'T8A_F4');
        //$upload->setAttrib('class', 'multi');
        $upload->setAttrib('title', $translator->translate('fileimportUploadTitle'));
        $upload->setDecorators(array(
            'Errors',
            'File',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('next');
        $submit->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));


        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));

        $this->addElements(array(
            $upload,
            //$submit
        ));

    }
}