<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Searchlist_Attribute extends Form_AbstractAppForm
{

    public function __construct($translator, $ciTypeId, $maxElements, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('childCreate');
        $this->setAttrib('enctype', 'multipart/form-data');

        $hiddenContainer = new Zend_Form_Element_Hidden('container');
        $hiddenContainer->setValue($containerId);
        $hiddenContainer->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $hiddenCiType = new Zend_Form_Element_Hidden('ciType');
        $hiddenCiType->setValue($ciTypeId);
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
        for ($i = 1; $i <= $maxElements; $i++) {

            $element = new Zend_Dojo_Form_Element_FilteringSelect('create_' . $i);
            $element->setLabel('bodyAttributeId');
            $element->setAutoComplete(true)
                ->setStoreId('tidStore')
                ->setStoreType('dojo.data.ItemFileReadStore')
                ->setStoreParams(array('url' => APPLICATION_URL . 'attribute/autocomplete'))
                ->setAttrib("searchAttr", "name")
                ->setRequired(false);

            $element->removeDecorator('Label');
            $this->addElement($element);

            $width = new Zend_Form_Element_Text('width_' . $i);
            $width->setLabel('width');
            $width->setAttrib('onKeyPress', 'return numbersonly(this, event);');
            $width->removeDecorator('Label');
            $this->addElement($width);
        }

        $submit = new Zend_Form_Element_Submit('save');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');
        $submit->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));
        $this->addElement($submit);
    }

}