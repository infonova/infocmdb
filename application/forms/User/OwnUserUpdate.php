<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_User_OwnUserUpdate extends Form_AbstractAppForm
{
    public function __construct($translator, $config, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('settingsUser');
        $this->setAttrib('enctype', 'multipart/form-data');


        // email address
        $email = new Zend_Form_Element_Text('email');
        $email->setLabel('mailaddress');
        $email->setAttrib('size', '30');
        $email->setDescription($translator->translate('email_desc'));
        $email->setAttrib('title', $translator->translate('userEmailTitle'));
        if ($config->user->email->validators->notempty->enabled) {
            $email->setRequired(true);
            $email->autoInsertNotEmptyValidator(true);
        }
        $this->addElement($email);


        // Password
        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('password');
        $password->setAttrib('title', $translator->translate('userPasswordTitle'));
        $password->setAttrib('autocomplete', 'off');
        $password->setAttrib('size', '30');
        $password->setDescription($translator->translate('password_desc'));

        $password->addValidator($this->createStringLengthValidator($config, 'user', 'password'), true);
        $password->addValidator($this->createRegexValidator($config, 'user', 'password'), true);
        $password->addValidator(new Form_Validator_PasswordConfirmation($translator));
        $password->addValidator(new Form_Validator_PasswordStrength($translator));
        $this->addElement($password);


        $passwordConfirm = new Zend_Form_Element_Password('password_confirm');
        $passwordConfirm->setLabel('passwordConfirm');
        $passwordConfirm->setAttrib('size', '30');
        $passwordConfirm->setDescription($translator->translate('password_confirm_desc'));
        $passwordConfirm->setAttrib('title', $translator->translate('userPasswordConfirmTitle'));
        $this->addElement($passwordConfirm);

        // FirstName
        $firstname = new Zend_Form_Element_Text('firstname');
        $firstname->setLabel('firstname');
        $firstname->setAttrib('title', $translator->translate('userFirstnameTitle'));

        if ($config->user->firstname->validators->notempty->enabled) {
            $firstname->setRequired(true);
            $firstname->autoInsertNotEmptyValidator(true);
        }

        $firstname->addValidator($this->createStringLengthValidator($config, 'user', 'firstname'), true);
        $firstname->addValidator($this->createRegexValidator($config, 'user', 'firstname'), true);
        $firstname->setDescription($translator->translate('firstname_desc'));
        $firstname->setAttrib('size', '30');
        $this->addElement($firstname);


        // LastName
        $lastname = new Zend_Form_Element_Text('lastname');
        $lastname->setLabel('lastname');
        $lastname->setAttrib('title', $translator->translate('userLastnameTitle'));

        if ($config->user->lastname->validators->notempty->enabled) {
            $lastname->setRequired(true);
            $lastname->autoInsertNotEmptyValidator(true);
        }

        $lastname->addValidator($this->createStringLengthValidator($config, 'user', 'lastname'), true);
        $lastname->addValidator($this->createRegexValidator($config, 'user', 'lastname'), true);
        $lastname->setDescription($translator->translate('lastname_desc'));
        $lastname->setAttrib('size', '30');
        $this->addElement($lastname);


        // language
        $language = new Zend_Form_Element_Select('language');
        $language->setLabel('language');
        $language->addMultiOptions(array('de' => 'Deutsch', 'en' => 'English'));
        $language->setAttrib('title', $translator->translate('userLanguageTitle'));
        $language->setDescription($translator->translate('language_desc'));
        $language->setAttrib('style', 'width:160px');
        $this->addElement($language);


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