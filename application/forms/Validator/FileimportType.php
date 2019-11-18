<?php

class Form_Validator_FileimportType extends Zend_Validate_Abstract
{
    const NOT_ALLOWED = 'filetypeNotSupported';

    protected $_messageTemplates = array(
        self::NOT_ALLOWED => 'Filetype not Supported',
    );

    public function isValid($value, $context = null)
    {
        $value = (string)$value;
        $this->_setValue($value);

        $fileType = end(explode(".", $fileName));
        $fileType = strtoupper($fileType);
        $fileType = trim($fileType);

        $allowed = array();
        array_push($allowed, 'CSV');
        array_push($allowed, 'XLS');
        array_push($allowed, 'XLSX');
        array_push($allowed, 'TXT');

        if (!$fileType || !in_array($fileType, $allowed)) {
            $this->_error(self::NOT_ALLOWED);
            return false;
        } else {
            return true;
        }
    }
}