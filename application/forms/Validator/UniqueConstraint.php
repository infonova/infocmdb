<?php

class Form_Validator_UniqueConstraint extends Zend_Validate_Abstract
{
    const NOT_UNIQUE = 'notUnique';

    protected $_messageTemplates = array(
        self::NOT_UNIQUE => 'notunique',
    );


    private $attributeId;
    private $ciid;

    public function __construct($attributeId = null, $ciid = null)
    {
        $this->attributeId = $attributeId;
        $this->ciid        = $ciid;


    }

    public function isValid($value, $context = null)
    {

        $value = (string)$value;
        $this->_setValue($value);

        $ciDaoImpl = new Dao_Ci();

        $count = 0;
        $value = trim($value);
        if ($this->ciid) {
            // is update
            $count = $ciDaoImpl->checkUniqueUpdate($value, $this->ciid, $this->attributeId);
        } else {
            // select for database
            $count = $ciDaoImpl->checkUnique($value, $this->attributeId);
        }

        if ($count['cnt'] > 0) {
            $this->_error(self::NOT_UNIQUE);
            return false;
        } else {
            return true;
        }
    }
}