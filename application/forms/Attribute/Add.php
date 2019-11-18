<?php

class Form_Attribute_Add extends Form_AbstractAppForm
{
    public function __construct($translator, $attributeList, $sessionID, $attributeGroupId = null, $options = null)
    {
        parent::__construct($translator, $options);

        // create second autocomplete form
        $this->setName('autocompleteattributes');


        $this->setElementDecorators(array(
            'ViewHelper',
            array('HtmlTag', array('tag' => 'div', 'style' => 'float:left; width:30px')),
        ));

        $attachmentAttributeId = new Zend_Form_Element_Text('autoAttribute');
        $attachmentAttributeId->setLabel('attribute');
        $attachmentAttributeId->setAutoInsertNotEmptyValidator(true);
        $attachmentAttributeId->setAttrib('class', 'attributeSelect');

        $autoSubmit = new Zend_Form_Element_Submit('add');
        $autoSubmit->setLabel('add');

        $hidden = new Zend_Form_Element_Hidden('sessionID');
        $hidden->setValue($sessionID);
        $hidden->clearDecorators();
        $hidden->addDecorator('ViewHelper');

        $this->addElements(array($attachmentAttributeId, $autoSubmit, $hidden));


    }

}