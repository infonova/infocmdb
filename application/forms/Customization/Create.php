<?php

/**
 * This class is used to create the reporting input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Customization_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $userlist, $customizationId = null, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CreateForm');

        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setRequired(true);
        $name->setAutoInsertNotEmptyValidator(true);
        if (!$customizationId)
            $name->addValidator(new Zend_Validate_Db_NoRecordExists('customization', 'name'));
        $name->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'name-row'))));

        $note = new Zend_Form_Element_Textarea('note');
        $note->setLabel('note');
        $note->setRequired(true);
        $note->setAttrib('rows', 14);
        $note->setAutoInsertNotEmptyValidator(true);
        $note->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'description-row'))));

        // add script 
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
        $link->setAttrib('href', APPLICATION_URL . 'fileupload/index/filetype/customization/genId/0');
        $link->setAttrib('toptions', "effect = clip, layout = flatlook");
        $link->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'script-row'))));

        $user = new Zend_Form_Element_Select('user');
        $user->setLabel('user');
        $user->setRequired(true);
        $user->addMultiOptions($userlist);
        $user->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'user-row'))));

        $trigger = new Zend_Form_Element_Radio('trigger');
        $trigger->setLabel('trigger');
        $trigger->setRequired(true);
        $trigger->setMultiOptions(array('activity' => 'actionTriggered', 'time' => 'timeTriggered'));
        $trigger->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'trigger-row'))));

        $this->addElements(array($name, $note, $script, $fileName, $link, $addReceiver, $active, $submit, $user));

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');
        $submit->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array(array('row' => 'HtmlTag'), array('tag' => 'tr'))));;

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width:80%;')),
            array('Label', array('tag' => 'td')),
        ));

        $this->addElements(array($trigger, $submit));
    }

}