<?php

class Util_AttributeType_Type_QueryPersist extends Util_AttributeType_Type_Abstract
{


    const ATTRIBUTE_TYPE_ID = 18;
    const ALLOW_EMPTY = true;

    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        $placeholders = array(
            ':id:' => $translator->translate('attributeHintIndividualQueryCurrentCiId'),
        );

        $form = new Form_Attribute_IndividualQueryPersist($translator, array(
            'placeholders' => $placeholders,
        ));

        return $form;
    }

    public static function execute_query($ciId, $attribute, $historyid)
    {
        foreach ($attribute as $attr) {

            $attributeDaoImpl = new Dao_Attribute();
            $query            = $attributeDaoImpl->getDefaultQuery($attr['attribute_id']);

            $query = $query[Db_AttributeDefaultQueries::QUERY];
            $query = str_replace(':id:', $ciId, $query, $replaceCount);

            $result = "";
            if ($replaceCount > 0 && !is_numeric($ciId)) {
                Zend_Registry::get('Log')->log(sprintf('Attribute[%s] requires CI-ID to be given!', $attribute[Db_CiAttribute::NAME]), Zend_Log::DEBUG);
            } else {
                $sel = $attributeDaoImpl->getValuesBySqlInjection($query);
                foreach ($sel as $s) {
                    $isFirst = true;
                    foreach ($s as $atb) {
                        if (!$isFirst) {
                            $result .= ", ";
                        } else {
                            $isFirst = false;
                        }

                        $result .= $atb;
                    }

                }


                $data[Db_CiAttribute::HISTORY_ID] = $historyid;
                $data[Db_CiAttribute::VALUE_TEXT] = $result;
                #print_r($attr); die;
                if (empty($attr[Db_CiAttribute::ID])) {
                    //if no id -> create ci-attribute-entry
                    $data[Db_CiAttribute::CI_ID]        = $ciId;
                    $data[Db_CiAttribute::ATTRIBUTE_ID] = $attr['attribute_id'];
                    $data[Db_CiAttribute::IS_INITIAL]   = '1';
                    $attributeDaoImpl->insertCiAttribute($data);
                } else {
                    //else update ci-attribute-entry
                    $attributeDaoImpl->updateCiAttribute($attr[Db_CiAttribute::ID], $data);
                }
            }
            $attributeDaoImpl = null;

        }
    }

    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        $attribute['noEscape'] = true;
        return $attribute;
    }


    // TODO: use script somehow?
    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attribute[Db_Attribute::ID]);

        $query = $query[Db_AttributeDefaultQueries::QUERY];
        $query = str_replace(':id:', $ciId, $query, $replaceCount);


        $result = "";

        try {

            if (strpos($query, ':script#:') === false) {

                if ($replaceCount > 0 && !is_numeric($ciId)) {
                    $this->logger->log(sprintf('Attribute[%s] requires CI-ID to be given!', $attribute[Db_CiAttribute::NAME]), Zend_Log::DEBUG);
                } else {
                    $sel = $attributeDaoImpl->getValuesBySqlInjection($query);
                    foreach ($sel as $s) {
                        $isFirst = true;
                        foreach ($s as $atb) {
                            if (!$isFirst) {
                                $result .= ", ";
                            } else {
                                $isFirst = false;
                            }
                            $result .= $atb;
                        }
                    }
                }
            } else {
                throw new Exception(sprintf("getCurrentAttributeValue (attribute %d): scripts not supported", $attribute[Db_Attribute::ID]));
            }
        } catch (Exception $e) {
            $result = "Query failed";
            $this->logger->log($e, Zend_Log::CRIT);
        }


        return array(
            'value'      => $result,
            'allowEmpty' => self::ALLOW_EMPTY,
        );
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

        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attributeId);
        $query            = $query[Db_AttributeDefaultQueries::QUERY];

        if ($ciId) {
            $query = str_replace(':id:', $ciId, $query);
        }
        $select = new Zend_Form_Element_Text($attributeName);
        $select->setLabel($attributeDescription);
        //$select->setValue($query);

        if ($maxLength)
            $select->setAttrib('maxlength', $maxLength);

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
        $query            = $attributeDaoImpl->getDefaultQuery($values[Db_CiAttribute::ATTRIBUTE_ID]);

        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = $query[Db_AttributeDefaultQueries::QUERY];
        return $data;
    }


    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
//		$attributeDaoImpl = new Dao_Attribute();
//		$query = $attributeDaoImpl->getDefaultQuery($attribute[Db_Attribute::ID]);
//
//		$ciId = $attribute['ci_id'];
//		$query = $query[0]['value'];
//
//		if($ciId) {
//			$query = str_replace(':id:', $ciId, $query);
//		}

        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId']]            = $values[$storedIDs[0]][Db_CiAttribute::VALUE_TEXT];
        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId'] . 'hidden'] = $values[$storedIDs[0]]['ciAttributeId'];
        return $formData;
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key = $attribute['genId'];

        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attribute[Db_Attribute::ID]);

        $query = $query[Db_AttributeDefaultQueries::QUERY];
        $query = str_replace(':id:', $ciId, $query);

        $result = "";

        try {
            if (strpos($query, ':script#:') === false) {
                $sel = $attributeDaoImpl->getValuesBySqlInjection($query);

                foreach ($sel as $s) {
                    $isFirst = true;
                    foreach ($s as $atb) {
                        if (!$isFirst) {
                            $result .= ", ";
                        } else {
                            $isFirst = false;
                        }

                        $result .= $atb;
                    }
                }
            } else {
                throw new Exception(sprintf("addCi (attribute %d): scripts not supported", $attribute[Db_Attribute::ID]));
            }
        } catch (Exception $e) {
            $result = "Query failed";
        }

        $currentVal = $result;


        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = $currentVal;
        return $data;
    }

    public function isEqual($oldArray, $newArray)
    {

        return true;

    }


}