<?php

/**
 * This class is used to create the login Form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attributegroup_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $attributeGroupList, $attributeGroupConfig)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAction(APPLICATION_URL . 'attributegroup/create/');

        if (is_null($attributeGroupList)) {
            $attributeGroupList = array();
        }

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name')
            ->setRequired(true)
            ->addValidator($this->createNotEmptyValidator(), true)
            ->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attributegroup', 'name'), true)
            ->addValidator($this->createRegexValidator($attributeGroupConfig, 'attributegroup', 'name'), true);
        $name->setAttrib('maxlength', $attributeGroupConfig->name->prop->maxlength);
        $name->setAttrib('title', $translator->translate('attributeGroupNameTitle'));
        $name->setAttrib('size', '40');


        $is_duplicate_allow = new Zend_Form_Element_Checkbox('duplicate_allow');
        $is_duplicate_allow->setLabel('duplicate_allow');
        $is_duplicate_allow->setDescription($translator->translate('duplicate_allow_desc'));

        $uniqueValidator = new Form_Validator_UniqueConstraintAttributeGroups();
        $name->addValidator($uniqueValidator);
        $name->setDescription($translator->translate('name_desc'));
        // description
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('description')
            ->setRequired(true)
            ->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attributegroup', 'description'), true)
            ->addValidator($this->createRegexValidator($attributeGroupConfig, 'attributegroup', 'description'), true);
        $description->setAttrib('maxlength', $attributeGroupConfig->description->prop->maxlength);
        $description->setAttrib('title', $translator->translate('attributeGroupDescriptionTitle'));
        $description->setDescription($translator->translate('description_desc'));
        $description->setAttrib('size', '40');

        // parent view type ->option drop down
        $parentAttributeGroup = new Zend_Form_Element_Select('parentAttributeGroup');
        $parentAttributeGroup->setLabel('attributegroupParent');
        $parentAttributeGroup->addMultiOptions($attributeGroupList);
        $parentAttributeGroup->setAttrib('title', $translator->translate('attributeGroupParentTitle'));
        $parentAttributeGroup->setDescription($translator->translate('parentAttributeGroup_desc'));
        $parentAttributeGroup->setAttrib('style', 'width:260px');
        // note
        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note')
            ->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attributegroup', 'note'), true)
            ->addValidator($this->createRegexValidator($attributeGroupConfig, 'attributegroup', 'note'), true);
        $note->setAttrib('maxlength', $attributeGroupConfig->note->prop->maxlength);
        $note->setAttrib('title', $translator->translate('attributeGroupNoteTitle'));
        $note->setDescription($translator->translate('note_desc'));
        $note->setAttrib('size', '40');

        $sorting = new Zend_Form_Element_Text('sorting');
        $sorting->setLabel('sorting')
            ->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attributegroup', 'sorting'), true);
        //->addValidator($this->createRegexValidator($attributeGroupConfig, 'attributegroup', 'sorting'), true);
        $sorting->setAttrib('maxlength', $attributeGroupConfig->sorting->prop->maxlength);
        $sorting->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $sorting->setAttrib('title', $translator->translate('attributeGroupSortingTitle'));
        $sorting->setDescription($translator->translate('sorting_desc'));
        $sorting->setAttrib('size', '40');

        $submit = new Zend_Form_Element_Submit('create');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');

        $this->addElements(array($name, $parentAttributeGroup, $description,
            $note, $sorting, $is_duplicate_allow, $submit));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $submit->clearDecorators();
        $submit->addDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table')),
            'Form',
        ));

        $this->getElement('create')->removeDecorator('Label');
    }
}