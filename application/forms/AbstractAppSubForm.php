<?php

class Form_AbstractAppSubForm extends Zend_Form_SubForm
{

    public function __construct($translator, $options = null)
    {
        parent::__construct($options);
        $this->setTranslator($translator);
    }

    /**
     * creates a valid regex validator
     *
     * @param Zend_Config_Ini $config
     * @param string          $configName
     * @param string          $configProperty
     *
     * @return Zend_Validate_Callback
     * @throws Zend_Validate_Exception
     */
    protected function createValidateRegexValidator($config = null, $configName = null, $configProperty = null)
    {
        // String length validator
        $validator = new Zend_Validate_Callback(function ($value, $option)
        {
            return @preg_match($value, null) !== false;
        });

        $validator->setMessages(array(
            Zend_Validate_Callback::INVALID_VALUE =>
                $this->_translator->_($configProperty . 'RegexNotValid'),
            Zend_Validate_Callback::INVALID_CALLBACK =>
                $this->_translator->_($configProperty . 'RegexFailed'),
        ));

        return $validator;
    }

}
