<?php

class Util_AttributeType_Type_Info extends Util_AttributeType_Type_Abstract
{

    const ATTRIBUTE_VALUES_ID = 'text';
    const ATTRIBUTE_TYPE_ID   = 17;
    const ALLOW_EMPTY = true;

    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {

        $formSpecific = new Form_Attribute_IndividualInfo($translator);
        return $formSpecific;
    }


    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $info             = $attributeDaoImpl->getAttributeDefaultValues($attribute[Db_Attribute::ID]);
        $info             = $info[0]['value'];

        $attribute['value_text'] = $info;
        $attribute['noEscape']   = true;//render html content
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


        $selection        = array();
        $selection[0]     = "";
        $attributeDaoImpl = new Dao_Attribute();
        $info             = $attributeDaoImpl->getAttributeDefaultValues($attributeId);
        $info             = $info[0]['value'];

        $select = new Zend_Form_Element_Textarea($attributeName);
        $select->setLabel($attributeDescription);
        $select->setValue($info);

        if ($maxLength)
            $select->setAttrib('maxlength', $maxLength);

        if ($cols)
            $select->setAttrib('cols', $cols);

        if ($rows)
            $select->setAttrib('rows', $rows);

        $select->setAttrib('disabled', true);
        $select->setAttrib('class', 'disabled');

        if ($attributeNote) {
            $select->removeDecorator('description');
            $select->setDescription($attributeNote);
            $select->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $select->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($select);
    }


    /**
     * prepare ci attribute value for single edit
     *
     * @param unknown_type $values
     */
    public function returnFormData($values, $attributeId = null)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $info             = $attributeDaoImpl->getAttributeDefaultValues($values[Db_CiAttribute::ATTRIBUTE_ID]);

        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = $info[0]['value'];
        return $data;
    }


    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $info             = $attributeDaoImpl->getAttributeDefaultValues($attribute[Db_Attribute::ID]);

        $ciId = $attribute['ci_id'];
        $info = $info[0]['value'];

        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId']]            = $info;
        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId'] . 'hidden'] = $values[$storedIDs[0]]['ciAttributeId'];
        return $formData;
    }


    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        return array(
            'value'      => $currentVal,
            'allowEmpty' => self::ALLOW_EMPTY,
            'skipUpdate' => true,
        );
    }

    public function addCi($values, $attribute, $ciId)
    {
        $key        = $attribute['genId'];
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        $data = array();
        return $data;
    }


    public static function insertMissingAttributes($ciId, $historyid = null)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $attributes       = $attributeDaoImpl->getAttributesByAttributeTypeCiID($ciId, Util_AttributeType_Type_Info::ATTRIBUTE_TYPE_ID);
        $historyDao       = new Dao_History();

        foreach ($attributes as $attr) {
            if (empty($attr[Db_CiAttribute::ID])) {
                if (!$historyid) {
                    $historyid = $historyDao->createHistory(0, Enum_History::CI_UPDATE);
                }

                //if no id -> create ci-attribute-entry
                $data[Db_CiAttribute::CI_ID]        = $ciId;
                $data[Db_CiAttribute::ATTRIBUTE_ID] = $attr['attribute_id'];
                $data[Db_CiAttribute::IS_INITIAL]   = '1';
                $data[Db_CiAttribute::HISTORY_ID]   = $historyid;
                $attributeDaoImpl->insertCiAttribute($data);
            }
        }
        $attributeDaoImpl = null;

    }
}