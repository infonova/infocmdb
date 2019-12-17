<?php

class Util_AttributeType_Type_Query extends Util_AttributeType_Type_Abstract
{


    const ATTRIBUTE_TYPE_ID = 15;
    const ALLOW_EMPTY = true;

    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        $placeholders = array(
            ':id:' => $translator->translate('attributeHintIndividualQueryCurrentCiId'),
        );

        $form = new Form_Attribute_IndividualQuery($translator, array(
            'placeholders' => $placeholders,
        ));

        return $form;
    }

    // since mysql select uses 4th parameter for list, we have to skip it here
    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        if ($this->isHistoryView) {
            $text                                  = $this->createTranslator()->getAdapter()->translate('queryHistoryView');
            $attribute['noEscape']                 = true;
            $attribute[Db_CiAttribute::VALUE_TEXT] = "<span class='type-not-supported-text'>" . $text . "</span>";
            return $attribute;
        }

        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attribute[Db_Attribute::ID]);
        $query            = $query[Db_AttributeDefaultQueries::QUERY];
        $query            = str_replace(':id:', $ciId, $query, $replaceCount);

        $result = "";

        if ($replaceCount > 0 && !is_numeric($ciId)) {
            $this->logger->log(sprintf('Attribute[%s] requires CI-ID to be given!', $attribute[Db_CiAttribute::NAME]), Zend_Log::DEBUG);
        } else {
            try {
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
            } catch (Exception $e) {
                $result = "Query failed";
                $this->logger->log($e, Zend_Log::WARN);
            }
        }

        if ($attribute[Db_Attribute::IS_EVENT]) {

            $light = explode(", ", $result);
            if (ctype_xdigit($light[0]) && strlen($light[0]) == 6) {
                $result = '<div data-id="' . htmlentities($light[1]) . '" align="center" class="light" style="background:#' . $light[0] . '">' . $light[1] . '</div>';
            } else {
                $result = 'color not valid';
            }
        }

        $attribute[Db_CiAttribute::VALUE_TEXT] = $result;
        //SQL Values are not escaped !
        $attribute['noEscape'] = true;
        return $attribute;
    }


    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        return array(
            'value'      => " ",
            'allowEmpty' => self::ALLOW_EMPTY,
            'skipUpdate' => true,
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


        $selection        = array();
        $selection[0]     = "";
        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attributeId);
        $query            = $query[Db_AttributeDefaultQueries::QUERY];

        if ($ciId) {
            $query = str_replace(':id:', $ciId, $query);
        }
        $select = new Zend_Form_Element_Hidden($attributeName);
//		$select->setLabel($attributeDescription);
        $select->setValue($query);

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
        $attributeDaoImpl = new Dao_Attribute();
        $query            = $attributeDaoImpl->getDefaultQuery($attribute[Db_Attribute::ID]);

        $ciId  = $attribute['ci_id'];
        $query = $query[Db_AttributeDefaultQueries::QUERY];

        if ($ciId) {
            $query = str_replace(':id:', $ciId, $query);
        }

        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId']]            = $query;
        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId'] . 'hidden'] = $values[$storedIDs[0]]['ciAttributeId'];
        return $formData;
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key        = $attribute['genId'];
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        if ($attribute['value'] == 'text' && $currentVal == '0') {
            $currentVal = "";
        }

        $data = array();
        return $data;
    }

    public function getString(&$form, $attribute)
    {
        if (!($attribute[Db_AttributeRole::PERMISSION_READ] === '1' || $attribute[Db_AttributeRole::PERMISSION_WRITE] === '1')) {
            return "";
        }


        $varName = $attribute['name'] . $attribute['genId'];


        if ($form->$varName) {

            //Admin-Mode: show attribute-name
            if (Zend_Registry::get('adminMode') === true) {
                $attribute['note'] = $attribute['description'];
                $link              = "<a href='" . APPLICATION_URL . "attribute/edit/attributeId/" . $attribute['id'] . "'><img class='image' src='" . APPLICATION_URL . "images/navigation/settings.png'></a>";
                $form->$varName->setLabel($link . ' ' . $attribute['name']);
            }


            $val = $this->setAttributeValue($attribute, $form->getValue('ciid'));

            $class = "";


            $retString = "<td>
							<label title=\"" . htmlspecialchars($attribute['note']) . "\" class='" . $class . "'>" . $form->$varName->getLabel() . "</label>
						</td>
						<td>" . $val[Db_CiAttribute::VALUE_TEXT];

            if ($attribute['hint']) {
                $retString .= $form->$varName->setDecorators(array(new Form_Decorator_MyTooltip()));
            }
            $retString .= '</td>';

            return $retString;
        }


        return "";
    }

}
