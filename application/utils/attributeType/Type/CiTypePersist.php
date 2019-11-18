<?php

class Util_AttributeType_Type_CiTypePersist extends Util_AttributeType_Type_Abstract
{

    protected $_formElements;
    const ATTRIBUTE_VALUES_ID = 'ci';
    const ATTRIBUTE_TYPE_ID   = 19;


    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        $ciTypeDaoImpl = new Dao_CiType();
        $ciTypes       = $ciTypeDaoImpl->getCiTypeRowset();

        return new Form_Attribute_IndividualCitype($translator, $ciTypes, $options, true);
    }


    public function getAutocompleteSelection(int $attributeId, $filter, int $ciId = null, int $userId = null)
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
        $attributeName        = $ciAttribute[Db_Attribute::NAME];
        $attributeDescription = $ciAttribute[Db_Attribute::DESCRIPTION];
        $attributeNote        = $ciAttribute[Db_Attribute::NOTE];
        $attributeType        = $ciAttribute['type'];
        $attributeValue       = $ciAttribute['value'];
        $notNull              = $ciAttribute[Db_CiTypeAttribute::IS_MANDATORY];
        $isUnique             = $ciAttribute[Db_Attribute::IS_UNIQUE];
        $regex                = $ciAttribute['regex'];
        $maxLength            = $ciAttribute[Db_Attribute::INPUT_MAXLENGTH];
        $cols                 = $ciAttribute[Db_Attribute::TEXTAREA_COLS];
        $rows                 = $ciAttribute[Db_Attribute::TEXTAREA_ROWS];
        $hint                 = $ciAttribute[Db_Attribute::HINT];

        $util      = new Util_CiType();
        $selection = $util->getSelectionForFormElement($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID]);

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

        // split!
        $cal = $ciAttribute[Db_CiAttribute::VALUE_CI];
        $select->setValue($cal);

        if ($cols == null || $cols == 0)
            $cols = 180;

        $select->setAttrib('style', 'width:' . $cols . 'px;');

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
        $attributeValue       = $ciAttribute['value']; //always null!
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
                $selection      = array($ciAttributeRow['value_ci'] => $ciAttributeRow['note']);
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
            $selection = $util->getSelectionForFormElement($ciAttribute[Db_Attribute::ID]);

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


    /**
     * modifies the attribute value to be displayed
     *
     * @param unknown_type $attribute
     * @param unknown_type $path
     */
    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        $id    = $attribute[Db_CiAttribute::VALUE_CI];
        $value = $attribute['valueNote'];

        if (!isset($value) && isset($attribute['ciAttributeId']) && !empty($attribute['ciAttributeId'])) {
            $daoAttribute = new Dao_Attribute;
            $ciAttribute  = $daoAttribute->getCiAttributeById($attribute['ciAttributeId']);
            $value        = $ciAttribute[Db_CiAttribute::NOTE];
        }

        $attribute[Db_CiAttribute::VALUE_TEXT] = '<a href="' . APPLICATION_URL . '/ci/detail/ciid/' . $id . '">' . $value . '</a>';
        $attribute['noEscape']                 = true;
        return $attribute;
    }


    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        $data = null;
        $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_CI];
        //$data = stristr  ($data, ':', true);

        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId']]            = $data;
        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId'] . 'hidden'] = $values[$storedIDs[0]]['ciAttributeId'];
        return $formData;
    }


    public function returnFormData($values, $attributeId = null)
    {
        $remoteCi = $values['value'];

        if (!$remoteCi || $remoteCi == '' || $remoteCi == ' ') {
            return array();
        }

        if (!$attributeId) {
            return array();
        }
        $util   = new Util_CiType();
        $values = $util->getSelectionForFormElement($attributeId);

        foreach ($values as $selCiId => $selVal) {
            if ($selCiId == $remoteCi) {
                $remoteValue = $selVal;
                break;
            }
        }

        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $remoteCi;
        $data[Db_CiAttribute::NOTE]     = $remoteValue;
        return $data;
    }


    // TODO: implement me
    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $id                             = $values[$attribute[Db_Attribute::NAME] . $key];
        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $id;
        $data[Db_CiAttribute::NOTE]     = $currentVal;
        return $data;
    }


    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];


        if (!$values || !$attribute[Db_Attribute::ID]) {
            return array();
        }

        $util   = new Util_CiType();
        $values = $util->getSelectionForFormElement($attribute[Db_Attribute::ID]);

        $remoteValue = "";
        foreach ($values as $selCiId => $selVal) {
            if ($selCiId == $currentVal) {
                $remoteValue = $selVal;
                break;
            }
        }

        $currentVal = $remoteValue;
        return array(
            'value' => $currentVal,
        );;
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key      = $attribute['genId'];
        $remoteCi = $values[$attribute[Db_Attribute::NAME] . $key];

        if (!$remoteCi)
            return null;

        $util   = new Util_CiType();
        $values = $util->getSelectionForFormElement($attribute[Db_Attribute::ID]);

        foreach ($values as $selCiId => $selVal) {
            if ($selCiId == $remoteCi) {
                $remoteValue = $selVal;
                break;
            }
        }

        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $remoteCi;
        $data[Db_CiAttribute::NOTE]     = $remoteValue;
        return $data;
    }
}