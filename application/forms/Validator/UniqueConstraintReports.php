<?php

class Form_Validator_UniqueConstraintReports extends Zend_Validate_Abstract
{
    const NOT_UNIQUE = 'notUnique';

    protected $_messageTemplates = array(
        self::NOT_UNIQUE => 'Value is not Unique',
    );

    public function isValid($value)
    {
        /** @var Zend_Controller_Request_Abstract $request */
        $request = $request = Zend_Controller_Front::getInstance()->getRequest();
        $daoImpl = new Dao_Reporting();

        $id = $request->getParam('reportingId', 0);

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