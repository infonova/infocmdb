<?php

class Form_Theme_Update extends Form_AbstractAppForm
{
    public function __construct($translator, $config, $themeId)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAction(APPLICATION_URL . 'theme/edit/themeId/' . $themeId);


        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setAttrib('title', $translator->translate('themeNameTitle'));
        $name->setAttrib('size', '30');
        $name->setDescription($translator->translate('name_desc'));

        if ($config->name->validators->notempty->enabled) {
            $name->setRequired(true);
            $name->autoInsertNotEmptyValidator(true);
        }

        $name->addValidator($this->createStringLengthValidator($config, 'theme', 'name'), true);
        $name->addValidator($this->createRegexValidator($config, 'theme', 'name'), true);
        $name->addValidator(new Form_Validator_UniqueConstraintTheme());


        $valid = new Zend_Form_Element_Checkbox('valid');
        $valid->setLabel('valid');
        $valid->setDescription($translator->translate('description_desc'));


        // description
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('description');
        $description->setAttrib('title', $translator->translate('themeDescriptionTitle'));
        $description->setAttrib('size', '30');
        $description->setDescription($this->_translator->translate('description_desc'));


        if ($config->description->validators->notempty->enabled) {
            $description->setRequired(true);
            $description->autoInsertNotEmptyValidator(true);
        }

        $description->addValidator($this->createStringLengthValidator($config, 'theme', 'description'), true);
        $description->addValidator($this->createRegexValidator($config, 'theme', 'description'), true);


        // note
        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $note->setAttrib('title', $translator->translate('themeNoteTitle'));
        $note->setAttrib('size', '30');
        $note->setDescription($translator->translate('note_desc'));

        if ($config->note->validators->notempty->enabled) {
            $note->setRequired(true);
            $note->autoInsertNotEmptyValidator(true);
        }

        $note->addValidator($this->createStringLengthValidator($config, 'theme', 'note'), true);
        $note->addValidator($this->createRegexValidator($config, 'theme', 'note'), true);


        $this->addElements(array($name, $valid, $description, $note));
    }


    public function addStartPage($selection)
    {
        $child = new Zend_Form_Element_Select('startpage');
        $child->addMultiOptions($selection);
        $child->setLabel('homepage');
        $child->setAttrib('title', $this->_translator->translate('themeStartpageTitle'));
        $child->setAttrib('style', 'width:200px');
        $child->setDescription($this->_translator->translate('startpage_desc'));
        $this->addElement($child);
    }


    public function addMenu($menuId, $menuName, $menuDescription)
    {
        $child = new Zend_Form_Element_Checkbox($menuId);
        $child->setLabel($menuDescription);
        $this->addElement($child);
    }


    public function addSubmitButton()
    {
        $submit = new Zend_Form_Element_Submit('create');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');

        $this->addElement($submit);

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));


        $this->getElement('create')->removeDecorator('Label');

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));
    }
}