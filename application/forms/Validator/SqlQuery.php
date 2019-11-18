<?php

class Form_Validator_SqlQuery extends Zend_Validate_Abstract
{
    const INVALID_QUERY     = 'invalidQuery';
    const SELECT_ONLY_QUERY = 'invalidQuery_noSelect';
    const ACCESS_DENIED     = 'accessDenied';

    protected $_messageTemplates = array(
        self::INVALID_QUERY     => 'invalidQuery',
        self::SELECT_ONLY_QUERY => 'invalidQuery_noSelect',
        self::ACCESS_DENIED     => 'accessDenied',
    );

    public function isValid($value, $context = null)
    {
        #is always valid because mysql user is only allowed to SELECT, INSERT, UPDATE and EXECUTE
        return true;
    }
}