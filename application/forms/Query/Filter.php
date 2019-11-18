<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Query_Filter extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('queryFilter');
        $this->setMethod('get');
        $this->setAttrib('enctype', 'multipart/form-data');


        $search = new Zend_Form_Element_Text('search');
        $search->setLabel('queryIndexSearch');
        $search->setDecorators(array(
            'ViewHelper',
            'Errors',
            array('Label', array('tag' => 'td')),
            array('HtmlTag', array('tag' => 'td')),
        ));

        $submit = new Zend_Form_Element_Submit('filterQuery');
        $submit->setLabel($translator->translate('filter'));
        $submit->setAttrib('class', 'attribute_search_button');
        $submit->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $this->addElements(array($search, $submit));

        $this->setDecorators(array());

    }

}