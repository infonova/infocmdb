<?php

class Form_Validator_AllowCiAttach extends Zend_Validate_Abstract
{
    const NOT_ALLOWED = 'ciAttachNotAllowed';

    protected $_messageTemplates = array(
        self::NOT_ALLOWED => 'CI Attach is not allowed for the given CI Type',
    );

    public function isValid($value, $context = null)
    {
        $value = (string)$value;
        $this->_setValue($value);

        // select for database
        $ciDaoImpl = new Dao_Ci();
        $count     = $ciDaoImpl->checkUnique($value);

        if ($count['cnt'] > 0) {
            $this->_error(self::NOT_UNIQUE);
            return false;
        } else {
            return true;
        }
    }
}