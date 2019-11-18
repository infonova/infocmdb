<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Ci_SingleUpdate extends Form_AbstractAppForm
{
    public function __construct($translator, $ciid, $ciAttribute, $type, $page = null, $tabIndex = null, $userId = null, $ciId = null)
    {
        $ciAttribute[Db_CiAttribute::IS_INITIAL] = 1;
        $options                                 = (isset($options)) ? $options : null;
        parent::__construct($translator, $options);
        $this->setName('CreateForm');
        $this->setAttrib('enctype', 'multipart/form-data');

        if (isset($type)) {
            $this->setAction(APPLICATION_URL . 'ci/detail/ciid/' . $ciid . '/uniqueId/' . $ciAttribute[Db_CiAttribute::ID] . '/type/' . $type . '/page/' . $page);
        } else {
            $this->setAction(APPLICATION_URL . 'ci/detail/ciid/' . $ciid . '/uniqueId/' . $ciAttribute[Db_CiAttribute::ID] . '/tab_index/' . $tabIndex);
        }


        $attributeType = Util_AttributeType_Factory::get($ciAttribute['type']);
        $elements      = $attributeType->getFormElementsForSingleEdit($ciAttribute, $userId, $ciId);

        foreach ($elements as $element) {
            // replace standard description decorator with tooltip decorator
            if ($element->getDescription() != '') {
                $element->removeDecorator('description');
                $decorators = $element->getDecorators();
                array_splice($decorators, 1, 0, array(new Form_Decorator_MyTooltip()));

                $element->setDecorators($decorators);
            }

            if ($ciAttribute[Db_CiAttribute::IS_INITIAL] && $ciAttribute['type'] != Enum_AttributeType::SCRIPT
                && $ciAttribute['type'] != Enum_AttributeType::ATTACHMENT
                && $ciAttribute['type'] != Enum_AttributeType::EXECUTEABLE
                && $ciAttribute['type'] != Enum_AttributeType::SELECT_POPUP
            ) {
                $element->setRequired(true);
                $element->autoInsertNotEmptyValidator(true);
                $element->setLabel('');
                $element->removeDecorator('label');

            }

            if ($ciAttribute[Db_Attribute::IS_UNIQUE]) {
                $uniqueValidator = new Form_Validator_UniqueConstraint($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID], $ciid);
                $element->addValidator($uniqueValidator);
            }

            if ($ciAttribute[Db_Attribute::REGEX])
                $element->addValidator('regex', false, array($ciAttribute[Db_Attribute::REGEX]));


            $this->addElement($element);
        }
    }
}