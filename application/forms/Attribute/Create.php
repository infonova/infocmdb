<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $attributeTypeList, $attributeValueList, $attributeGroupList, $attributeGroupConfig, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        if (is_null($attributeTypeList)) {
            $attributeTypeList = array();
        }
        if (is_null($attributeValueList)) {
            $attributeValueList = array();
        }
        if (is_null($attributeGroupList)) {
            $attributeGroupList = array();
        }

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name');
        $name->setDescription($translator->translate('name_desc'));
        $name->setAttrib('title', $translator->translate('attributeNameTitle'));
        $name->addValidator(new Form_Validator_UniqueConstraintAttributes());

        if ($attributeGroupConfig->attribute->name->validators->notempty->enabled) {
            $name->setRequired(true);
            $name->addValidator($this->createNotEmptyValidator(), true);
        }
        if ($attributeGroupConfig->attribute->name->validators->strlen->enabled) {
            $name->setAttrib('maxlength', $attributeGroupConfig->attribute->name->validators->strlen->options->max);
            $name->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attribute', 'name'), true);
        }
        if ($attributeGroupConfig->attribute->name->validators->strlen->options->size) {
            $name->setAttrib('size', $attributeGroupConfig->attribute->name->validators->strlen->options->size);
        }
        if ($attributeGroupConfig->attribute->name->validators->regex->enabled) {
            $name->addValidator($this->createRegexValidator($attributeGroupConfig, 'attribute', 'name'), true);
        }
        $this->addElement($name);


        // description
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('description');
        $description->setDescription($translator->translate('description_desc'));
        $description->setAttrib('title', $translator->translate('attributeDescriptionTitle'));

        if ($attributeGroupConfig->attribute->description->validators->notempty->enabled) {
            $description->setRequired(true);
            $description->addValidator($this->createNotEmptyValidator(), true);
        }
        if ($attributeGroupConfig->attribute->description->validators->strlen->enabled) {
            $description->setAttrib('maxlength', $attributeGroupConfig->attribute->description->validators->strlen->options->max);
            $description->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attribute', 'description'), true);
        }
        if ($attributeGroupConfig->attribute->description->validators->strlen->options->size) {
            $description->setAttrib('size', $attributeGroupConfig->attribute->description->validators->strlen->options->size);
        }
        if ($attributeGroupConfig->attribute->description->validators->regex->enabled) {
            $description->addValidator($this->createRegexValidator($attributeGroupConfig, 'attribute', 'description'), true);
        }
        $this->addElement($description);


        // note
        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $note->setDescription($translator->translate('note_desc'));
        $note->setAttrib('title', $translator->translate('attributeNoteTitle'));

        if ($attributeGroupConfig->attribute->note->validators->notempty->enabled) {
            $note->setRequired(true);
            $note->addValidator($this->createNotEmptyValidator(), true);
        }
        if ($attributeGroupConfig->attribute->note->validators->strlen->enabled) {
            $note->setAttrib('maxlength', $attributeGroupConfig->attribute->note->validators->strlen->options->max);
            $note->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attribute', 'note'), true);
        }
        if ($attributeGroupConfig->attribute->note->validators->strlen->options->size) {
            $note->setAttrib('size', $attributeGroupConfig->attribute->note->validators->strlen->options->size);
        }
        if ($attributeGroupConfig->attribute->note->validators->regex->enabled) {
            $note->addValidator($this->createRegexValidator($attributeGroupConfig, 'attribute', 'note'), true);
        }
        $this->addElement($note);


        // attribute types
        $attributeType = new Zend_Form_Element_Select('attributeType');
        $attributeType->setLabel('attributeType');
        $attributeType->addMultiOptions($attributeTypeList);
        $attributeType->setValue(Util_AttributeType_Type_Input::ATTRIBUTE_TYPE_ID);
        $attributeType->setRequired(true);
        $attributeType->setAttrib('title', $translator->translate('attributeAttributeTypeTitle'));
        $this->addElement($attributeType);


        // -> view types
        $displayType = new Zend_Form_Element_Select('displayType');
        $displayType->setLabel('displayType');
        $displayType->addMultiOptions($attributeGroupList);
        $displayType->setRequired(true);
        $displayType->autoInsertNotEmptyValidator(true);
        $displayType->setAttrib('title', $translator->translate('attributedisplayTypeTitle'));
        $displayType->setDescription($translator->translate('attributgroup_desc'));
        $this->addElement($displayType);


        // order number
        $sorting = new Zend_Form_Element_Text('sorting');
        $sorting->setLabel('sorting');
        $sorting->setValue(255);
        $sorting->setAttrib('maxlength', 4);
        $sorting->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $sorting->setRequired(true);
        $sorting->autoInsertNotEmptyValidator(true);
        $sorting->setAttrib('title', $translator->translate('attributeSortingTitle'));
        $sorting->setDescription($translator->translate('sorting_desc'));

        if ($attributeGroupConfig->attribute->sorting->validators->notempty->enabled) {
            $sorting->setRequired(true);
            $sorting->addValidator($this->createNotEmptyValidator(), true);
        }

        if ($attributeGroupConfig->attribute->sorting->validators->strlen->enabled) {
            $sorting->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attribute', 'sorting'), true);
        }
        if ($attributeGroupConfig->attribute->sorting->validators->regex->enabled) {
            $note->addValidator($this->createRegexValidator($attributeGroupConfig, 'attribute', 'sorting'), true);
        }
        $this->addElement($sorting);


        // hint
        $hint = new Zend_Form_Element_Textarea('hint');
        $hint->setLabel('hint');
        $hint->setAttrib('title', $translator->translate('attributeHintTitle'));
        $hint->setAttrib('class', 'tinymce');
        $hint->setDescription($translator->translate('hint_desc'));

        if ($attributeGroupConfig->attribute->hint->validators->notempty->enabled) {
            $hint->setRequired(true);
            $hint->addValidator($this->createNotEmptyValidator(), true);
        }
        if ($attributeGroupConfig->attribute->hint->validators->strlen->enabled) {
            $hint->setAttrib('maxlength', $attributeGroupConfig->attribute->hint->validators->strlen->options->max);
            $hint->addValidator($this->createStringLengthValidator($attributeGroupConfig, 'attribute', 'hint'), true);
        }
        if ($attributeGroupConfig->attribute->hint->validators->regex->enabled) {
            $hint->addValidator($this->createRegexValidator($attributeGroupConfig, 'attribute', 'hint'), true);
        }
        $this->addElement($hint);


        // Column
        $column = new Zend_Form_Element_Select('column');
        $column->setLabel('column');
        $column->setAttrib('title', $translator->translate('attributeColumnTitle'));
        $column->addMultiOptions(array(1 => '1', 2 => '2'));
        $column->setDescription($translator->translate('column_desc'));
        $this->addElement($column);


        // highlight attribute (yes/no)
        $highlightAttribute = new Zend_Form_Element_Checkbox('highlightAttribute');
        $highlightAttribute->setLabel('highlightAttribute');
        $highlightAttribute->setAttrib('title', $translator->translate('attributeHighlightAttributeTitle'));
        $highlightAttribute->setDescription($translator->translate('highlight_desc'));
        $this->addElement($highlightAttribute);

        // xml tag
        $xml = new Zend_Form_Element_Text('xml');
        $xml->setLabel('createXML');
        $xml->setAttrib('title', $translator->translate('attributeXMLTitle'));
        $xml->setDescription($translator->translate('xml_desc'));
        $this->addElement($xml);


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

    public function addCiType($citypeId, $citypeName, $citypeDescription = null, $ciTypeActive = null)
    {
        $citype = new Zend_Form_Element_Radio('citypeId_' . $citypeId);
        $citype->setMultiOptions(array('0' => '', '1' => '', '2' => ''));

        $citype->setLabel($citypeName)
            ->setSeparator(' ')
            ->setValue(0);
        if ($citypeDescription)
            $citype->setAttrib('title', $citypeDescription);

        if ($ciTypeActive == '0') {
            $citype->setAttrib('disabled', 'disabled');
        }

        $citype->setDecorators(array(
            'ViewHelper',
            array('HtmlTag', array('tag' => 'div', 'class' => 'radio_group')),
        ));

        $this->addElement($citype);
    }

    public function addRole($roleId, $roleName, $roleDescription = null, $roleActive = null)
    {
        $role = new Zend_Form_Element_Radio('roleId_' . $roleId);
        $role->setMultiOptions(array('0' => '', '1' => '', '2' => ''));

        $role->setLabel($roleName)
            ->setSeparator(' ')
            ->setValue(0);
        if ($roleDescription)
            $role->setAttrib('title', $roleDescription);

        if ($roleActive == '0') {
            $role->setAttrib('disabled', 'disabled');
        }

        $role->setDecorators(array(
            'ViewHelper',
            array('HtmlTag', array('tag' => 'div', 'class' => 'radio_group')),
        ));

        $this->addElement($role);
    }

}