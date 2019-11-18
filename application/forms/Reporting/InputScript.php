<?php

class Form_Reporting_InputScript extends Form_AbstractAppForm
{

    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CreateForm');


        // to create a file
        $nameContent = new Zend_Form_Element_Text('name');
        $nameContent->setLabel('name');
        $nameContent->setAttrib('title', $translator->translate('reportingNameContentTitle'));

        $fileContent = new Zend_Form_Element_Textarea('content');
        $fileContent->setLabel('content');
        $fileContent->setAttrib('rows', 14);
        $fileContent->setAutoInsertNotEmptyValidator(true);
        $fileContent->setAttrib('title', $translator->translate('reportingFileContentTitle'));

        $this->addElements(array($nameContent, $fileContent));

        // to upload a file
        $description = new Zend_Form_Element_Text('scriptdescription');
        $description->setLabel('filename');
        $description->setAttrib('readonly', true);
        $description->setRequired(true);
        $description->setAutoInsertNotEmptyValidator(true);
        $description->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'scriptdescription-row'))));
        $description->setAttrib('title', $translator->translate('reportingDescriptionTitle'));

        $fileName = new Zend_Form_Element_Text('scriptfilename');
        $fileName->setLabel('filepath');
        $fileName->setAttrib('readonly', true);
        $fileName->setRequired(true);
        $fileName->setAutoInsertNotEmptyValidator(true);
        $fileName->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'scriptfilename-row'))));
        $fileName->setAttrib('title', $translator->translate('reportingFileNameTitle'));

        $link = new Zend_Form_Element_Image('script');
        $link->setImage(APPLICATION_URL . 'images/icon/upload.png');
        $link->setLabel('scriptUpload');
        $link->setAttrib('title', $translator->translate("upload"));
        $link->setAttrib('class', "tu_iframe_500x120");
        $link->setAttrib('tabindex', '-1');
        $link->setAttrib('href', APPLICATION_URL . 'fileupload/index/filetype/reporting/attributeId/script/genId/0');
        $link->setAttrib('toptions', "effect = clip, layout = flatlook");
        $link->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'script-row'))));
        $link->setAttrib('title', $translator->translate('reportingLinkTitle'));


        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width:80%;')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));


        $this->addElements(array($description, $fileName, $link));
    }
}