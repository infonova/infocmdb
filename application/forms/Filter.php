<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Filter extends Form_AbstractAppForm
{
    public function __construct($translator, $columns = null, $options = null)
    {


        parent::__construct($translator, $options);
        $this->setName('searchForm');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setMethod('POST');

        $search = new Zend_Form_Element_Text('search');
        $search->setLabel('filter');
        $search->setDecorators(array(
            'ViewHelper',
            'Errors',
            array('Label', array('tag' => 'td')),
            array('HtmlTag', array('tag' => 'td')),
        ));

        if (isset($columns) && $columns != null) {

            foreach ($columns as $column) {
                if (($options['columnOptions'][$column]['element_type'] ?? '') === 'select') {
                    $columnfilter = new Zend_Form_Element_Select($column);
                    $optionsArray = array('' => '') + $options['columnOptions'][$column]['select_options'];
                    $columnfilter->addMultiOptions($optionsArray);
                } else {
                    $columnfilter = new Zend_Form_Element_Text($column);
                }
                $columnfilter->setDecorators(array(
                    'Errors',
                    'ViewHelper',

                ));

                $this->addElement($columnfilter);

            }

        }


        $submit = new Zend_Form_Element_Submit('filterButton');
        $submit->setLabel($translator->translate('filter'));
        $submit->setAttrib('class', 'attribute_search_button');
        $submit->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $this->addElements(array($search, $submit));

    }
}