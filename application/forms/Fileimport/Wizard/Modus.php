<?php

class Form_Fileimport_Wizard_Modus extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('createForm');
        $this->setAttrib('enctype', 'multipart/form-data');


        $data                  = array();
        $data['autoimport']    = $translator->translate('modusAutoImport');
        $data['autoinsert']    = $translator->translate('modusAutoInsert');
        $data['autoattribute'] = $translator->translate('modusAutoAttribute');
        $data['manualimport']  = $translator->translate('modusManualImport');
        $data['manualinsert']  = $translator->translate('modusManualInsert');
        $data['updateauto']    = $translator->translate('modusUpdateAuto');
        $data['updatemanual']  = $translator->translate('modusUpdateManual');

        $select = new Zend_Form_Element_Radio('select');
        $select->setMultiOptions($data);
        $select->setValue('import');
        $select->setAttrib('title', $translator->translate('fileimportModusTitle'));
        $select->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('next');
        $this->addElements(array($select));
    }

}