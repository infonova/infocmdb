<?php

class Form_Relationtype_Update extends Form_AbstractAppForm
{
    public function __construct($translator, $createRelationConfig)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setAttrib('size', '40');
        $name->addValidator(new Form_Validator_UniqueConstraintRelationTypes());
        $name->setDescription($translator->translate('name_desc'));
        if ($createRelationConfig->name->validators->notempty->enabled) {
            $name->setRequired(true);
            $name->setAutoInsertNotEmptyValidator(true);
        }
        if ($createRelationConfig->name->validators->strlen->enabled) {
            $name->addValidator($this->createStringLengthValidator($createRelationConfig, 'relation', 'name'), true);
        }
        if ($createRelationConfig->name->validators->regex->enabled) {
            $name->addValidator($this->createRegexValidator($createRelationConfig, 'relation', 'name'), true);
        }
        $name->setAttrib('title', $translator->translate('relationtypeNameTitle'));
        $this->addElement($name);


        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('description');
        $description->setAttrib('size', '40');

        if ($createRelationConfig->description->validators->notempty->enabled) {
            $description->setRequired(true);
            $description->setAutoInsertNotEmptyValidator(true);
        }
        if ($createRelationConfig->description->validators->strlen->enabled) {
            $description->addValidator($this->createStringLengthValidator($createRelationConfig, 'relation', 'description'), true);
        }
        if ($createRelationConfig->description->validators->regex->enabled) {
            $description->addValidator($this->createRegexValidator($createRelationConfig, 'relation', 'description'), true);
        }
        $description->setAttrib('title', $translator->translate('relationtypeDescriptionTitle'));
        $description->setDescription($translator->translate('description_desc'));
        $this->addElement($description);


        $description2 = new Zend_Form_Element_Text('description2');
        $description2->setLabel('description2');
        $description2->setAttrib('size', '40');

        if ($createRelationConfig->description2->validators->notempty->enabled) {
            $description2->setRequired(true);
            $description2->setAutoInsertNotEmptyValidator(true);
        }
        if ($createRelationConfig->description2->validators->strlen->enabled) {
            $description2->addValidator($this->createStringLengthValidator($createRelationConfig, 'relation', 'description2'), true);
        }
        if ($createRelationConfig->description2->validators->regex->enabled) {
            $description2->addValidator($this->createRegexValidator($createRelationConfig, 'relation', 'description2'), true);
        }
        $description2->setAttrib('title', $translator->translate('relationtypeDescriptionOptionalTitle'));
        $description2->setDescription($translator->translate('description2_desc'));
        $this->addElement($description2);


        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $note->setAttrib('size', '40');

        if ($createRelationConfig->note->validators->notempty->enabled) {
            $note->setRequired(true);
            $note->setAutoInsertNotEmptyValidator(true);
        }
        if ($createRelationConfig->note->validators->strlen->enabled) {
            $note->addValidator($this->createStringLengthValidator($createRelationConfig, 'relation', 'note'), true);
        }
        if ($createRelationConfig->note->validators->regex->enabled) {
            $note->addValidator($this->createRegexValidator($createRelationConfig, 'relation', 'note'), true);
        }
        $note->setAttrib('title', $translator->translate('relationtypeNoteTitle'));
        $note->setDescription($translator->translate('note_desc'));
        $this->addElement($note);


        $color = new Zend_Form_Element_Text('color');
        $color->setLabel('color');

        if ($createRelationConfig->color->validators->notempty->enabled) {
            $color->setRequired(true);
            $color->setAutoInsertNotEmptyValidator(true);
        }
        if ($createRelationConfig->color->validators->strlen->enabled) {
            $color->addValidator($this->createStringLengthValidator($createRelationConfig, 'relation', 'color'), true);
        }
        if ($createRelationConfig->color->validators->regex->enabled) {
            $color->addValidator($this->createRegexValidator($createRelationConfig, 'relation', 'color'), true);
        }
        $color->setAttrib('title', $translator->translate('relationtypeColorTitle'));
        $color->setDescription($translator->translate('color_desc'));
        $this->addElement($color);


        $visualize = new Zend_Form_Element_Checkbox('visualize');
        $visualize->setLabel('visualizeCiType');
        $visualize->setOptions(array('visualize'));
        $visualize->setChecked(true);
        $visualize->setAttrib('title', $translator->translate('relationtypeVisualizeTitle'));
        $visualize->setDescription($translator->translate('visualize_desc'));
        $this->addElement($visualize);

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width:20%;')),
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
    public function addCiType($citypeId, $citypeName)
    {
        $citype = new Zend_Form_Element_Checkbox($citypeId);
        $citype->setLabel($citypeName);
        $citype->removeDecorator('Label');
        $this->addElement($citype);
    }
}