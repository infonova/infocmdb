<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_IndividualCitype extends Zend_Form_SubForm
{
    public function __construct($translator, $ciTypes, $options = null, $isPersistent = false)
    {
        parent::__construct($translator, $options);
        $this->setName('citype');
        $this->setAttrib('enctype', 'multipart/form-data');


        //width
        $textfieldWidth = new Zend_Form_Element_Text('textfieldWidth');
        $textfieldWidth->setLabel('textfieldWidth');
        $textfieldWidth->setAttrib('maxlength', 4);
        $textfieldWidth->setValue(180);
        $textfieldWidth->setAttrib('onKeyPress', 'return numbersonly(this, event);');
        $textfieldWidth->setAttrib('title', $translator->translate('attributeTextfieldWidthTitle'));
        $this->addElement($textfieldWidth);


        // citype autocomplete
        $ciTypeAutocomplete = new Zend_Form_Element_Checkbox('ciTypeAutocomplete');
        $ciTypeAutocomplete->setLabel('ciTypeAutocomplete');
        $ciTypeAutocomplete->setAttrib('title', $translator->translate('attributeCiTypeAutocompleteTitle'));
        $this->addElement($ciTypeAutocomplete);


        if ($isPersistent === false) {
            // citype project restriction
            $ciTypeProjectRestriction = new Zend_Form_Element_Checkbox('ProjectRestriction');
            $ciTypeProjectRestriction->setLabel('ProjectRestriction');
            $ciTypeProjectRestriction->setAttrib('title', $translator->translate('attributeProjectRestrictionTitle'));
            $this->addElement($ciTypeProjectRestriction);
        }


        $ciTypesSelect[null] = $translator->translate('pleaseChose');
        foreach ($ciTypes as $ciType) {
            $ciTypesSelect[$ciType['id']] = $ciType['name'];
        }

        $ciType = new Zend_Form_Element_Select('ciType');
        $ciType->setLabel('ciType')
            ->setRequired(true)
            ->setMultiOptions($ciTypesSelect);
        $this->addElement($ciType);


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

    public function addAttributes($id)
    {
        $attributeElement = new Zend_Form_Element_MultiCheckbox('ciTypeAttributes');
        $attributeElement->setLabel('attributes');

        if ($id) {
            $ciTypeDaoImpl = new Dao_CiType();
            $hierarchy     = $ciTypeDaoImpl->retrieveCiTypeHierarchy($id);

            $ciTypeList = '0';
            foreach ($hierarchy as $hier) {
                $ciTypeList .= ', ' . $hier;
            }
            $attributes = $ciTypeDaoImpl->getAttributesByCiTypeHierarchy($ciTypeList);

            $attributesSelect = array();
            foreach ($attributes as $attribute) {
                $attributesSelect[$attribute['id']] = $attribute['name'];
            }

            $attributeElement->addMultiOptions($attributesSelect);
        }

        $attributeElement->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'attributesRow')),
        ));

        $this->addElement($attributeElement);
    }


}