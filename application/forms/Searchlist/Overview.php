<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Searchlist_Overview extends Form_AbstractAppForm
{

    public function __construct($translator, $ciTypes, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('create');
        $this->setAttrib('enctype', 'multipart/form-data');

        foreach ($ciTypes as $ciType) {
            $type = new Zend_Form_Element_Radio($ciType[Db_CiType::ID]);
            $type->setMultiOptions(array('0' => ' | ', '1' => ' | ',)); // 0 => use default; 1 => use custom

            $type->setLabel($ciType[Db_CiType::NAME])
                ->setSeparator(' ')
                ->setValue(0);

            $this->addElement($type);
        }


        $this->setElementDecorators(array(
            'ViewHelper',
        ));

        $submit = new Zend_Form_Element_Submit('save');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');

        $this->addElement($submit);
    }

}