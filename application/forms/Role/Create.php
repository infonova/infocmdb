<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Role_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $config)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAction(APPLICATION_URL . 'role/create');


        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setAttrib('title', $translator->translate('roleNameTitle'));
        $name->setAttrib('size', '30');
        $name->setDescription($translator->translate('name_desc'));

        if ($config->name->validators->notempty->enabled) {
            $name->setRequired(true);
            $name->autoInsertNotEmptyValidator(true);
        }

        $name->addValidator($this->createStringLengthValidator($config, 'role', 'name'), true);
        $name->addValidator($this->createRegexValidator($config, 'role', 'name'), true);

        $uniqueValidator = new Form_Validator_UniqueConstraintRoles();
        $name->addValidator($uniqueValidator);
        $this->addElement($name);


        // description
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('description');
        $description->setAttrib('title', $translator->translate('roleDescriptionTitle'));
        $description->setDescription($translator->translate('description_desc'));
        $description->setAttrib('size', '30');


        if ($config->description->validators->notempty->enabled) {
            $description->setRequired(true);
            $description->autoInsertNotEmptyValidator(true);
        }

        $description->addValidator($this->createStringLengthValidator($config, 'role', 'description'), true);
        $description->addValidator($this->createRegexValidator($config, 'role', 'description'), true);
        $this->addElement($description);


        // note
        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $note->setAttrib('title', $translator->translate('roleNoteTitle'));
        $note->setAttrib('size', '30');
        $note->setDescription($translator->translate('note_desc'));

        if ($config->note->validators->notempty->enabled) {
            $note->setRequired(true);
            $note->autoInsertNotEmptyValidator(true);
        }

        $note->addValidator($this->createStringLengthValidator($config, 'role', 'note'), true);
        $note->addValidator($this->createRegexValidator($config, 'role', 'note'), true);
        $this->addElement($note);


        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));


        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));
    }


    public function addAttribute($attributeId, $attributeName, $attributeDescription = null)
    {
        $attribute = new Zend_Form_Element_Radio('attributeId_' . $attributeId);
        $attribute->setMultiOptions(array('0' => '', '1' => '', '2' => ''));

        $attribute->setLabel($attributeName)
            ->setSeparator(' ')
            ->setValue(0);
        if ($attributeDescription)
            $attribute->setAttrib('title', $attributeDescription);

        $attribute->setDecorators(array(
            'ViewHelper',
            array('HtmlTag', array('tag' => 'div', 'class' => 'radio_group')),
        ));

        $this->addElement($attribute);
    }

    /**
     * adds an attribute to the given form
     */
    public function addUser($userId, $userName, $userDescription = "")
    {
        $user = new Zend_Form_Element_Checkbox('userId_' . $userId);
        $user->setLabel($userName);
        $user->setAttrib('title', $userDescription);
        $user->removeDecorator('Label');
        $this->addElement($user);
    }

}