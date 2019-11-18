<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Fileimport_Filter extends Form_AbstractAppForm
{
    public function __construct($translator, $queueList, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('fileimportFilter');
        $this->setAttrib('enctype', 'multipart/form-data');

        $search = new Zend_Form_Element_Select('search');
        $search->setMultiOptions($queueList);
        $search->setLabel('filter');
        $search->setAttrib('onchange', 'this.form.submit()');
        $search->setDecorators(array(
            'ViewHelper',
            'Errors',
            array('Label', array('tag' => 'td')),
            array('HtmlTag', array('tag' => 'td')),
        ));


        $this->addElements(array($search));
        $this->setDecorators(array());
    }

}