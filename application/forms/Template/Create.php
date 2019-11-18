<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Template_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CreateForm');
        $this->setAttrib('enctype', 'multipart/form-data');

        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setAttrib('size', 30);
        $name->setRequired(true);
        $name->setAutoInsertNotEmptyValidator(true);

        $description = new Zend_Form_Element_Text('descr');
        $description->setLabel('description');
        $description->setAttrib('size', 30);
        $description->setRequired(true);
        $description->setAutoInsertNotEmptyValidator(true);

        $note = new Zend_Form_Element_Textarea('note');
        $note->setLabel('note');
        $note->setAttrib('cols', 30);
        $note->setAttrib('rows', 5);


        $script = new Zend_Form_Element_Hidden('description');
        $script->setLabel('scriptDescription');

        $fileName = new Zend_Form_Element_Hidden('filename');
        $fileName->setLabel('fileName');

        $link = new Zend_Form_Element_Image('script');
        $link->setImage(APPLICATION_URL . 'images/icon/upload.png');
        $link->setLabel('file');
        $link->setAttrib('title', $translator->translate("upload"));
        $link->setAttrib('class', "tu_iframe_500x120");
        $link->setAttrib('tabindex', '-1');
        $link->setAttrib('href', APPLICATION_URL . 'fileupload/index/filetype/template/genId/0');
        $link->setAttrib('toptions', "effect = clip, layout = flatlook");
        $link->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'script-row'))));


        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');
        $submit->addDecorators(array(
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width:20%;')),
        ));

        $this->addElements(array(
            $name,
            $description,
            $note,
            $script,
            $fileName,
            $link,
        ));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width:80%;')),
            array('Label', array('tag' => 'td')),
        ));

        $this->addElement($submit);
    }

}