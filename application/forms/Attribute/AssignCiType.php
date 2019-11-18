<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Attribute_AssignCiType extends Form_AbstractAppForm
{
    public function __construct($translator, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('assign');
        $this->setAttrib('enctype', 'multipart/form-data');


        $ciTypeDaoImpl = new Dao_CiType();
        $ciTypes       = $ciTypeDaoImpl->getCiTypeRowset();

        foreach ($ciTypes as $ciType) {
            $ciTypesSelect[$ciType['id']] = $ciType['name'];
        }

        // Name
        $ciType = new Zend_Form_Element_Select('ciType');
        $ciType->setLabel('ciType')
            ->setRequired(true)
            ->addMultiOption(0, $translator->translate('pleaseChose'))
            ->addMultiOptions($ciTypesSelect);

        $this->addElements(array($ciType));

    }

}