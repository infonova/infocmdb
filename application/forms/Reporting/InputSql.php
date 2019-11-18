<?php

class Form_Reporting_InputSql extends Form_AbstractAppForm
{

    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CreateForm');

        $sql = new Zend_Form_Element_Textarea('query');
        $sql->setLabel('sql');
        $sql->setRequired(true);
        $sql->setAttrib('rows', 14);
        $sql->setAutoInsertNotEmptyValidator(true);
        $sql->setAttrib('title', $translator->translate('reportingSqlTitle'));

        $this->addElements(array($sql));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width:80%;')),
            array('Label', array('tag' => 'td')),
        ));

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));
    }

}