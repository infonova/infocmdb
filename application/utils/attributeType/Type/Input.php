<?php

class Util_AttributeType_Type_Input extends Util_AttributeType_Type_Abstract
{

    const ATTRIBUTE_VALUES_ID = 'text';
    const ATTRIBUTE_TYPE_ID   = 1;


    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        return new Form_Attribute_IndividualText($translator, $options);
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

        $cols        = $ciAttribute[Db_Attribute::TEXTAREA_COLS];
        $rows        = $ciAttribute[Db_Attribute::TEXTAREA_ROWS];
        $hint        = $ciAttribute[Db_Attribute::HINT];
        $uniquecheck = $ciAttribute[Db_Attribute::IS_UNIQUE_CHECK];

        $defaultvalue = $attributeDaoImpl->getAttributeDefaultValues($attributeId);
        $defaultvalue = $defaultvalue[0][Db_AttributeDefaultValues::VALUE];

        $input = new Zend_Form_Element_Text($attributeName);
        $input->setLabel($attributeDescription);

        if ($maxLength)
            $input->setAttrib('maxlength', $maxLength);

        if ($cols)
            $input->setAttrib('size', $cols);

        if ($isUnique) {
            $input->addValidator(new Form_Validator_UniqueConstraint($attributeId, $ciId));
            $input->setLabel('(u) ' . $input->getLabel());
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


        if ($uniquecheck) {

            $input->setAttrib('class', 'unique-check');
            $input->setAttrib('id', $attributeId);

        }


        $input->setAttrib('title', $attributeNote);


        return array($input);
    }

}