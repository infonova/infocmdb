<?php

abstract class Util_AttributeType_Type_Abstract
{

    protected $_formElements;
    protected $_ciAttribute;
    protected $_attributeId;
    protected $logger;
    protected $isHistoryView = false;

    const ATTRIBUTE_VALUES_ID = 'text';
    const ATTRIBUTE_TYPE_ID   = 1;
    const ALLOW_EMPTY = false;


    public function __construct(int $uniqueId = null, int $attributeId = null)
    {
        if ($attributeId) {
            $this->_attributeId = $attributeId;
        }

        $this->logger = Zend_Registry::get('Log');
    }

    /**
     * @return Zend_Translate
     */
    public function createTranslator()
    {
        return Zend_Registry::get('Zend_Translate');
    }

    /**
     * @return Zend_Form_SubForm return individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        return;
    }


    public function getAutocompleteSelection(int $attributeId, $filter, int $ciId, int $userId)
    {
        return array();
    }

    public function getAutocompleteValue($attributeId, $ciId)
    {
        return null;
    }

    /**
     * modifies the attribute value to be displayed
     *
     * @param unknown_type $attribute
     * @param unknown_type $path
     */
    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        return $attribute;
    }


    /**
     * returns all form elements for Single-Update Action
     *
     * @param $ciAttribute
     */
    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {
        $hint    = (isset($ciAttribute[Db_Attribute::HINT])) ? $ciAttribute[Db_Attribute::HINT] : null;
        $ar      = self::getFormElements($ciAttribute, null, null);
        $element = $ar[0];
        $element->setValue($ciAttribute[Db_CiAttribute::VALUE_TEXT]);
        if ($hint) {
            $element->setDescription($this->prepareHintForTooltip($hint));
        }
        return array($element);
    }


    /**
     * returns all form elements for CI create and update
     * no values are set
     *
     * @param $ciAttribute
     */
    public function getFormElements($ciAttribute, $key = null, $ciId = null, $isValidate = false, $userId = null)
    {
        $hint    = (isset($ciAttribute[Db_Attribute::HINT])) ? $ciAttribute[Db_Attribute::HINT] : null;
        $element = new Zend_Form_Element_Text('value');
        $element->setLabel($ciAttribute[Db_Attribute::DESCRIPTION]);

        if (isset($ciAttribute[Db_Attribute::INPUT_MAXLENGTH]))
            $element->setAttrib('maxlength', $ciAttribute[Db_Attribute::INPUT_MAXLENGTH]);

        if ($ciAttribute[Db_Attribute::TEXTAREA_COLS])
            $element->setAttrib('size', $ciAttribute[Db_Attribute::TEXTAREA_COLS]);
        $element->setDecorators(array('ViewHelper', 'Errors'));
        if ($hint) {
            $element->setDescription($this->prepareHintForTooltip($hint));
        }
        return array($element);
    }


    /**
     * prepare ci attribute value for single edit
     *
     * @param unknown_type $values
     */
    public function returnFormData($values, $attributeId = null)
    {
        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = trim($values['value']);
        return $data;
    }


    /**
     * add ci_attribute values to formdata. Used for ci edit
     *
     * @param array $formData
     * @param Util_AttributeType_Type_Abstract $attribute
     * @param array $values
     * @param array $storedIDs
     */
    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        $data = null;

        if (isset($values[$storedIDs[0]][Db_CiAttribute::VALUE_TEXT])) {
            $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_TEXT];
        } else if (isset($values[$storedIDs[0]][Db_CiAttribute::VALUE_DATE])) {
            $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_DATE];
        } else if (isset($values[$storedIDs[0]][Db_CiAttribute::VALUE_DEFAULT])) {
            $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_DEFAULT];
        } else if (isset($values[$storedIDs[0]][Db_CiAttribute::VALUE_CI])) {
            $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_CI];
        }

        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId']]            = $data;
        $formData[$attribute[Db_Attribute::NAME] . $attribute['genId'] . 'hidden'] = $values[$storedIDs[0]]['ciAttributeId'];
        return $formData;
    }

    public function getValueByCiAttribute($ciAttribute, $nl2br = false)
    {
        $return = '';
        if (isset($ciAttribute[Db_CiAttribute::VALUE_TEXT])) {
            $return = $ciAttribute[Db_CiAttribute::VALUE_TEXT];
        } else if (isset($ciAttribute[Db_CiAttribute::VALUE_DATE])) {
            $return = $ciAttribute[Db_CiAttribute::VALUE_DATE];
            $nl2br  = false;
        } else if (isset($ciAttribute[Db_CiAttribute::VALUE_DEFAULT])) {
            $return = $ciAttribute[Db_CiAttribute::VALUE_DEFAULT];
        } else if (isset($ciAttribute[Db_CiAttribute::VALUE_CI])) {
            $return = $ciAttribute[Db_CiAttribute::VALUE_CI];
        }

        if ($nl2br === true) {
            $return = nl2br($return);
        }

        return $return;
    }


    /**
     * retrieve ci attribute value
     *
     * @param unknown_type $values
     * @param unknown_type $attribute
     */
    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        return array(
            'value' => trim($currentVal),
        );
    }


    /**
     * retrieve Data to update (for ci edit)
     *
     * @param unknown_type $values
     * @param unknown_type $attribute
     * @param unknown_type $key
     * @param unknown_type $currentVal
     */
    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = trim($currentVal);
        return $data;
    }


    public function isEqual($oldArray, $newArray)
    {

        $oldArray['value_text'] = (string)$oldArray['value_text'];
        if (isset($newArray['value_text'])) {
            $newArray['value_text'] == (string)$newArray['value_text'];
        }

        if (is_array($newArray)) {

            if ((isset($newArray[Db_CiAttribute::VALUE_TEXT]) && $oldArray[Db_CiAttribute::VALUE_TEXT] !== $newArray[Db_CiAttribute::VALUE_TEXT])
                || (isset($newArray[Db_CiAttribute::VALUE_DEFAULT]) && $oldArray[Db_CiAttribute::VALUE_DEFAULT] !== $newArray[Db_CiAttribute::VALUE_DEFAULT])
                || (isset($newArray[Db_CiAttribute::VALUE_DATE]) && $oldArray[Db_CiAttribute::VALUE_DATE] !== $newArray[Db_CiAttribute::VALUE_DATE])
                || (isset($newArray[Db_CiAttribute::VALUE_CI]) && $oldArray[Db_CiAttribute::VALUE_CI] !== $newArray[Db_CiAttribute::VALUE_CI])
                || (isset($newArray[Db_CiAttribute::NOTE]) && $oldArray[Db_CiAttribute::NOTE] !== $newArray[Db_CiAttribute::NOTE])
            ) {
                return false;
            }

            return true;

        } else {

            if (isset($newArray))
                return false;

            if ((!isset($oldArray[Db_CiAttribute::VALUE_CI]) && $oldArray[Db_CiAttribute::VALUE_CI] !== $newArray)
                || (!isset($oldArray[Db_CiAttribute::VALUE_DATE]) && $oldArray[Db_CiAttribute::VALUE_DATE] !== $newArray)
                || (!isset($oldArray[Db_CiAttribute::VALUE_DEFAULT]) && $oldArray[Db_CiAttribute::VALUE_DEFAULT] !== $newArray)
                || (!isset($oldArray[Db_CiAttribute::VALUE_TEXT]) && $oldArray[Db_CiAttribute::VALUE_TEXT] !== $newArray)
            ) {
                return false;
            }

            return true;
        }

    }

    /**
     * generates content to add a new ci attribute
     *
     * @param unknown_type $values
     * @param unknown_type $attribute
     * @param unknown_type $ciId
     */
    public function addCi($values, $attribute, $ciId)
    {
        $key = $attribute['genId'];

        if (isset($values[$attribute[Db_Attribute::NAME] . $key])) {
            $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];
        } else {
            return null;
        }

        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = trim($currentVal);

        return $data;
    }


    public function normalizeValue($attributeId, $value)
    {

        return $value;
    }


    /**
     * check if option is used
     *
     * @param unknown_type $optionId
     * @param unknown_type $attributeId
     */
    public function isOptionUsed($optionId, $attributeId)
    {
        $attributeDaoImpl = new Dao_Attribute();
        if ($attributeDaoImpl->getCiAttributeUsingAttributeDefaultValue($optionId, $attributeId))
            return true;
        else
            return false;
    }


    public function getString(&$form, $attribute)
    {
        $varName = $attribute['name'] . $attribute['genId'];

        //Admin-Mode: show attribute-name
        if ($form->$varName && Zend_Registry::get('adminMode') === true) {
            $attribute['note'] = $attribute['description'];
            $link              = "<a href='" . APPLICATION_URL . "attribute/edit/attributeId/" . $attribute['id'] . "'><img class='image' src='" . APPLICATION_URL . "images/navigation/settings.png'></a>";
            $form->$varName->setLabel($link . ' ' . $attribute['name']);
        }


        //if attribute is writable and not fixed info
        if ($form->$varName && $attribute[Db_AttributeRole::PERMISSION_WRITE] && $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] != Util_AttributeType_Type_Info::ATTRIBUTE_TYPE_ID) {


            $class = "";

            if ($form->$varName->isRequired()) {
                $class = "required";
            }

            $retString = "<td>
							<label title=\"" . htmlspecialchars($attribute['note']) . "\" class='" . $class . "'>" . $form->$varName->getLabel() . "</label>
						</td>
						<td>" . $form->$varName->setDecorators(array('Errors', array('ViewHelper', array('tag' => 'div'))));

            if ($attribute['hint']) {
                $retString .= $form->$varName->setDecorators(array(new Form_Decorator_MyTooltip()));
            }
            $retString .= '</td>';


            return $retString;

        } elseif (($form->$varName && $attribute[Db_AttributeRole::PERMISSION_READ]) || $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_Info::ATTRIBUTE_TYPE_ID) { //if attribute is readable or fixed info


            $ciId = $form->ciid->getValue();

            $attribute[Db_CiAttribute::VALUE_TEXT] = $form->$varName->getValue();

            $retArray = $this->setAttributeValue($attribute, $ciId);


            $class = "";

            if ($form->$varName->isRequired()) {
                $class = "required";
            }

            $retString = "<td>
							<label title=\"" . htmlspecialchars($attribute['note']) . "\" class='" . $class . "'>" . $form->$varName->getLabel() . "</label>
						</td>
						<td>" . $retArray[Db_CiAttribute::VALUE_TEXT];

            if ($attribute['hint']) {
                $retString .= $form->$varName->setDecorators(array(new Form_Decorator_MyTooltip()));
            }
            $retString .= '</td>';


            return $retString;


        }


        return "";
    }

    public function prepareHintForTooltip($hint)
    {
        $hint = addslashes( // escape for JS
            htmlspecialchars( // escape for Tooltip
                trim( // remove whitespaces at beginning and end
                    preg_replace('/\s\s+/', '', $hint) // remove new-lines to don't crash JS
                )
            )
        );
        return $hint;
    }

    public function setHistoryView($value)
    {
        $this->isHistoryView = $value;
    }
}