<?php

class Form_Attribute_IndividualFilter extends Zend_Form_SubForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('filter');

        // Name
        $name = new Zend_Form_Element_Textarea('filter');
        $name->setLabel('filterQuery');
        $name->addValidator(new Form_Validator_SqlQuery());
        $this->addElement($name);

        $note = new Form_Element_Note('filterNote');
        $note->setValue($translator->translate('formFilterDescription'));

        $this->addElement($note);


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