<?php

class Util_CiType
{

    public $daoAttribute;
    public $daoCi;
    public $serviceCiGet;

    protected $_cache;


    public function __construct()
    {
        $this->_cache = new Util_SimpleCache();

        $this->daoAttribute = new Dao_Attribute();
        $this->daoCi        = new Dao_Ci();
        $this->serviceCiGet = new Service_Ci_Get(null, null, 0);
    }

    public function getSelectionForFormElement($attributeId, $filter = null, $allowHierarchy = true, $ciId = null, $userId = null)
    {


        $ciDaoImpl         = new Dao_Ci();
        $attributeDaoImpl  = new Dao_Attribute();
        $attributeOriginal = $attributeDaoImpl->getAttribute($attributeId);

        $newAttribute                   = array();
        $newAttribute[Db_Attribute::ID] = $attributeOriginal[Db_Attribute::ID];

        $attribute       = $newAttribute;
        $selection       = array();
        $selection[null] = "";
        $ciAttributes    = array();

        $ciTypes = $attributeDaoImpl->getDefaultCiType($attributeId);

        $filter = strtolower($filter);
        $filter = preg_quote($filter);
        $filter = str_replace('\*', '.*', $filter); #enable wildcard


        if (count($ciTypes) > 1) {
            // TODO: handle joins.. but how??

            // call procedure and select result from temp table!
        } else {
            // is a single citype!
            $ciType     = $ciTypes[0];
            $attributes = $attributeDaoImpl->getDefaultCiTypeAttributes($ciType[Db_AttributeDefaultCitype::ID]);

            $currentValueCi = '';
            if (!empty($ciId)) {
                $currentValueCi = $attributeDaoImpl->getCurrentCiTypeValue($ciId, $attributeId);
                $currentValueCi = $currentValueCi[0][Db_CiAttribute::VALUE_CI];
            }

            // create list of allowed CI-ID's if attribute is project-restricted
            $allowedCiIds = null;
            if ($attributeOriginal[Db_Attribute::IS_PROJECT_RESTRICTED] === '1' && !empty($userId)) {
                $projectDaoImpl = new Dao_Project();
                $projectList    = $projectDaoImpl->getProjectsByUserId($userId);

                $projectIds = null;
                foreach ($projectList as $p) {
                    $projectIds[] = $p[Db_Project::ID];
                }
                $allowedCiIdsResult = $ciDaoImpl->getCiListByCiTypeId($ciType[Db_AttributeDefaultCitype::CI_TYPE_ID], $projectIds);
                $allowedCiIds       = array();
                foreach ($allowedCiIdsResult as $aCiId) {
                    $allowedCiIds[$aCiId['id']] = $aCiId['id'];
                }

                //add currently selected CI to allowed CI-ID's
                if (!empty($currentValueCi) && !isset($allowedCiIds[$currentValueCi])) {
                    $allowedCiIds[$currentValueCi] = $currentValueCi;
                }
            }

            $attributeIdList = null;
            $conditions      = array();
            $orderBy         = array();
            foreach ($attributes as $al) {
                if ($attributeIdList !== null)
                    $attributeIdList .= ', ';

                $attributeIdList .= $al[Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_ID];

                //ci_attribute.attribute_id=xy DESC, ci_attribute.attribute_id=yz DESC --> order by order_number defined in attribute_default_citype_attributes --> handled in getDefaultCiTypeAttributes
                $orderBy[] = new Zend_Db_Expr(Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . "=" . $al['attribute_id'] . " DESC");

                if ($al[Db_AttributeDefaultCitypeAttributes::CONDITION]) {
                    $conditions[$al[Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_ID]] = $al[Db_AttributeDefaultCitypeAttributes::CONDITION];
                }
            }

            //without attributes no result --> return empty array
            if (!$attributeIdList) {
                return array();
            }

            $orderBy[]    = Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . " ASC";
            $ciAttributes = $attributeDaoImpl->getCiAttributesByCiTypeId($ciType[Db_AttributeDefaultCitype::CI_TYPE_ID], $attributeIdList, $conditions, 0, $orderBy);
            $tempSel      = array();

            foreach ($ciAttributes as $key => $row) {
                $valueText = $row[Db_CiAttribute::VALUE_TEXT] . $row[Db_CiAttribute::VALUE_DEFAULT];

                if ($row['attributeType'] == Enum_AttributeType::CI_TYPE || $row['attributeType'] == Enum_AttributeType::CI_TYPE_PERSIST) {
                    $valueText = $this->getCiTypeValueToDisplay($row['attributeId'], $row[Db_CiAttribute::VALUE_CI]);
                }

                if ($valueText && $valueText != ' ') {
                    //only add to result if attribute is not project-restricted or user is allowed to see the CI!
                    if ($allowedCiIds === null || isset($allowedCiIds[$row[Db_Ci::ID]])) {
                        if (!isset($tempSel[$row[Db_Ci::ID]])) {
                            $tempSel[$row[Db_Ci::ID]] = array();
                        }

                        $tempSel[$row[Db_Ci::ID]][$row['attributeId']] = $valueText;
                    }

                }
            }

            foreach ($tempSel as $ciKey => $valArray) {
                $orderString = null;


                foreach ($attributes as $orderAtt) {
                    $orderAttId = $orderAtt[Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_ID];
                    $newValue   = $valArray[$orderAttId];
                    if ($newValue) {
                        if ($orderString)
                            $orderString .= ', ';

                        $orderString .= $newValue;
                    }
                }


                $tempSel[$ciKey] = $orderString;
                $orderString     = strtolower($orderString);


                if ($filter) {
                    if (!preg_match('/.*' . $filter . '.*/', $orderString)) { // .* = 0 or more chars
                        unset($tempSel[$ciKey]);
                    }
                }
            }

            //move currently selected item to first position --> don't loose current value if autocomplete gets triggered --> result limit
            if (!empty($currentValueCi)) {
                if (!empty($currentValueCi) && isset($tempSel[$currentValueCi])) {
                    $currentArrayValue = $tempSel[$currentValueCi];
                    unset($tempSel[$currentValueCi]);
                    $tempSel = array($currentValueCi => $currentArrayValue) + $tempSel;
                }
            }


            if ($filter || !$allowHierarchy) {
                return $tempSel;
            }

            //merge empty value from $selection with result $tempSel (without loosing keys!)
            $selection = $selection + $tempSel;
            return $selection;

        }

        return $selection;
    }

    /*
     * @param $ciAttribute - mix of attribute and ci_attribute row
     */
    public function getCiTypeValueToDisplay($attributeId, $ciAttributeValueCi, $context = "row")
    {
        // skip logic if there is no ci-ID given
        if (empty($ciAttributeValueCi)) {
            return '';
        }

        $cacheKey = $attributeId . '__' . $ciAttributeValueCi;

        $cachedValue = $this->_cache->get('ciTypeValueToDisplay', $cacheKey);
        if ($cachedValue) {
            return $cachedValue;
        }

        // Init Base Classes
        $ciServiceGet = $this->serviceCiGet;

        $ci = $this->daoCi->getCi($ciAttributeValueCi);
        if (!is_array($ci)) { // if ci doesn't exist - e.g. deleted
            $this->_cache->set('ciTypeValueToDisplay', $cacheKey, '');
            return '';
        }
        $ciTypeId               = $ci[Db_Ci::CI_TYPE_ID];
        $attributeDefaultCiType = $this->getDefaultCiType($attributeId, $ciTypeId); // find configuration for given attribute and ci-type
        if (isset($attributeDefaultCiType[0])) { // only one row possible --> get first row
            $attributeDefaultCiType = $attributeDefaultCiType[0];
        } else {
            return '';
        }

        // get configured attributes ordered by order_number column
        $foreignAttributes = $this->getDefaultCiTypeAttributes($attributeDefaultCiType[Db_AttributeDefaultCitype::ID]);

        // transform rows into list of attribute-id's
        $foreignAttributeList = array();
        foreach ($foreignAttributes as $foreignAttribute) {
            $foreignAttributeList[] = $this->getAttribute($foreignAttribute[Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_ID]);
        }

        if ($context === 'row') {
            $ciList = array(array('id' => $ciAttributeValueCi));
        } else { // list - fetch all data at once
            $ciList = $this->daoCi->getCiListForCiIndex($ciTypeId);
        }

        // resolve attribute values
        $rows = $ciServiceGet->getListResultForCiList($foreignAttributeList, $ciList);

        foreach ($rows as $row) {
            $cacheKeyToSave = $attributeId . '__' . $row[Db_Ci::ID];

            // resolving attributes overrules ordering --> reorder
            $values = '';
            foreach ($foreignAttributeList as $foreignAttribute) {
                if (isset($row[$foreignAttribute[Db_Attribute::NAME]]) && $row[$foreignAttribute[Db_Attribute::NAME]] != '') {
                    if ($values !== '') {
                        $values .= ', ';
                    }
                    $values .= $row[$foreignAttribute[Db_Attribute::NAME]];
                }
            }

            $this->_cache->set('ciTypeValueToDisplay', $cacheKeyToSave, $values);
        }

        return $this->_cache->get('ciTypeValueToDisplay', $cacheKey);
    }


    public function getDefaultCiType($attributeId, $ciTypeId = null)
    {
        $cacheKey = $attributeId . '__' . $ciTypeId;
        $value    = $this->_cache->get('attributeDefaultCiTypes', $cacheKey);
        if ($value === false) {
            $value = $this->daoAttribute->getDefaultCiType($attributeId, $ciTypeId);
            $this->_cache->set('attributeDefaultCiTypes', $cacheKey, $value);
        }

        return $value;
    }

    public function getAttribute($id)
    {
        $cacheKey = $id;
        $value    = $this->_cache->get('attributes', $cacheKey);
        if ($value === false) {
            $value = $this->daoAttribute->getAttribute($id);
            $this->_cache->set('attributes', $cacheKey, $value);
        }

        return $value;
    }

    public function getDefaultCiTypeAttributes($id)
    {
        $cacheKey = $id;
        $value    = $this->_cache->get('attributeDefaultCitypeAttributes', $cacheKey);
        if ($value === false) {
            $value = $this->daoAttribute->getDefaultCiTypeAttributes($id);
            $this->_cache->set('attributeDefaultCitypeAttributes', $cacheKey, $value);
        }

        return $value;
    }
}
