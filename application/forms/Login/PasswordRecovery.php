<?php

/**
 * This class is used to create the Password Recovery Form
 *
 */
class Form_Login_PasswordRecovery extends Form_AbstractAppForm
{

    public function __construct($translator, $options = null, $twoFactorAuthEnabled = false)
    {
        parent::__construct($translator, $options);

        $userConfig = new Util_Config('forms/user.ini', APPLICATION_ENV);

        $username = new Zend_Form_Element_Text('username');
        $username->setAttrib('tabindex', '1');
        $username->setAttrib('placeholder', $this->_translator->_("loginPasswordRecoveryUser"));
        $username->setAttrib('class', 'authInput');
        $username->removeDecorator('Label');

        $password = new Zend_Form_Element_Password('password');
        $password->setAttrib('placeholder', $this->_translator->_('loginPasswordRecoveryNewPassword'));
        $password->removeDecorator('Label');
        $password->setAttrib('class', 'authInput');
        $password->setAttrib('tabindex', '2');
        $password->setAttrib('autocomplete', 'off');

        $password->addValidator($this->createStringLengthValidator($userConfig, 'user', 'password'), true);
        $password->addValidator(new Form_Validator_PasswordConfirmation($this->_translator));
        $password->addValidator(new Form_Validator_PasswordStrength($this->_translator));

        $password_confirm = new Zend_Form_Element_Password('password_confirm');
        $password_confirm->setAttrib('placeholder', $this->_translator->_('loginPasswordRecoveryNewPasswordConfirm'));
        $password_confirm->removeDecorator('Label');
        $password_confirm->setAttrib('class', 'authInput');
        $password_confirm->setAttrib('tabindex', '3');

        $submit = new Zend_Form_Element_Image('submit');
        $submit->setAttrib('onclick', 'form.submit();');
        $submit->removeDecorator('Label');
        $submit->setImage(APPLICATION_URL . 'images/arrow_right_k.png');

        $formElements = array($username, $password, $password_confirm, $submit);

        if ($twoFactorAuthEnabled === true) {
            $verifyCode = new Zend_Form_Element_Text('verify_code');
            $verifyCode->removeDecorator('Label');
            $verifyCode->setAttrib('placeholder', $this->_translator->_('loginPasswordRecoveryTFA'));
            $verifyCode->setAttrib('class', 'tfaInput authInput');
            $verifyCode->setAttrib('tabindex', '4');
            array_push($formElements, $verifyCode);
        }

        $this->addElements($formElements);
    }

}