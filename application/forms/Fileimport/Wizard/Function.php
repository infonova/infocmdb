<?php

class Form_Fileimport_Wizard_Function extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('createForm');
        $this->setAttrib('enctype', 'multipart/form-data');


        $data                 = array();
        $data['attribute']    = $translator->translate('attribute');
        $data['relation']     = $translator->translate('relation');
        $data['importauto']   = $translator->translate('modusUpdateAuto');
        $data['importmanual'] = $translator->translate('modusUpdateManual');


        $modus = new Zend_Form_Element_Radio('modus');
        $modus->setMultiOptions($data);
        $modus->setValue('import');
        $modus->setAttrib('class', 'modus');
        $modus->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $this->addElement($modus);
    }

}