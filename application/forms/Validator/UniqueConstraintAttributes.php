<?php

class Form_Validator_UniqueConstraintAttributes extends Zend_Validate_Abstract
{

    const NOT_UNIQUE = "notUniqueName";

    protected $_messageTemplates = array(
        self::NOT_UNIQUE => "notUniqueName",
    );

    public function isValid($value)
    {
        /** @var Zend_Controller_Request_Abstract $request */
        $request = $request = Zend_Controller_Front::getInstance()->getRequest();
        $daoImpl = new Dao_Attribute();

        $id = $request->getParam('attributeId', 0);

        $value = (string)$value;
        $value = trim($value);

        $this->_setValue($value);

        $count = $daoImpl->checkUnique($value, $id);

        if ($count['cnt'] > 0) {
            $this->_error(self::NOT_UNIQUE);
            return false;
        } else {
            return true;
        }
    }
}