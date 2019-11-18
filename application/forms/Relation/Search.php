<?php

/**
 * This class is used to create the relation filter
 *
 */
class Form_Relation_Search extends Form_AbstractAppForm
{
    public function __construct($translator, $ciId, $relationTypeList, $directionList, $attributeList_1 = array(), $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAction(APPLICATION_URL . 'relation/create/ciid/' . $ciId);

        $attributes1 = new Zend_Form_Element_Select('attributes1');
        $attributes1->addMultiOptions(array(0 => 'bitte wählen'));
        $attributes1->addMultiOptions($attributeList_1);
        $attributes1->setLabel('attribute');
        $attributes1->setAttrib('class', 'relation_attribute_select');
        if (!$attributeList_1) {
            $attributes1->setAttrib('disabled', true);
        }
        $this->addElement($attributes1);

        $relationSwitch = new Zend_Form_Element_Checkbox('switch');
        $relationSwitch->setLabel('switchRelation');
        $this->addElement($relationSwitch);

        $relationList = new Zend_Form_Element_Select('relation');
        $relationList->addMultiOptions(array(0 => 'bitte wählen'));
        $relationList->addMultiOptions($relationTypeList);
        $relationList->setLabel('relation');
        $relationList->setRequired(true);
        $relationList->setAutoInsertNotEmptyValidator(true);
        $this->addElement($relationList);

        $pageField = new Zend_Form_Element_Hidden('page');
        $this->addElement($pageField);

        $sessionField = new Zend_Form_Element_Hidden('session');
        $this->addElement($sessionField);

        $directions = new Zend_Form_Element_Select('direction');
        $directions->setMultiOptions($directionList);
        $directions->setValue(Db_CiRelationDirection::getDefaultDirection());
        $directions->setLabel('direction');
        $this->addElement($directions);

        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('note');
        $this->addElement($note);

        $color = new Zend_Form_Element_Text('color');
        $color->setLabel('color');
        $color->setAttrib('readonly', 'readonly');
        $color->setValue('000');
        $color->addValidator(new Zend_Validate_Regex('/[0-9a-fA-F]+/'));
        $this->addElement($color);

        $weightList = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $weight     = new Zend_Form_Element_Select('weight');
        $weight->setMultiOptions($weightList);
        $weight->setLabel('weight');
        $this->addElement($weight);

        $searchField = new Zend_Form_Element_Text('searchstring');
        $searchField->setLabel('searchstring');
        $searchField->setValue('*');
        $this->addElement($searchField);


        $searchButton = new Zend_Form_Element_Submit('searchButton');
        $searchButton->setLabel('search');

        $searchButton->setRequired(true);

        $this->addElement($searchButton);
        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
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
}