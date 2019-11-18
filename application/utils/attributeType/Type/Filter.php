<?php

class Util_AttributeType_Type_Filter extends Util_AttributeType_Type_Abstract
{

    const ATTRIBUTE_VALUES_ID = 'ci';
    const ATTRIBUTE_TYPE_ID   = 20;


    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        return new Form_Attribute_IndividualFilter($translator);
    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElements($ciAttribute)
     */
    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {
        // TODO: single edit forbidden! cannot be used with filter attribute
        return array();
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


        // returned values = attributeId or attributeName???? // TODO: name!
        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attributeId);
        $statement        = $query[Db_AttributeDefaultQueries::QUERY];

        $queryParameter = $attributeDaoImpl->getDefaultQueryParameter($query[Db_AttributeDefaultQueries::ID]);

        $selectors      = array();
        $selectionStart = "[";
        $selectionString;

        foreach ($queryParameter as $param) {
            array_push($selectors, $param[Db_AttributeDefaultQueriesParameter::PARAMETER]);

            if ($selectionString)
                $selectionString .= ",";

            $selectionString .= $param[Db_AttributeDefaultQueriesParameter::PARAMETER];
        }


        $valueToReplace = null;

        if ($ciId || $isValidate)
            $valueToReplace = '%';

        foreach ($selectors as $p2replace) {
            $replaceKey = ':' . $p2replace . ':';
            $statement  = str_replace($replaceKey, $valueToReplace, $statement);
        }

        $statement = str_replace(':ciid:', $ciId, $statement);

        $result = $attributeDaoImpl->executeStatement($statement);

        $data = array();
        foreach ($result as $key => $res) {
            $k = null;
            $v = null;
            foreach ($res as $attribute) {
                if (!$k) {
                    $k = $attribute;
                } else {
                    $v = $attribute;
                }
            }

            if ($k && $v)
                $data[$k] = $v;
        }

        // SELECT -> option drop down
        $select = new Zend_Form_Element_Select($attributeName);
        $select->setLabel($attributeDescription);
        $select->setRegisterInArrayValidator(false);


        $jscripts = Zend_Registry::get('jsScripts');

        if (!$jscripts)
            $jscripts = array();

        if (!$jscripts['filter'])
            $jscripts['filter'] = array();

        $jscripts['filter'][$attributeName] = $attributeId;
        Zend_Registry::set('jsScripts', $jscripts);

        $select->setAttrib('onClick', $selectionString);

        // TODO: befüllen wenn isPost???
        $select->addMultiOptions($data);


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
            $select->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $select->setDescription($this->prepareHintForTooltip($hint));
        }


        if ($cols == null || $cols == 0)
            $cols = 180;

        $select->setAttrib('style', 'width:' . $cols . 'px;');

        return array($select);
    }


    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        $id                                    = $attribute[Db_CiAttribute::VALUE_CI];
        $value                                 = $attribute['valueNote'];
        $attribute[Db_CiAttribute::VALUE_TEXT] = '<a href="' . APPLICATION_URL . '/ci/detail/ciid/' . $id . '">' . $value . '</a>';
        $attribute['noEscape']                 = true;
        return $attribute;
    }


    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $res = $this->getDataList($attribute[Db_Attribute::ID], $ciId);

        $id                             = $values[$attribute[Db_Attribute::NAME] . $key];
        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $id;
        $data[Db_CiAttribute::NOTE]     = $res[$id];

        if (!$data[Db_CiAttribute::NOTE])
            $data[Db_CiAttribute::NOTE] = 'ERROR';
        return $data;
    }


    public function addCi($values, $attribute, $ciId)
    {
        $data = $this->getDataList($attribute[Db_Attribute::ID]);

        $key      = $attribute['genId'];
        $remoteCi = $values[$attribute[Db_Attribute::NAME] . $key];
        $note     = $data[$remoteCi];

        if (!$remoteCi || $remoteCi == '0')
            return null;

        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $remoteCi;
        $data[Db_CiAttribute::NOTE]     = $note;
        return $data;
    }


    private function getDataList($attributeId, $ciId = null)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attributeId);
        $statement        = $query[Db_AttributeDefaultQueries::QUERY];

        $queryParameter = $attributeDaoImpl->getDefaultQueryParameter($query[Db_AttributeDefaultQueries::ID]);

        $selectors = array();
        foreach ($queryParameter as $param) {
            array_push($selectors, $param[Db_AttributeDefaultQueriesParameter::PARAMETER]);
        }


        foreach ($selectors as $p2replace) {
            $replaceKey = ':' . $p2replace . ':';
            $statement  = str_replace($replaceKey, '%', $statement);
        }

        $statement = str_replace(':ciid:', $ciId, $statement);
        $result    = $attributeDaoImpl->executeStatement($statement);

        $data = array();
        foreach ($result as $key => $res) {
            $k = null;
            $v = null;
            foreach ($res as $att) {
                if (!$k) {
                    $k = $att;
                } else {
                    $v = $att;
                }
            }

            if ($k && $v)
                $data[$k] = $v;
        }

        return $data;
    }

}