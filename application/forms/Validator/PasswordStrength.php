<?php

class Form_Validator_PasswordStrength extends Zend_Validate_Abstract
{

    protected $translator;
    protected $logger;
    protected $config;

    const NOT_VALID = "not_valid";
    protected $_messageTemplates = array();

    /**
     * @param Zend_Translator $translator
     */
    public function __construct($translator)
    {
        // set translator
        $this->translator = $translator;
        $language         = $translator->getLocale();
        // set logger
        $this->logger = Zend_Registry::get('Log');
        // try to load config
        try {
            $this->config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/user.ini', APPLICATION_ENV);
        } catch (Exception $ex) {
            // catch all exceptions and write them to log
            $this->logger->log("Error reading configs/forms/user.ini: " . $ex, Zend_Log::WARN);
            $this->config = null;
        }

        if (isset($this->config->password->validators->message->$language) && !empty($this->config->password->validators->message->$language)) {
            $message = $this->config->password->validators->message->$language;
        } else {
            $message = 'The password must be at least 8 characters long...';
        }

        $this->_messageTemplates = array(
            self::NOT_VALID => $message,
        );

    }

    /**
     * checks if password meets security requirements set in configs/forms/user.ini
     *
     * @param mixed $value
     *
     * @return boolean
     */
    public function isValid($value)
    {
        // return variable
        $valid = true;
        // try to read config and set values from config
        if (isset($this->config)) {
            $strlenEnabled = $this->config->password->validators->strlen->enabled;
            $minLength     = $this->config->password->validators->strlen->options->min;
            $maxLength     = $this->config->password->validators->strlen->options->max;
            $regexRules    = $this->config->password->validators->regex;
        }
        // set default value in case it is not set
        if (!self::checkIsset($minLength)) {
            $minLength = 8;
        }
        // set default value in case it is not set
        if (!self::checkIsset($maxLength)) {
            $maxLength = 180;
        }

        // set default value in case it is not set
        if (!isset($strlenEnabled)) {
            $strlenEnabled = true;
        } else if ($strlenEnabled === '') {
            // if strlenEnabled is empty string -> it is set to false
            $strlenEnabled = false;
        }
        // if password length enforcement is enabled
        if ($strlenEnabled == true) {
            // check if length of password is between $minLength and $maxLength
            $isLengthValid = (strlen($value) >= $minLength && strlen($value) <= $maxLength);
            // if password length is invalid -> set return variable to false and set error message
            if (!$isLengthValid) {
                $valid = false;
                $this->_error(self::NOT_VALID);
            }
        }
        // if there are regex rules defined in the config
        if (self::checkIsset($regexRules)) {
            // get all regex values from the config as array
            $rules = $regexRules->toArray();

            // iterate through each rule (to get 'enabled' and 'pattern')
            foreach ($rules as $rule) {
                // check if 'enabled' exists
                if (self::checkIsset($rule['enabled'])) {
                    // check if 'enabled' is true
                    if ($rule['enabled'] == true) {
                        // if pattern exists
                        if (self::checkIsset($rule['pattern'])) {
                            // pattern match the password (returns 0, 1 or false), typecast the value to bool
                            $patternMatch = (bool)preg_match($rule['pattern'], $value);
                            // if pattern did not match -> set return variable to false and set error message
                            if (!$patternMatch) {
                                $valid = false;
                                $this->_error(self::NOT_VALID);
                            }
                        }
                    }
                }
            } // end foreach

        }

        return $valid;
    }


    /**
     * check if variable is set, not empty
     *
     * @param mixed $a
     *
     * @return bool
     */
    private function checkIsset($a)
    {
        return isset($a) && !empty($a);// && $a;
    }

}