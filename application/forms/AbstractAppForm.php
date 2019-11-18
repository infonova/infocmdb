<?php

class Form_AbstractAppForm extends Zend_Form
{

    public function __construct($translator, $options = null)
    {
        parent::__construct($options);
        $this->setTranslator($translator);
        // FIXME: reactivate if csrf protection is required. may cause some form timeout/invalid token messages
//		$this->addElement('hash', 'csrf_token',
//					array('salt' => get_class($this) . 's3cr3t%Ek@on9!'));
    }


    /**
     * creates a validator to check null input
     *
     * @return Zend_Validate_NotEmpty
     */
    protected function createNotEmptyValidator()
    {
        // String length validator
        $validator = new Zend_Validate_NotEmpty();


        return $validator;
    }


    /**
     * creates a validator to check the length of an input field by the configured parameter
     *
     * @param mixed  $config     the config file
     * @param string $configName the name of the property
     * @param string $confiProperty
     *
     * @return Zend_Validate_StringLength
     */
    protected function createStringLengthValidator($config, $configName, $confiProperty)
    {
        if (isset($config->$confiProperty->validators->strlen->enabled) && $config->$confiProperty->validators->strlen->enabled) {
            $minLength = $config->$confiProperty->validators->strlen->options->min;
            $maxLength = $config->$confiProperty->validators->strlen->options->max;
            // String length validator
            $validator = new Zend_Validate_StringLength($minLength, $maxLength);
            $validator->setMessages(array(
                Zend_Validate_StringLength::TOO_SHORT =>
                    $this->_translator->_($confiProperty . 'TooShort'),
                Zend_Validate_StringLength::TOO_LONG  =>
                    $this->_translator->_($confiProperty . 'TooLong'),
            ));

            return $validator;
        } else {
            $validator = new Zend_Validate_StringLength(0, 50000);
            return $validator;
        }
    }


    /**
     * creates a regex validator for the CiType
     *
     * @param unknown_type $config
     *
     * @return Zend_Validate_Regex
     */
    protected function createRegexValidator($config, $configName, $confiProperty)
    {
        if (is_object($config->$confiProperty) && $config->$confiProperty->validators->regex->enabled) {
            $regex = $config->$confiProperty->validators->regex->pattern;

            // String length validator
            $validator = new Zend_Validate_Regex($regex);
            $validator->setMessages(array(
                Zend_Validate_Regex::NOT_MATCH =>
                    $this->_translator->_($confiProperty . 'RegexNotMatch'),
            ));

            return $validator;
        } else {
            $validator = new Zend_Validate_StringLength(0, 50000);
            return $validator;
        }

    }


}