<?php

/**
 * This class is used to create the citype filter
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Ci_SelectPopup extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setAttrib('id', 'filter_form');
        $this->setName('filter');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAttrib('onSubmit', 'return filterPagination()');

        $searchField = new Zend_Form_Element_Text('value');
        $searchField->setLabel('searchstring');

        $this->addElement($searchField);

        $searchButton = new Zend_Form_Element_Submit('submit');
        $searchButton->setLabel('filter');
        $searchButton->setAttrib('class', 'attribute_search_button');
        $searchButton->setAttrib('onClick', 'return filterPagination()');

        $this->addElement($searchButton);
    }
}