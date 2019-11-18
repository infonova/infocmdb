<?php

/**
 * This class is used to update the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Project_Update extends Form_AbstractAppForm
{
    public function __construct($translator, $config)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');

        if ($config->name->validators->notempty->enabled) {
            $name->setRequired(true);
            $name->autoInsertNotEmptyValidator(true);
        }
        $name->addValidator($this->createStringLengthValidator($config, 'project', 'name'), true);
        $name->addValidator($this->createRegexValidator($config, 'project', 'name'), true);
        $name->setAttrib('title', $translator->translate('projectNameTitle'));
        $name->addValidator(new Form_Validator_UniqueConstraintProject());
        $name->setAttrib('size', '30');
        $name->setDescription($translator->translate('name_desc'));
        $this->addElement($name);


        // description
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('description');
        $description->setAttrib('title', $translator->translate('projectDescriptionTitle'));
        $description->setAttrib('size', '30');
        $description->setDescription($translator->translate('description_desc'));

        if ($config->description->validators->notempty->enabled) {
            $description->setRequired(true);
            $description->autoInsertNotEmptyValidator(true);
        }
        $description->addValidator($this->createStringLengthValidator($config, 'project', 'description'), true);
        $description->addValidator($this->createRegexValidator($config, 'project', 'description'), true);
        $this->addElement($description);


        // note
        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $note->setAttrib('title', $translator->translate('projectNoteTitle'));
        $note->setAttrib('size', '30');
        $note->setDescription($translator->translate('note_desc'));


        if ($config->note->validators->notempty->enabled) {
            $note->setRequired(true);
            $note->autoInsertNotEmptyValidator(true);
        }
        $note->addValidator($this->createStringLengthValidator($config, 'project', 'note'), true);
        $note->addValidator($this->createRegexValidator($config, 'project', 'note'), true);
        $this->addElement($note);


        $orderNumber = new Zend_Form_Element_Text('order');
        $orderNumber->setLabel('order');
        $orderNumber->setAttrib('title', $translator->translate('projectOrderNumberTitle'));
        $orderNumber->setAttrib('size', '30');
        $orderNumber->setDescription($translator->translate('order_desc'));

        if ($config->order->validators->notempty->enabled) {
            $orderNumber->setRequired(true);
            $orderNumber->autoInsertNotEmptyValidator(true);
        }
        $orderNumber->setValue(0);
        $this->addElement($orderNumber);


        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'width' => '35%')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));


        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));
    }

    /**
     * adds an attribute to the given form
     */
    public function addUser($userId, $userName, $userDescription = "")
    {
        $user = new Zend_Form_Element_Checkbox($userId);
        $user->setLabel($userName);
        $user->setAttrib('title', $userDescription);
        $user->removeDecorator('Label');
        $this->addElement($user);
    }

}