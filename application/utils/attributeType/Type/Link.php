<?php

class Util_AttributeType_Type_Link extends Util_AttributeType_Type_Abstract
{

    const ATTRIBUTE_VALUES_ID = 'text';
    const ATTRIBUTE_TYPE_ID   = 11;


    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        $attribute[Db_CiAttribute::VALUE_TEXT] = '<a href="' . $attribute[Db_CiAttribute::VALUE_TEXT] . '" target="_blank">' . $attribute[Db_CiAttribute::VALUE_TEXT] . '</a>';
        $attribute['noEscape']                 = true;
        return $attribute;
    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElements($ciAttribute)
     */
    public function getFormElements($ciAttribute, $key = null, $ciId = null, $isValidate = false, $userId = null)
    {
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

        $input = new Zend_Form_Element_Text($attributeName);
        $input->setLabel($attributeDescription);

        if ($maxLength)
            $input->setAttrib('maxlength', $maxLength);

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

        if ($attributeNote) {
            $input->removeDecorator('description');
            $input->setDescription($attributeNote);
            $input->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $input->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($input);
    }
}