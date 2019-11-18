<?php

class Form_Attribute_OptionChange extends Form_AbstractAppForm
{

    public function __construct($translator, $attributeOptions, $attributeId, $isCreate, $options = array())
    {
        parent::__construct($translator, $options);
        $this->setName('option');
        $this->setAttrib('enctype', 'multipart/form-data');
        if ($isCreate)
            $this->setAction(APPLICATION_URL . 'attribute/optionwizard/attributeId/' . $attributeId . '/isNew/1');
        else
            $this->setAction(APPLICATION_URL . 'attribute/optionwizard/attributeId/' . $attributeId . '/isChange/1');

        // options
        foreach ($attributeOptions as $option) {
            if ($isCreate) {
                $optionCleared = preg_replace('/\W/', '', $option['value']);

                $element = new Zend_Form_Element_Text($optionCleared);
                $element->setValue($option['value']);

                $order = new Zend_Form_Element_Text($optionCleared . 'ordernumber');
                if (isset($option[Db_AttributeDefaultValues::ORDER_NUMBER]))
                    $order->setValue($option[Db_AttributeDefaultValues::ORDER_NUMBER]);
                $order->setAttrib('size', '5');
                $order->setAttrib('maxlength', '3');


                $save = new Zend_Form_Element_Submit($optionCleared . 'save');
                $save->setAttrib('class', 'standard_button');
                $save->setName($optionCleared . 'save');
            } else {
                $element = new Zend_Form_Element_Text($option['id']);
                $element->setValue($option['value']);

                $order = new Zend_Form_Element_Text($option['id'] . 'ordernumber');
                if (isset($option[Db_AttributeDefaultValues::ORDER_NUMBER]))
                    $order->setValue($option[Db_AttributeDefaultValues::ORDER_NUMBER]);
                $order->setAttrib('size', '5');
                $order->setAttrib('maxlength', '3');

                $save = new Zend_Form_Element_Submit($option['id'] . 'save');
                $save->setAttrib('class', 'standard_button');
                $save->setName($option['id'] . 'save');

                if (!$option[Db_AttributeDefaultValues::IS_ACTIVE]) {
                    $element->setAttrib('disabled', 'true');
                    $element->setAttrib('title', 'Disabled');
                }
            }


            $element->setDecorators(array('ViewHelper', 'Errors'));

            $order->setDecorators(array('ViewHelper', 'Errors'));

            $save->setLabel('optionChange');
            $save->setDecorators(array('ViewHelper', 'Errors'));

            $this->addElements(array($element, $order, $save));
        }

    }

}