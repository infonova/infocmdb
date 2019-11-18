<?php

class Form_Attribute_IndividualOptionsChange extends Zend_Form_SubForm
{

    public function __construct($translator, $attributeOptions, $attributeId = null, $options = array())
    {
        parent::__construct($translator, $options);
        $this->setName('options');
        $this->setAttrib('enctype', 'multipart/form-data');

        // options
        foreach ($attributeOptions as $option) {
            if (!$attributeId) {

                $optionCleared = preg_replace('/\W/', '', $option['value']);

                $element = new Zend_Form_Element_Text('option_' . $optionCleared);
                $order   = new Zend_Form_Element_Text('option_' . $optionCleared . 'ordernumber');

                if (isset($option['order'])) {
                    $order->setValue($option['order']);
                }
            } else {
                $element = new Zend_Form_Element_Text('option_' . $option['id']);
                $order   = new Zend_Form_Element_Text('option_' . $option['id'] . 'ordernumber');
                if (isset($option[Db_AttributeDefaultValues::ORDER_NUMBER])) {
                    $order->setValue($option[Db_AttributeDefaultValues::ORDER_NUMBER]);
                }

                if (!$option['valid']) {
                    $element->setAttrib('disabled', 'true');
                    $element->setAttrib('title', 'Disabled');
                }
            }


            $element->setValue($option['value']);

            $order->setAttrib('size', '5');
            $order->setAttrib('maxlength', '3');


            $remove = new Zend_Form_Element_Image($option['id'] . 'remove');
            $remove->setLabel('remove');
            $remove->setImage(APPLICATION_URL . 'images/icon/inactive.png');
            $remove->setAttrib('alt', 'unused');
            $remove->setAttrib('height', 14);
            $remove->setAttrib('width', 14);
            $remove->setAttrib('onClick', 'javascript:removeOption(this); return false;');


            $element->setDecorators(array('ViewHelper', 'Errors'));
            $order->setDecorators(array('ViewHelper', 'Errors'));
            $remove->setDecorators(array('ViewHelper', 'Errors'));

            $this->addElements(array($remove, $element, $order));
        }

    }

}