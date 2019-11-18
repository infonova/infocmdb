<?php

/**
 * This class is used to create the login Form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Citype_Create extends Form_AbstractAppForm
{

    private $translator;

    public function __construct($translator, $ciTypeList)
    {
        parent::__construct($translator, null);
        $this->translator = $translator;
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAttrib('action', APPLICATION_URL . 'citype/create/');

        if (is_null($ciTypeList)) {
            $ciTypeList = array();
        }

        // parent Ci type ->option drop down
        $parentCiType = new Zend_Form_Element_Select('parentCiType');
        $parentCiType->setDescription($this->translator->translate('parentCiType_desc'));
        $parentCiType->setLabel('parentCiType');
        $parentCiType->addMultiOptions($ciTypeList);
        $parentCiType->setAttrib('style', 'width:200px');
        $parentCiType->setAttrib('title', $this->translator->translate('citypeParentCiTypeTitle'));
        $this->addElement($parentCiType);
    }


    /**
     * adds a CI TYPE child element to the form
     *
     * @param unknown_type $ciTypeList
     * @param unknown_type $count
     *
     * @return unknown_type
     */
    public static function getChild($ciTypeList, $count, $value)
    {
        $child = new Zend_Form_Element_Select('child_' . $count);
        $child->addMultiOptions($ciTypeList);
        $child->setAttrib('style', 'width:200px');
        if ($value)
            $child->setValue($value);

        $child->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        return $child;
    }


    /**
     * completes the form
     *
     * @return unknown_type
     */
    public function finalizeForm($ciTypeConfig, $fileUploadConfig, $projectList, $maxElements = null)
    {
        $defaultProject = new Zend_Form_Element_Select('defaultProject');
        $defaultProject->setLabel('defaultProject');
        $defaultProject->addMultiOptions($projectList);
        $defaultProject->setAttrib('style', 'width:200px');
        $defaultProject->setAttrib('title', $this->translator->translate('citypeDefaultProjectTitle'));
        $defaultProject->setDescription($this->translator->translate('defaultProject_desc'));
        $this->addElement($defaultProject);

        $defaultAttribute = new Zend_Form_Element_Text('defaultAttribute');
        $defaultAttribute->setLabel('defaultAttribute');
        $defaultAttribute->setAttrib('title', $this->translator->translate('citypeDefaultAttributeTitle'));
        $defaultAttribute->setAttrib('class', 'attributeSelect');
        $defaultAttribute->setDescription($this->translator->translate('defaultAttribute_desc'));
        $this->addElement($defaultAttribute);

        $defaultSortAttribute = new Zend_Form_Element_Text('defaultSortAttribute');
        $defaultSortAttribute->setLabel('defaultSortAttribute');
        $defaultSortAttribute->setAttrib('title', $this->translator->translate('citypeDefaultSortAttributeTitle'));
        $defaultSortAttribute->setAttrib('class', 'attributeSelect');
        $defaultSortAttribute->setDescription($this->translator->translate('defaultSortAttribute_desc'));
        $this->addElement($defaultSortAttribute);

        $isDefaultSortAsc = new Zend_Form_Element_Checkbox('isDefaultSortAsc');
        $isDefaultSortAsc->setLabel('isDefaultSortAsc');
        $isDefaultSortAsc->setAttrib('title', $this->translator->translate('isDefaultSortAscTitle'));
        $isDefaultSortAsc->setDescription($this->translator->translate('isDefaultSortAsc_desc'));
        $this->addElement($isDefaultSortAsc);


        for ($i = 0; $i < 240; $i++) {

            $addAttribute = new Zend_Form_Element_Text('addAttribute_' . $i);
            $addAttribute->setAttrib('class', 'attributeSelect');

            if ($i == 79)
                $addAttribute->setAttrib('onchange', 'visible_div1()');
            if ($i == 159)
                $addAttribute->setAttrib('onchange', 'visible_div2()');


            $this->addElement($addAttribute);

            $ismandatory = new Zend_Form_Element_Checkbox('ismandatory_' . $i);
            $ismandatory->setLabel('isMandatory');
            $ismandatory->setAttrib('title', $this->translator->translate('ismandatory'));

            $this->addElement($ismandatory);


        }

        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('name')
            ->setRequired(true)
            ->addValidator($this->createStringLengthValidator($ciTypeConfig, 'citype', 'name'), true)
            ->addValidator($this->createRegexValidator($ciTypeConfig, 'citype', 'name'), true);
        $name->addValidator(new Form_Validator_UniqueConstraintCiTypes());
        $name->setAttrib('title', $this->translator->translate('citypeNameTitle'));
        $name->setDescription($this->translator->translate('name_desc'));
        $this->addElement($name);


        // allows CI attach (yes/no)
        $allowCiAttach = new Zend_Form_Element_Checkbox('allowCiAttach');
        $allowCiAttach->setLabel('allowCiAttach');
        $allowCiAttach->setAttrib('title', $this->translator->translate('citypeAllowCiAttachTitle'));
        $allowCiAttach->setDescription($this->translator->translate('allowCiAttach_desc'));
        $this->addElement($allowCiAttach);


        // allows Attribute attach (yes/no)
        $allowAttributeAttach = new Zend_Form_Element_Checkbox('allowAttributeAttach');
        $allowAttributeAttach->setLabel('allowAttributeAttach');
        $allowAttributeAttach->setAttrib('title', $this->translator->translate('citypeAllowAttributeAttachTitle'));
        $allowAttributeAttach->setDescription($this->translator->translate('allowAttributeAttach_desc'));
        $this->addElement($allowAttributeAttach);


        // description
        $description = new Zend_Form_Element_Text('description');
        $description->setLabel('description')
            ->setAttrib('maxlength', 30)
            ->setRequired(true)
            ->addValidator($this->createStringLengthValidator($ciTypeConfig, 'citype', 'description'), true)
            ->addValidator($this->createRegexValidator($ciTypeConfig, 'citype', 'description'), true);
        $description->setAttrib('title', $this->translator->translate('citypeDescriptionTitle'));
        $description->setDescription($this->translator->translate('description_desc'));
        $this->addElement($description);


        // note
        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note')
            ->setAttrib('maxlength', 40)
            ->setRequired(true)
            ->addValidator($this->createStringLengthValidator($ciTypeConfig, 'citype', 'note'), true)
            ->addValidator($this->createRegexValidator($ciTypeConfig, 'citype', 'note'), true);
        $note->setAttrib('title', $this->translator->translate('citypeNoteTitle'));
        $note->setDescription($this->translator->translate('note_desc'));
        $this->addElement($note);


        $createButtonDescription = new Zend_Form_Element_Text('createButtonDescription');
        $createButtonDescription->setLabel('createButtonDescription')
            ->addValidator($this->createStringLengthValidator($ciTypeConfig, 'citype', 'createButtonDescription'), true)
            ->addValidator($this->createRegexValidator($ciTypeConfig, 'citype', 'createButtonDescription'), true);
        $createButtonDescription->setAttrib('title', $this->translator->translate('citypeCreateButtonDescriptionTitle'));
        $createButtonDescription->setDescription($this->translator->translate('createButtonDescription_desc'));
        $this->addElement($createButtonDescription);

        //xml tag
        $xml = new Zend_Form_Element_Text('xml');
        $xml->setLabel('citypeXMLTitle');
        $xml->setAttrib('title', $this->translator->translate('citypeXMLTitle'));
        $xml->setDescription($this->translator->translate('xml_desc'));
        $this->addElement($xml);


        $ticketEnabled = new Zend_Form_Element_Checkbox('ticketEnabled');
        $ticketEnabled->setLabel('ticketEnabled');
        $ticketEnabled->setAttrib('title', $this->translator->translate('citypeTicketEnabledTitle'));
        $ticketEnabled->setDescription($this->translator->translate('ticketEnabled_desc'));
        $this->addElement($ticketEnabled);


        $eventEnabled = new Zend_Form_Element_Checkbox('eventEnabled');
        $eventEnabled->setLabel('eventEnabled');
        $eventEnabled->setAttrib('title', $this->translator->translate('citypeEventEnabledTitle'));
        $eventEnabled->setDescription($this->translator->translate('eventEnabled_desc'));
        $this->addElement($eventEnabled);

        $query = new Zend_Form_Element_Textarea('query');
        $query->setLabel('query');
        $query->setAttrib('title', $this->translator->translate('Query'));
        $query->setDescription($this->translator->translate('query_desc'));
        $this->addElement($query);


        // add order number (sorting)
        $orderNumber = new Zend_Form_Element_Text('orderNumber');
        $orderNumber->setLabel('sorting')
            ->setRequired(true)
            ->addValidator($this->createStringLengthValidator($ciTypeConfig, 'citype', 'oderNumber'), true)
            ->addValidator($this->createRegexValidator($ciTypeConfig, 'citype', 'oderNumber'), true);
        $orderNumber->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $orderNumber->setAttrib('maxlength', 4);
        $orderNumber->setValue(0);
        $orderNumber->setAttrib('title', $this->translator->translate('citypeOrderNumberTitle'));
        $orderNumber->setDescription($this->translator->translate('orderNumber_desc'));
        $this->addElement($orderNumber);


        //listview		
        $hiddenContainer = new Zend_Form_Element_Hidden('container');
        //$hiddenContainer->setValue($containerId);
        $hiddenContainer->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $hiddenCiType = new Zend_Form_Element_Hidden('ciType');
        //$hiddenCiType->setValue($ciTypeId);
        $hiddenCiType->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $scrollable = new Zend_Form_Element_Checkbox('scrollable');
        $scrollable->setChecked(false);
        $scrollable->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $this->addElements(array($hiddenContainer, $hiddenCiType, $scrollable));

        if (!$maxElements)
            $maxElements = 20;

        for ($i = 1; $i <= $maxElements; $i++) {

            $element = new Zend_Form_Element_Text('create_' . $i);
            $element->setAttrib('class', 'attributeSelect');


            $this->addElement($element);
            $width = new Zend_Form_Element_Text('width_' . $i);
            $width->setAttrib('onKeyPress', 'return numbersonly(this, event);');
            $this->addElement($width);
        }


        //listview end 


        if ($fileUploadConfig->file->upload->icon->enabled) {
            $icon = new Zend_Form_Element_File('icon');
            $icon->setLabel('icon');
            $icon->setDescription($this->translator->translate('icon_desc'));
            $icon->setAttrib('title', $this->translator->translate('citypeIconTitle'));

            $path        = APPLICATION_PUBLIC . $fileUploadConfig->file->upload->path->folder;
            $destination = $fileUploadConfig->file->upload->icon->folder;

            $minwidth  = $fileUploadConfig->file->upload->icon->minwidth;
            $minheight = $fileUploadConfig->file->upload->icon->minheight;
            $maxwidth  = $fileUploadConfig->file->upload->icon->maxwidth;
            $maxheight = $fileUploadConfig->file->upload->icon->maxheight;

            if (!$minwidth) {
                $minwidth = 10;
            }
            if (!$minheight) {
                $minheight = 10;
            }
            if (!$maxwidth) {
                $maxwidth = 30;
            }
            if (!$maxheight) {
                $maxheight = 30;
            }

            $icon->setMaxFileSize($fileUploadConfig->file->upload->icon->maxfilesize);
            $icon->setDestination($path . $destination);
            $icon->addValidator('Extension', false, 'jpg,jpeg,png,gif');
            $icon->addValidator('ImageSize', false, array($minwidth, $minheight, $maxwidth, $maxheight));
            $this->addElement($icon);
        }


        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $this->icon->setDecorators(array(
                'File',
                'Errors',
                array(new Form_Decorator_MyDescription()),
                array(array('data' => 'HtmlTag'),
                    array('tag' => 'td', 'class' => 'element')),
                array('Label', array('tag' => 'td')),
                array(array('row' => 'HtmlTag'),
                    array('tag' => 'tr')))
        );


        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));

        for ($i = 0; $i < 240; $i++) {

            $adda = $this->getElement('addAttribute_' . $i);
            $adda->setDecorators(array(
                'ViewHelper',
                'Errors',
            ));

            $ismd = $this->getElement('ismandatory_' . $i);
            $ismd->setDecorators(array(
                'ViewHelper',
                'Errors',
            ));
        }


        for ($i = 1; $i <= $maxElements; $i++) {

            $cre = $this->getElement('create_' . $i);
            $cre->clearDecorators();
            $cre->setDecorators(array(
                'ViewHelper',
                'Errors',
                array(array('data' => 'HtmlTag')),

            ));


            $w = $this->getElement('width_' . $i);
            $w->clearDecorators();
            $w->addDecorators(array(
                'ViewHelper',
                'Errors',
            ));


        }

        $scr = $this->getElement('scrollable');
        $scr->clearDecorators();
        $scr->addDecorators(array(
            'ViewHelper',
            'Errors',
        ));

    }


    public function addAttribute($attributeId, $attributeName, $attributeDescription = "")
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

    public function addRelations($relationId, $relationName, $relationDescription)
    {
        $check = new Zend_Form_Element_Checkbox('relationId_' . $relationId);
        $check->setOptions(array('active'));
        if ($relationDescription)
            $check->setAttrib('title', $relation);
        $check->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));
        $this->addElement($check);


        $maxRelations = new Zend_Form_Element_Text('relationId_' . $relationId . '_limit');
        $maxRelations->setAttrib('maxlength', 5);
        $maxRelations->setAttrib('size', 5);
        $maxRelations->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $maxRelations->setAttrib('title', 'Keine Eingabe bedeutet unbegrenzte Anzahl');
        $maxRelations->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));
        $this->addElement($maxRelations);


        $orderNumber = new Zend_Form_Element_Text('relationId_' . $relationId . '_order');
        $orderNumber->setAttrib('maxlength', 5);
        $orderNumber->setAttrib('size', 5);
        $orderNumber->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $orderNumber->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));
        $this->addElement($orderNumber);


    }


}