<?php

class Form_Announcement_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $config, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAction(APPLICATION_URL . 'announcement/create');

        // name - intern description
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setAttrib('title', $translator->translate('name'));
        $name->setAttrib('size', '30');
        $name->setRequired(true);
        $name->setAutoInsertNotEmptyValidator(true);

        // type
        $type = new Zend_Form_Element_Select('type');
        $type->addMultiOption('information', 'information');
        $type->addMultiOption('question', 'question');
        $type->addMultiOption('agreement', 'agreement');
        $type->setLabel('type');
        $type->setRequired(true);
        $type->setAttrib('title', $translator->translate('type'));
        $type->setAutoInsertNotEmptyValidator(true);

        // german title
        $title_de = new Zend_Form_Element_Text('title_de');
        $title_de->setLabel('titleDe');
        $title_de->setAttrib('title', $translator->translate('titleDe'));
        $title_de->setAttrib('size', '30');

        // german message
        $message_de = new Zend_Form_Element_Textarea('message_de');
        $message_de->setLabel('messageDe');
        $message_de->setAttrib('title', $translator->translate('messageDe'));
        $message_de->setAttrib('class', 'tinymce');

        // english title
        $title_en = new Zend_Form_Element_Text('title_en');
        $title_en->setLabel('titleEn');
        $title_en->setAttrib('title_en', $translator->translate('titleEn'));
        $title_en->setAttrib('size', '30');

        // englisch message
        $message_en = new Zend_Form_Element_Textarea('message_en');
        $message_en->setLabel('messageEn');
        $message_en->setAttrib('title', $translator->translate('messageEn'));
        $message_en->setAttrib('class', 'tinymce');

        // show from date
        $show_from_date = new Zend_Form_Element_Text('show_from_date');
        $show_from_date->setLabel('showFromDate');
        $show_from_date->setAttrib('class', 'datetime-picker');
        $show_from_date->setAttrib('data-enabletime', 'true');
        $show_from_date->setRequired(true);
        $show_from_date->setAutoInsertNotEmptyValidator(true);
        $show_from_date->addValidator(($validator = new Zend_Validate_Date(array('format' => 'Y-m-d H:i:s'))));

        // show to date
        $show_to_date = new Zend_Form_Element_Text('show_to_date');
        $show_to_date->setLabel('showToDate');
        $show_to_date->setAttrib('class', 'datetime-picker');
        $show_to_date->setAttrib('data-enabletime', 'true');
        $show_to_date->setRequired(true);
        $show_to_date->setAutoInsertNotEmptyValidator(true);
        $show_to_date->addValidator(($validator = new Zend_Validate_Date(array('format' => 'Y-m-d H:i:s'))));

        // valid
        $valid = new Zend_Form_Element_Checkbox('valid');
        $valid->setLabel('valid');

        // submit button
        $submit = new Zend_Form_Element_Submit('create');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');

        $this->addElements(array($name, $type, $title_de, $message_de, $title_en, $message_en, $show_from_date, $show_to_date, $valid, $submit));

        // Decorators
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