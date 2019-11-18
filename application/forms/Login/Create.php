<?php

/**
 * This class is used to create the login Form
 *
 * It does not use the AbstractAppForm
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Login_Create extends Zend_Form
{

    private $_timeout = 600;
    private $_salt    = 'login';

    public function __construct($translator, $authConfig, $urlParam, $options = null)
    {
        if (is_array($options)) {
            if (!empty($options['custom'])) {
                if (!empty($options['custom']['timeout'])) {
                    $this->_timeout = $options['custom']['timeout'];
                }
                if (!empty($options['custom']['salt'])) {
                    $this->_salt = $options['custom']['salt'];
                }
                unset($options['custom']);
            }
        }
        parent::__construct($options);


        $this->setName('Login');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setTranslator($translator);


        $token = new Zend_Form_Element_Hash('token');
        $token->setTimeout($this->_timeout);
        $token->setSalt(uniqid($this->_salt, true));

        $username = new Zend_Form_Element_Text('username');
        $username->setAttrib('class', 'authInput usernameInput');
        $username->setAttrib('placeholder', $translator->translate('username'));
        $username->setAttrib('tabindex', '1');
        $username->setAttrib('value', "Username");
        $username->removeDecorator('Label');


        $submit = new Zend_Form_Element_Image('login');
        $submit->removeDecorator('Label');
        //$submit->setAttrib('onclick', 'form.submit();');
        $submit->setAttrib('class', 'loginSubmit');
        $submit->setImage(APPLICATION_URL . 'images/arrow_right_k.png');

        $password = new Zend_Form_Element_Password('password');
        $password->setAttrib('class', "authInput passwordInput");
        $password->setAttrib('placeholder', $translator->translate('password'));
        $password->removeDecorator('Label');
        $password->setAttrib('tabindex', '2');
        $password->setAttrib('autocomplete', 'off');


        $url = new Zend_Form_Element_Hidden('url');
        $url->setValue($urlParam);
        $url->setDecorators(array('ViewHelper'));

        // input for TFA verify code (needed if user has TFA enabled)
        $tfaCode = new Zend_Form_Element_Text('verifyCode');
        $tfaCode->setAttrib('class', 'verifyCodeInput authInput');
        $tfaCode->setAttrib('placeholder', $translator->translate('2FACode'));
        $tfaCode->setAttrib('style', 'display: none');
        $tfaCode->removeDecorator('Label');
        $tfaCode->setAttrib('tabindex', '10');

        $this->addElements(array($username, $submit,
            $password, $url, $tfaCode));
    }


    /**
     * creates a validator to check the length of the username by the configured parameter
     *
     * @param unknown_type $config
     *
     * @return Zend_Validate_StringLength
     */
    private function createUsernameStringLengthValidator($config)
    {
        $minLength = $config->username->validators->strlen->options->min;
        $maxLength = $config->username->validators->strlen->options->max;

        // String length validator
        $validator = new Zend_Validate_StringLength($minLength, $maxLength);
        $validator->setMessages(array(
            Zend_Validate_StringLength::TOO_SHORT =>
                $this->_translator->_('usernameTooShort'),
            Zend_Validate_StringLength::TOO_LONG  =>
                $this->_translator->_('usernameTooLong'),
        ));

        return $validator;
    }


    /**
     * creates a validator to check null input
     *
     * @param unknown_type $config
     *
     * @return Zend_Validate_NotEmpty
     */
    private function createUsernameNotEmptyValidator($config)
    {
        // String length validator
        $validator = new Zend_Validate_NotEmpty();
        $validator->setMessages(array(
            Zend_Validate_NotEmpty::IS_EMPTY =>
                $this->_translator->_('isEmpty'),
        ));

        return $validator;
    }


    /**
     * creates a regex validator for the username
     *
     * @param unknown_type $config
     *
     * @return Zend_Validate_Regex
     */
    private function createUsernameRegexValidator($config)
    {
        $regex = $config->username->validators->regex->pattern;
        // String length validator
        $validator = new Zend_Validate_Regex($regex);
        $validator->setMessages(array(
            Zend_Validate_Regex::NOT_MATCH =>
                $this->_translator->_('usernameRegexNotMatch'),
        ));

        return $validator;
    }


    /**
     *
     *
     * @param unknown_type $config
     *
     * @return Zend_Validate_StringLength
     */
    private function createPasswordStringLengthValidator($config)
    {
        $minLength = $config->password->validators->strlen->options->min;
        $maxLength = $config->password->validators->strlen->options->max;

        // String length validator
        $validator = new Zend_Validate_StringLength($minLength, $maxLength);
        $validator->setMessages(array(
            Zend_Validate_StringLength::TOO_SHORT =>
                $this->_translator->_('passwordTooShort'),
            Zend_Validate_StringLength::TOO_LONG  =>
                $this->_translator->_('passwordTooLong'),
        ));

        return $validator;
    }


    /**
     *
     * @param unknown_type $config
     *
     * @return Zend_Validate_NotEmpty
     */
    private function createPasswordNotEmptyValidator($config)
    {
        // String length validator
        $validator = new Zend_Validate_NotEmpty();
        $validator->setMessages(array(
            Zend_Validate_NotEmpty::IS_EMPTY =>
                $this->_translator->_('isEmpty'),
        ));

        return $validator;
    }

}
