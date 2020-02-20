<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_User_ChangePassword extends Form_AbstractAppForm
{
    public function __construct($translator, $config, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('changePassword');
        $this->setAttrib('enctype', 'multipart/form-data');

        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('password');
        $password->setDescription($translator->translate('newPasswordDesc'));
        $password->setAttrib('title', $translator->translate('userPasswordTitle'));
        $password->setAttrib('autocomplete', 'new-password');
        $password->setAttrib('size', '30');
        $password->setRequired(true);
        $password->addValidator($this->createStringLengthValidator($config, 'user', 'password'), true);
        $password->addValidator($this->createRegexValidator($config, 'user', 'password'), true);
        $password->addValidator(new Form_Validator_PasswordConfirmation($translator));
        $password->addValidator(new Form_Validator_PasswordStrength($translator));
        $this->addElement($password);

        $passwordConfirm = new Zend_Form_Element_Password('password_confirm');
        $passwordConfirm->setLabel('passwordConfirm');
        $passwordConfirm->setAttrib('size', '30');
        $passwordConfirm->setAttrib('autocomplete', 'new-password');
        $passwordConfirm->setDescription($translator->translate('password_confirm_desc'));
        $passwordConfirm->setAttrib('title', $translator->translate('userPasswordConfirmTitle'));
        $this->addElement($passwordConfirm);

        $passwordExpireOff = new Zend_Form_Element_Checkbox('password_expire_off');
        $passwordExpireOff->setLabel($translator->translate('noPassExpireLabel'));
        $passwordExpireOff->setAttrib('title', $translator->translate('noPassExpireTitle'));
        $passwordExpireOff->setDescription($translator->translate('noPassExpireDesc'));
        $this->addElement($passwordExpireOff);

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(new Form_Decorator_MyDescription()),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        $this->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'create_form')),
            'Form',
        ));
    }
}
