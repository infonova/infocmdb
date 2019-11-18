<?php

class Form_Event_Filter extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('attributeFilter');
        $this->setAttrib('enctype', 'multipart/form-data');


        $search = new Zend_Form_Element_Text('search');
        $search->setLabel('filter');
        $search->setDecorators(array(
            'ViewHelper',
            'Errors',
            array('Label', array('tag' => 'td')),
            array('HtmlTag', array('tag' => 'td')),
        ));

        $submit = new Zend_Form_Element_Submit('filterEvents');
        $submit->setLabel('');
        $submit->setAttrib('class', 'attribute_search_button');
        $submit->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $this->addElements(array($search, $submit));

        $this->setDecorators(array());

    }

}