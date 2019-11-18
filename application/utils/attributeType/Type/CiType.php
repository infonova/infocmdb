<?php

class Util_AttributeType_Type_CiType extends Util_AttributeType_Type_Abstract
{

    protected $_formElements;
    const ATTRIBUTE_VALUES_ID = 'ci';
    const ATTRIBUTE_TYPE_ID   = 16;


    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        $ciTypeDaoImpl = new Dao_CiType();
        $ciTypes       = $ciTypeDaoImpl->getCiTypeRowset();

        return new Form_Attribute_IndividualCitype($translator, $ciTypes, $options);
    }


    public function getAutocompleteSelection(int $attributeId, $filter, int $ciId, int $userId)
    {
        $utilCiType = new Util_CiType();
        return $utilCiType->getSelectionForFormElement($attributeId, $filter, false, $ciId, $userId);
    }

    public function getAutocompleteValue($attributeId, $ciId)
    {
        // load value!!
        $utilCiType = new Util_CiType();
        return $utilCiType->getCiTypeValueToDisplay($attributeId, $ciId);
    }

    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {
        $attributeId          = $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID];
        $ciAttributeId        = $ciAttribute[Db_CiAttribute::ID];
        $attributeName        = (isset($ciAttribute[Db_Attribute::NAME])) ? $ciAttribute[Db_Attribute::NAME] : null;
        $attributeDescription = (isset($ciAttribute[Db_Attribute::DESCRIPTION])) ? $ciAttribute[Db_Attribute::DESCRIPTION] : null;
        $attributeNote        = (isset($ciAttribute[Db_Attribute::NOTE])) ? $ciAttribute[Db_Attribute::NOTE] : null;
        $attributeType        = (isset($ciAttribute['type'])) ? $ciAttribute['type'] : null;
        $attributeValue       = (isset($ciAttribute['value'])) ? $ciAttribute['value'] : null;
        $notNull              = (isset($ciAttribute[Db_CiTypeAttribute::IS_MANDATORY])) ? $ciAttribute[Db_CiTypeAttribute::IS_MANDATORY] : null;
        $isUnique             = (isset($ciAttribute[Db_Attribute::IS_UNIQUE])) ? $ciAttribute[Db_Attribute::IS_UNIQUE] : null;
        $regex                = (isset($ciAttribute['regex'])) ? $ciAttribute['regex'] : null;
        $maxLength            = (isset($ciAttribute[Db_Attribute::INPUT_MAXLENGTH])) ? $ciAttribute[Db_Attribute::INPUT_MAXLENGTH] : null;
        $cols                 = (isset($ciAttribute[Db_Attribute::TEXTAREA_COLS])) ? $ciAttribute[Db_Attribute::TEXTAREA_COLS] : null;
        $rows                 = (isset($ciAttribute[Db_Attribute::TEXTAREA_ROWS])) ? $ciAttribute[Db_Attribute::TEXTAREA_ROWS] : null;
        $hint                 = (isset($ciAttribute[Db_Attribute::HINT])) ? $ciAttribute[Db_Attribute::HINT] : null;


        $util      = new Util_CiType();
        $selection = $util->getSelectionForFormElement($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID], null, true, $ciId, $userId);

        // SELECT -> option drop down
        $select = new Zend_Form_Element_Select('value');
        $select->addMultiOptions($selection);

        if ($ciAttribute[Db_Attribute::IS_AUTOCOMPLETE]) {
            $select->setAttrib("id", $attributeId);
            $select->setAttrib("autocomplete", true);
        }

        if ($notNull) {
            $select->setRequired(true);
            $select->setAutoInsertNotEmptyValidator(true);
        }

        if ($attributeNote) {
            $select->removeDecorator('description');
            $select->setDescription($attributeNote);
        }

        if ($hint) {
            $select->setDescription($this->prepareHintForTooltip($hint));
        }

        if ($cols == null || $cols == 0)
            $cols = 180;

        $select->setAttrib('style', 'width:' . $cols . 'px;');

        $select->setValue($ciAttribute[Db_CiAttribute::VALUE_CI]);

        return array($select);
    }


    /**
     * @todo: implement me!!!
     *
     * @param $ciAttribute
     */
    public function getFormElements($ciAttribute, $key = null, $ciId = null, $isValidate = false, $userId = null)
    {
        $daoAttribute         = new Dao_Attribute();
        $attributeId          = $ciAttribute[Db_Attribute::ID];
        $attributeName        = $ciAttribute[Db_Attribute::NAME];
        $attributeDescription = $ciAttribute[Db_Attribute::DESCRIPTION];
        $attributeNote        = $ciAttribute[Db_Attribute::NOTE];
        $attributeType        = $ciAttribute['type'];
        $attributeValue       = $ciAttribute['value'];  //always null!
        $notNull              = $ciAttribute[Db_CiTypeAttribute::IS_MANDATORY];
        $isUnique             = $ciAttribute[Db_Attribute::IS_UNIQUE];
        $regex                = $ciAttribute['regex'];
        $write                = $ciAttribute['permission_write'];
        $maxLength            = $ciAttribute[Db_Attribute::INPUT_MAXLENGTH];
        $cols                 = $ciAttribute[Db_Attribute::TEXTAREA_COLS];
        $rows                 = $ciAttribute[Db_Attribute::TEXTAREA_ROWS];
        $hint                 = $ciAttribute[Db_Attribute::HINT];

        if ($ciAttribute[Db_Attribute::IS_AUTOCOMPLETE]) {

            $selection = array();
            if (!empty($ciAttribute['ciAttributeId'])) {
                //get ci_attribute-row --> get current value_ci
                $ciAttributeRow = $daoAttribute->getSingleCiAttributeById($ciAttribute['ciAttributeId']);
                $selection      = array($ciAttributeRow['value_ci'] => '');
            }
            $select = new Zend_Form_Element_Select($attributeName);
            $select->addMultiOptions($selection);
            $select->setRegisterInArrayValidator(false);
            $jscripts = Zend_Registry::get('jsScripts');

            if (!$jscripts)
                $jscripts = array();

            if (!$jscripts['autocomplete'])
                $jscripts['autocomplete'] = array();
            $jscripts['autocomplete'][$attributeName] = $attributeId;


            Zend_Registry::set('jsScripts', $jscripts);


        } else {
            $util      = new Util_CiType();
            $selection = $util->getSelectionForFormElement($ciAttribute[Db_Attribute::ID], null, true, $ciId, $userId);

            // SELECT -> option drop down
            $select = new Zend_Form_Element_Select($attributeName);
            $select->addMultiOptions($selection);

        }

        $select->setLabel($attributeDescription);

        if ($notNull) {
            $select->setRequired(true);
            $select->setAutoInsertNotEmptyValidator(true);
        }

        if (!$write) {
            $select->setAttrib('disabled', true);
            $select->setAttrib('class', 'disabled');
        }

        if ($attributeNote) {
            $select->removeDecorator('description');
            $select->setDescription($attributeNote);
        }

        if ($hint) {
            $select->setDescription($this->prepareHintForTooltip($hint));
        }

        if ($cols == null || $cols == 0)
            $cols = 180;


        $select->setAttrib('style', 'width:' . $cols . 'px;');

        return array($select);
    }


    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $currentVal;
        return $data;
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key        = $attribute['genId'];
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        if (!$currentVal)
            return null;

        if ($currentVal == '0') {
            return null;
        }

        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $currentVal;
        return $data;
    }


    /**
     * modifies the attribute value to be displayed
     *
     * @param unknown_type $attribute
     * @param unknown_type $path
     */
    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        if (!isset($attribute[Db_CiAttribute::VALUE_CI]))
            $attribute[Db_CiAttribute::VALUE_CI] = $attribute[Db_CiAttribute::VALUE_TEXT];
        $util                                  = new Util_CiType();
        $attVals                               = $util->getCiTypeValueToDisplay($attribute[Db_CiAttribute::ID], $attribute[Db_CiAttribute::VALUE_CI]);
        $attribute[Db_CiAttribute::VALUE_TEXT] = '<a href="' . APPLICATION_URL . '/ci/detail/ciid/' . $attribute[Db_CiAttribute::VALUE_CI] . '">' . $attVals . '</a>';
        $attribute['noEscape']                 = true;
        return $attribute;
    }


    public function returnFormData($values, $attributeId = null)
    {
        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $values['value'];
        return $data;
    }

    /**
     * match an array against a regular expression (needed by saving the ciParentType)
     *
     * @param regex $pattern
     * @param array $input
     */
    public function match_regex($pattern, $input, $flags = 0)
    {
        $keys = preg_grep($pattern, array_keys($input), $flags);
        $vals = array();
        foreach ($keys as $key) {
            $vals[$key] = $input[$key];
        }
        foreach ($vals as $key => $val) {
            if ($val == '' || $val == ' ' || is_null($val)) {
                unset($vals[$key]);
            }
        }
        return $vals;
    }
}