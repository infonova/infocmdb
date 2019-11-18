<?php

class Util_AttributeType_Type_SelectQuery extends Util_AttributeType_Type_Abstract
{


    const ATTRIBUTE_TYPE_ID = 21;

    private $result;
    private $selection;


    public function getAutocompleteSelection(int $attributeId, $filter, int $ciId,int $userId)
    {

        // retrieve query end execute
        $ciDaoImpl         = new Dao_Ci();
        $attributeDaoImpl  = new Dao_Attribute();
        $attributeOriginal = $attributeDaoImpl->getAttribute($attributeId);
        $query             = $attributeDaoImpl->getDefaultQuery($attributeId);
        $query             = $query[Db_AttributeDefaultQueries::QUERY];

        $selection = array();

        try {
            if ($ciId) {
                $query = str_replace(':id:', $ciId, $query);
            }
            if ($filter) {
                $query = str_replace(':value:', $filter, $query);
            }

            $query = str_replace(':id:', '', $query);
            $query = str_replace(':value:', '', $query);


            $result = $attributeDaoImpl->executeStatement($query);

        } catch (Exception $e) {
            $selection[null] = 'FEHLER';
            $result          = array();
            $this->logger->log($e, Zend_Log::ERR);
        }

        mb_internal_encoding('UTF-8');

        $filter = mb_strtolower($filter);
        $filter = preg_quote($filter);
        $filter = str_replace('\*', '.*', $filter); #enable wildcard

        $currentValues = array();
        if (!empty($ciId)) {
            $currentValues = $attributeDaoImpl->getCurrentCiTypeValue($ciId, $attributeId);
            $currentValues = preg_split('/,[\s]?+/', $currentValues[0][Db_CiAttribute::VALUE_CI]);
        }


        if ($attributeOriginal[Db_Attribute::IS_PROJECT_RESTRICTED] === '1' && !empty($userId)) {
            $projectDaoImpl = new Dao_Project();
            $projectList    = $projectDaoImpl->getProjectsByUserId($userId);

            $projectIds = null;
            foreach ($projectList as $p) {
                $projectIds[] = $p[Db_Project::ID];
            }
            $allowedCiIdsResult = $ciDaoImpl->getCiListByProjectId($projectIds);
            $allowedCiIds       = array();
            foreach ($allowedCiIdsResult as $aCiId) {
                $allowedCiIds[$aCiId['id']] = $aCiId['id'];
            }

            //add currently selected CI to allowed CI-ID's
            if (!empty($currentValues)) {
                foreach ($currentValues as $currentValue) {
                    $matches                    = explode('::', $currentValue);
                    $currentCiId                = $matches[0];
                    $allowedCiIds[$currentCiId] = $currentCiId;
                }
            }
        }

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

            if ($k && $v && ($allowedCiIds === null || isset($allowedCiIds[$k]))) {
                $selection[$k] = $v;
            }

            $v = mb_strtolower($v);

            if ($filter) {
                if (!preg_match('/.*' . $filter . '.*/i', $v)) { // .* = 0 or more chars
                    unset($selection[$k]);
                }
            }


        }

        //move currently selected item to first position --> don't loose current value if autocomplete gets triggered --> result limit
        if ($attributeOriginal[Db_Attribute::IS_MULTISELECT] !== '1' && !empty($currentValues) && isset($selection[$currentValues[0]])) {
            $currentArrayValue = $selection[$currentValues[0]];
            unset($selection[$currentValues[0]]);
            $selection = array($currentValues[0] => $currentArrayValue) + $selection;
        }

        return $selection;
    }


    public function getAutocompleteValue($attributeId, $ciId)
    {
        // retrieve query end execute
        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attributeId);
        $query            = $query[Db_AttributeDefaultQueries::QUERY];

        $selection = array();

        try {
            $query = str_replace(':id:', '', $query);
            $query = str_replace(':value:', '', $query);

            $result = $attributeDaoImpl->executeStatement($query);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }

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
                $selection[$k] = $v;
        }

        foreach ($selection as $key => $value) {
            if ($key == $ciId) {
                return $value;
            }
        }
        return "";
    }

    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        $placeholders = array(
            ':id:'    => $translator->translate('attributeHintIndividualQueryCurrentCiId'),
            ':value:' => $translator->translate('attributeHintIndividualQueryCurrentValue'),
        );

        $queryForm = new Form_Attribute_IndividualQuery($translator, array(
            'onlyQuery'    => true,
            'placeholders' => $placeholders,
        ));

        $form = new Form_Attribute_IndividualMultiselect($translator);
        $queryForm->addElements($form->getElements());
        return $queryForm;
    }


    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {

        return $this->getFormElements($ciAttribute, null, $ciId, false, $userId, 'singleedit');
    }


    public function getFormElements($ciAttribute, $key = null, $ciId = null, $isValidate = false, $userId = null, $mode = 'edit')
    {
        if (isset($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID])) {
            $attributeId   = $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID];
            $ciAttributeId = $ciAttribute[Db_CiAttribute::ID];
        } else {
            $attributeId   = $ciAttribute[Db_Attribute::ID];
            $ciAttributeId = $ciAttribute['ciAttributeId'];
        }
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

        $multiselect  = $ciAttribute[Db_Attribute::IS_MULTISELECT];
        $autoComplete = $ciAttribute[Db_Attribute::IS_AUTOCOMPLETE];


        // retrieve query end execute
        $ciDaoImpl        = new Dao_Ci();
        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attributeId);
        $query            = $query[Db_AttributeDefaultQueries::QUERY];

        $currentValues = array();
        if (!empty($ciId)) {
            $currentValues = $attributeDaoImpl->getCurrentCiTypeValue($ciId, $attributeId);
            $currentValues = preg_split('/,[\s]?+/', $currentValues[0][Db_CiAttribute::VALUE_CI]);
        }

        $selection = array('' => '');//add empty option at top of the array to prevent auto-select of value

        if ($ciAttribute[Db_Attribute::IS_PROJECT_RESTRICTED] === '1' && !empty($userId)) {
            $projectDaoImpl = new Dao_Project();
            $projectList    = $projectDaoImpl->getProjectsByUserId($userId);

            $projectIds = null;
            foreach ($projectList as $p) {
                $projectIds[] = $p[Db_Project::ID];
            }
            $allowedCiIdsResult = $ciDaoImpl->getCiListByProjectId($projectIds);
            $allowedCiIds       = array();
            foreach ($allowedCiIdsResult as $aCiId) {
                $allowedCiIds[$aCiId['id']] = $aCiId['id'];
            }

            //add currently selected CI to allowed CI-ID's
            if (!empty($currentValues)) {
                foreach ($currentValues as $currentValue) {
                    $matches                    = explode('::', $currentValue);
                    $currentCiId                = $matches[0];
                    $allowedCiIds[$currentCiId] = $currentCiId;
                }
            }
        }


        if (!$autoComplete) {
            try {
                if ($ciId) {
                    $query = str_replace(':id:', $ciId, $query);
                }

                $query  = str_replace(':id:', '', $query);
                $query  = str_replace(':value:', '', $query);
                $result = $attributeDaoImpl->executeStatement($query);

            } catch (Exception $e) {
                $selection[null] = 'FEHLER';
                $result          = array();
                $this->logger->log($e, Zend_Log::ERR);
            }


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

                if ($k && $v && ($allowedCiIds === null || isset($allowedCiIds[strval($k)]))) {
                    $selection[$k] = $v;
                }
            }
        }

        if ($autoComplete && !$multiselect) {
            if (!empty($ciAttributeId)) {
                //get ci_attribute-row --> get current value_ci
                $ciAttributeRow = $attributeDaoImpl->getSingleCiAttributeById($ciAttributeId);
                $selection      = array($ciAttributeRow['value_ci'] => '');
            }
            if ($mode == 'edit') {
                $select = new Zend_Form_Element_Select($attributeName);
            } elseif ($mode == 'singleedit') {
                $select = new Zend_Form_Element_Select('value');
                $select->setAttrib("id", $attributeId);
                $select->setAttrib("class", 'autocomplete-value');
                $select->setAttrib('data-attributeid', $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID]);
                $select->setAttrib("autocomplete", true);
            }
            $select->addMultiOptions($selection);
            $select->setRegisterInArrayValidator(false);
            $jscripts = array();
            if (Zend_Registry::isRegistered('jsScripts')) {
                $jscripts = Zend_Registry::get('jsScripts');
            }

            if (!$jscripts['autocomplete'])
                $jscripts['autocomplete'] = array();

            $jscripts['autocomplete'][$attributeName] = $attributeId;
            Zend_Registry::set('jsScripts', $jscripts);
        } else {

            // SELECT -> option drop down
            if ($multiselect) {
                if (!empty($ciAttributeId)) {
                    $selection = array_flip($currentValues);
                    // remove "zombie"-entry if value in database is empty
                    if (isset($selection[''])) {
                        unset($selection['']);
                    }
                } else {
                    // no empty option for multiselect
                    $selection = array();
                }

                if ($mode == 'edit') {
                    $select = new Zend_Form_Element_Multiselect($attributeName);
                } elseif ($mode == 'singleedit') {
                    $select = new Zend_Form_Element_Multiselect('value');
                }
                $select->addMultiOptions($selection);
                $select->setAttrib('class', 'multiselect');
                $select->setAttrib('data-attributeid', $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID]);
                $select->setAttrib('data-ismultiselect', $multiselect);
                $jscripts = array();
                if (Zend_Registry::isRegistered('jsScripts')) {
                    $jscripts = Zend_Registry::get('jsScripts');
                }

                if (!$jscripts['multiselect'])
                    $jscripts['multiselect'] = array();

                $jscripts['multiselect'][$attributeName] = $attributeId;
                Zend_Registry::set('jsScripts', $jscripts);
                $select->setRegisterInArrayValidator(false);
                if ($cols < 700)
                    $cols = 700;

            } else {
                if ($mode == 'edit') {
                    $select = new Zend_Form_Element_Select($attributeName);
                } elseif ($mode == 'singleedit') {
                    $select = new Zend_Form_Element_Select('value');
                }
                $select->setMultiOptions($selection);
            }
        }

        $select->setLabel($attributeDescription);

        if ($notNull) {
            $select->setRequired(true);
            $select->setAutoInsertNotEmptyValidator(true);
        }

        if (!$write && $mode == 'edit') {
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

        $select->setValue($currentValues);
        return array($select);
    }


    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $data = array();

        if (is_array($currentVal)) {
            $data[Db_CiAttribute::VALUE_CI] = implode(', ', $currentVal);
        } else {
            $data[Db_CiAttribute::VALUE_CI] = $currentVal;
        }

        return $data;
    }

    /**
     * modifies the attribute value to be displayed
     *
     * @param array       $attribute
     * @param int         $ciId
     * @param string|null $path
     * @param bool        $list
     *
     * @return array
     */
    public function setAttributeValue($attribute, $ciId, $path = null, $list = false)
    {
        if (!isset($attribute[Db_CiAttribute::VALUE_CI])
            && isset($attribute[Db_CiAttribute::VALUE_TEXT])
            && $attribute[Db_CiAttribute::VALUE_TEXT] != '') {
            $attribute[Db_CiAttribute::VALUE_CI]   = implode(',', $attribute[Db_CiAttribute::VALUE_TEXT]);
            $attribute[Db_CiAttribute::VALUE_TEXT] = '';
        }

        if (isset($attribute[Db_Attribute::DISPLAY_STYLE]) && !empty($attribute[Db_Attribute::DISPLAY_STYLE])) {
            $displayStyle = $attribute[Db_Attribute::DISPLAY_STYLE];
        } else {
            $displayStyle = 'oneRow';
        }

        $dataSeparator = '';
        if ($displayStyle === 'oneRow') {
            $dataSeparator = ', ';
        }


        if ($attribute[Db_CiAttribute::VALUE_CI]) {
            if (!isset($this->result[$attribute[Db_CiAttribute::ID]])) {


                try {


                    $attributeDaoImpl = new Dao_Attribute();
                    $query            = $attributeDaoImpl->getDefaultQuery($attribute[Db_CiAttribute::ID]);
                    $query            = $query[Db_AttributeDefaultQueries::QUERY];

                    $selection = array();

                    if ($ciId) {
                        $query = str_replace(':id:', $ciId, $query);
                    }

                    $query        = str_replace(':id:', '', $query);
                    $query        = str_replace(':value:', '', $query);
                    $queryToCheck = trim($query);

                    $result = array();
                    if (!empty($queryToCheck)) {
                        $result = $attributeDaoImpl->executeStatement($query);
                    }

                    $this->result[$attribute[Db_CiAttribute::ID]] = $result;

                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                }


                if (is_array($this->result[$attribute[Db_CiAttribute::ID]])) {

                    foreach ($this->result[$attribute[Db_CiAttribute::ID]] as $key => $res) {
                        $k = null;
                        $v = null;
                        foreach ($res as $attr) {
                            if (!$k) {
                                $k = $attr;
                            } else {
                                $v = $attr;
                            }
                        }

                        if ($k && $v)
                            $selection[$k] = $v;
                    }

                }

                $this->selection[$attribute[Db_CiAttribute::ID]] = $selection;


            }


            $CI = new Dao_Ci();


            $actuals = explode(',', $attribute[Db_CiAttribute::VALUE_CI]);


            $attribute[Db_CiAttribute::VALUE_CI] = null;

            if (!isset($attribute[Db_CiAttribute::VALUE_TEXT])) {
                $attribute[Db_CiAttribute::VALUE_TEXT] = null;
            }

            $displayElements = array();

            foreach ($actuals as $actual) {
                $actual      = trim($actual);
                $valueParts  = explode('::', $actual);
                $valueCiId   = $valueParts[0];
                $valueAmount = null;
                if (isset($valueParts[1])) {
                    $valueAmount = $valueParts[1];
                }

                if (!$list) {
                    $ci = $CI->getCi($valueCiId);
                }

                $displayAmount = '';
                if (!is_null($valueAmount)) {
                    $displayAmount = '<span class="counterLabel">' . $valueAmount . '</span>&nbsp;';
                }

                $displayKey  = $this->selection[$attribute[Db_CiAttribute::ID]][trim($valueCiId)] . '__' . $valueCiId;
                $displayText = $this->selection[$attribute[Db_CiAttribute::ID]][$valueCiId];


                if ($displayStyle === 'ciListExport' && !is_null($valueAmount)) {
                    $displayElements[$displayKey] = '"' . $displayText . '":' . $valueAmount;
                } elseif ($displayStyle !== 'ciListExport' && !$list && isset($ci) && $ci != "") {
                    $displayElements[$displayKey] = $displayAmount . '<a href="' . APPLICATION_URL . 'ci/detail/ciid/' . $valueCiId . '">' . $displayText . '</a>';
                } else {
                    $displayElements[$displayKey] = $displayAmount . $displayText;
                }

            }

            ksort($displayElements);

            if ($displayStyle === 'ciListExport') {
                $attribute[Db_CiAttribute::VALUE_TEXT] = implode($displayElements, ", ");
            } else {
                $attribute[Db_CiAttribute::VALUE_TEXT] = '<ul class="' . $displayStyle . '"><li data-separator="' . $dataSeparator . '">' . implode($displayElements, '</li><li data-separator="' . $dataSeparator . '">') . '</ul>';
            }


        }

        $attribute['noEscape'] = true;


        return $attribute;
    }


    public function returnFormData($values, $attributeId = null)
    {
        $data = array();

        if (is_array($values['value'])) {
            $data[Db_CiAttribute::VALUE_CI] = implode(', ', $values['value']);
        } else {
            $data[Db_CiAttribute::VALUE_CI] = $values['value'];
        }


        return $data;
    }

    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        if (is_array($currentVal)) {
            $currentVal = implode(', ', $currentVal);
        }

        return array(
            'value' => trim($currentVal),
        );
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key        = $attribute['genId'];
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        if (is_array($currentVal)) {
            $currentVal = implode(', ', $currentVal);
        }

        if ($attribute['value'] == 'text' && $currentVal == '0') {
            return null;
        }

        $data                           = array();
        $data[Db_CiAttribute::VALUE_CI] = $currentVal;
        return $data;
    }


    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_CI];

        $valueArray = explode(',', $data);

        // just to make sure...
        foreach ($valueArray as $key => $value) {
            $value            = trim($value);
            $valueArray[$key] = $value;
        }

        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId']]            = $valueArray;
        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId'] . 'hidden'] = $values[$storedIDs[0]]['ciAttributeId'];


        return $formData;
    }
}