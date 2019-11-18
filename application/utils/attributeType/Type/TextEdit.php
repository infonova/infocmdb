<?php

class Util_AttributeType_Type_TextEdit extends Util_AttributeType_Type_Abstract
{

    const ATTRIBUTE_VALUES_ID = 'text';
    const ATTRIBUTE_TYPE_ID   = 3;

    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        return new Form_Attribute_IndividualText($translator, $options);
    }

    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElementsForSingleEdit($ciAttribute)
     */
    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {
        $hint    = $ciAttribute[Db_Attribute::HINT];
        $element = new Zend_Form_Element_Textarea('value');
        $element->setAttrib('class', 'tinymce');

        if ($ciAttribute[Db_Attribute::TEXTAREA_COLS])
            $element->setAttrib('cols', $ciAttribute[Db_Attribute::TEXTAREA_COLS]);
        if ($ciAttribute[Db_Attribute::TEXTAREA_ROWS])
            $element->setAttrib('rows', $ciAttribute[Db_Attribute::TEXTAREA_ROWS]);
        $element->setValue($ciAttribute[Db_CiAttribute::VALUE_TEXT]);


        if ($hint) {
            $element->setDescription($this->prepareHintForTooltip($hint));
        }

        $translator = $this->createTranslator();

        $registry                    = Zend_Registry::getInstance();
        $headscripts                 = $registry['headScripts'];
        $headscripts['tinyMCE']      = APPLICATION_URL . 'js/tiny_mce/tiny_mce.js';
        $headscripts['tinyMCE_init'] = APPLICATION_URL . 'js/tiny_mce/tiny_mce_init_' . $translator->getLocale() . '.js';
        Zend_Registry::set('headScripts', $headscripts);

        return array($element);
    }

    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        $attribute[Db_CiAttribute::VALUE_TEXT] = trim($attribute[Db_CiAttribute::VALUE_TEXT]);
        $attribute[Db_CiAttribute::VALUE_TEXT] = str_replace("\n", "", $attribute[Db_CiAttribute::VALUE_TEXT]);
        $attribute['noEscape']                 = true;
        return $attribute;
    }

    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElements($ciAttribute)
     */
    public function getFormElements($ciAttribute, $key = null, $ciId = null, $isValidate = false, $userId = null)
    {
        $attributeDaoImpl = new Dao_Attribute();

        $attributeId          = $ciAttribute[Db_Attribute::ID];
        $attributeName        = $ciAttribute[Db_Attribute::NAME];
        $attributeDescription = $ciAttribute[Db_Attribute::DESCRIPTION];
        $attributeNote        = $ciAttribute[Db_Attribute::NOTE];
        $attributeType        = $ciAttribute['type'];
        $attributeValue       = $ciAttribute['value'];
        $notNull              = $ciAttribute[Db_CiTypeAttribute::IS_MANDATORY];
        $isUnique             = $ciAttribute[Db_Attribute::IS_UNIQUE];
        $regex                = $ciAttribute['regex'];
        $write                = $ciAttribute['permission_write'];
        $maxLength            = $ciAttribute[Db_Attribute::INPUT_MAXLENGTH];
        $cols                 = $ciAttribute[Db_Attribute::TEXTAREA_COLS];
        $rows                 = $ciAttribute[Db_Attribute::TEXTAREA_ROWS];
        $hint                 = $ciAttribute[Db_Attribute::HINT];


        $defaultvalue = $attributeDaoImpl->getAttributeDefaultValues($attributeId);
        $defaultvalue = $defaultvalue[0][Db_AttributeDefaultValues::VALUE];


        $input = new Zend_Form_Element_Textarea($attributeName);
        $input->setAttrib('class', 'tinymce');
        $input->setLabel($attributeDescription);
        if ($maxLength)
            $input->setAttrib('maxlength', $maxLength);
        if ($cols)
            $input->setAttrib('cols', $cols);
        if ($rows)
            $input->setAttrib('rows', $rows);

        if ($isUnique) {
            $input->addValidator(new Form_Validator_UniqueConstraint($attributeId, $ciId));
            $input->setLabel('(u) ' . $attributeDescription);
        }

        if ($notNull) {
            $input->setRequired(true);
            $input->setAutoInsertNotEmptyValidator(true);
        }

        if (!$write) {
            $input->setAttrib('disabled', true);
            $input->setAttrib('class', 'disabled');
        }

        if ($regex) {
            $input->addValidator('regex', false, array($regex));
        }

        if ($attributeNote) {
            $input->removeDecorator('description');
            $input->setDescription($attributeNote);
            $input->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $input->setDescription($this->prepareHintForTooltip($hint));
        }
        if ($defaultvalue) {
            $input->setValue($defaultvalue);
        }


        $translator = $this->createTranslator();

        $registry = Zend_Registry::getInstance();
        if($registry->offsetExists('headScripts')) {
            $headscripts                 = Zend_Registry::get('headScripts');
            $headscripts['tinyMCE']      = APPLICATION_URL . 'js/tiny_mce/tiny_mce.js';
            $headscripts['tinyMCE_init'] = APPLICATION_URL . 'js/tiny_mce/tiny_mce_init_' . $translator->getLocale() . '.js';
            Zend_Registry::set('headScripts', $headscripts);
        }


        return array($input);
    }

    public function getValueByCiAttribute($ciAttribute, $nl2br = false)
    {
        return $ciAttribute[Db_CiAttribute::VALUE_TEXT];
    }
}