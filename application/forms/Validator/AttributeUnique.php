<?php
require_once 'Zend/Validate/Abstract.php';

/**
 * Validator for ensuring that the values of an attributes are unique
 */
class Form_Validator_AttributeUnique extends Zend_Validate_Abstract
{

    protected $attributeID;

    /**
     * Validator Constructor
     * @param integer $attributeID id of attribute to check for unique attribute values
     */
    public function __construct($attributeID)
    {
        $this->attributeID = $attributeID;
    }

    const MESSAGE = "notUniqueAttribute";

    protected $_messageTemplates = array(
        self::MESSAGE => "notUniqueAttribute" // key of i18n
    );

    /**
     * Returns false if validation enabled ($value) and attribute values are not unique. True otherwise.
     * @param string $value html form checkbox value
     * @return boolean true if validation is successful and false if validation failed
     */
    public function isValid($value)
    {
        // only check unique values in edit mode
        if (!empty($this->attributeID)) {

            // only check unique values if checkbox is checked
            if ($value === "1") {
                $daoAttribute = new Dao_Attribute();
                $isUnique     = $daoAttribute->checkAttributeValuesUnique($this->attributeID);

                // add validation error
                if ($isUnique === false) {
                    $this->_error(self::MESSAGE);
                }

                return $isUnique;
            }
        }

        return true;
    }
}