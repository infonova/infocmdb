<?php

/**
 * This class is used to create the attribute input form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_User_Create extends Form_AbstractAppForm
{
    public function __construct($translator, $config, $themeList, $layouts, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('createUser');
        $this->setAttrib('enctype', 'multipart/form-data');

        // Theme
        $theme = new Zend_Form_Element_Select('theme');
        $theme->addMultiOptions($themeList);
        $theme->setLabel('theme');
        $theme->setAttrib('title', $translator->translate('userThemeTitle'));
        $theme->setDescription($translator->translate('theme_desc'));
        $theme->setAttrib('style', 'width:230px');

        if ($config->user->theme->validators->notempty->enabled) {
            $theme->setRequired(true);
            $theme->autoInsertNotEmptyValidator(true);
        }
        $this->addElement($theme);


        // Name
        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('username');
        $name->setAttrib('title', $translator->translate('userNameTitle'));
        $name->setDescription($translator->translate('name_desc'));

        if ($config->user->name->validators->notempty->enabled) {
            $name->setRequired(true);
            $name->autoInsertNotEmptyValidator(true);
        }

        $name->addValidator($this->createStringLengthValidator($config, 'user', 'name'), true);
        $name->addValidator($this->createRegexValidator($config, 'user', 'name'), true);
        $name->setAttrib('size', '30');
        $name->addValidator(new Form_Validator_UniqueConstraintUser());
        $this->addElement($name);


        // email address
        $email = new Zend_Form_Element_Text('email');
        $email->setLabel('mailaddress');
        $email->setAttrib('size', '30');
        $email->setAttrib('title', $translator->translate('userEmailTitle'));
        if ($config->user->email->validators->notempty->enabled) {
            $email->setRequired(true);
            $email->autoInsertNotEmptyValidator(true);
        }
        $email->setDescription($translator->translate('email_desc'));
        $this->addElement($email);


        // Password
        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('password');
        $password->setAttrib('size', '30');
        $password->setAttrib('autocomplete', 'off');
        $password->setAttrib('title', $translator->translate('userPasswordTitle'));
        $password->setDescription($translator->translate('password_desc'));

        if ($config->user->password->validators->notempty->enabled) {
            $password->setRequired(true);
            $password->autoInsertNotEmptyValidator(true);
        }

        $password->addValidator($this->createStringLengthValidator($config, 'user', 'password'), true);
        $password->addValidator($this->createRegexValidator($config, 'user', 'password'), true);
        $password->addValidator(new Form_Validator_PasswordConfirmation($translator));
        $password->addValidator(new Form_Validator_PasswordStrength($translator));
        $this->addElement($password);


        $passwordConfirm = new Zend_Form_Element_Password('password_confirm');
        $passwordConfirm->setLabel('passwordConfirm');
        $passwordConfirm->setAttrib('size', '30');
        $passwordConfirm->setAttrib('title', $translator->translate('userPasswordConfirmTitle'));
        $passwordConfirm->setDescription($translator->translate('password_confirm_desc'));
        $this->addElement($passwordConfirm);


        // CI delete
        $ciDelete = new Zend_Form_Element_Checkbox('ciDelete');
        $ciDelete->setLabel('ciDeleteAllowed');
        $ciDelete->setAttrib('title', $translator->translate('userCiDeleteTitle'));
        $ciDelete->setDescription($translator->translate('ciDelete_desc'));
        $this->addElement($ciDelete);


        // Relation delete
        $relationDelete = new Zend_Form_Element_Checkbox('relationDelete');
        $relationDelete->setLabel('relationDeleteAllowed');
        $relationDelete->setAttrib('title', $translator->translate('userRelationDeleteTitle'));
        $relationDelete->setDescription($translator->translate('relationDelete_desc'));
        $this->addElement($relationDelete);


        // LDAP-Auth
        $ldapAuth = new Zend_Form_Element_Checkbox('ldapAuth');
        $ldapAuth->setLabel('ldapAuth');
        $ldapAuth->setAttrib('title', $translator->translate('userLdapAuthTitle'));
        $ldapAuth->setDescription($translator->translate('ldapAuth_desc'));
        $this->addElement($ldapAuth);

        // Password expiration
        $password_expire_off = new Zend_Form_Element_Checkbox('password_expire_off');
        $password_expire_off->setLabel($translator->translate('noPassExpireLabel'));
        $password_expire_off->setAttrib('title', $translator->translate('noPassExpireTitle'));
        $password_expire_off->setDescription($translator->translate('noPassExpireDesc'));
        $this->addElement($password_expire_off);

        // isRoot
        $isRoot = new Zend_Form_Element_Checkbox('isRoot');
        $isRoot->setLabel('allowAdminMode');
        $isRoot->setAttrib('title', $translator->translate('userIsRootTitle'));
        $isRoot->setDescription($translator->translate('isRoot_desc'));
        $this->addElement($isRoot);


        // FirstName
        $firstname = new Zend_Form_Element_Text('firstname');
        $firstname->setLabel('firstname');
        $firstname->setAttrib('title', $translator->translate('userFirstnameTitle'));
        $firstname->setDescription($translator->translate('firstname_desc'));
        $firstname->setAttrib('size', '30');

        if ($config->user->firstname->validators->notempty->enabled) {
            $firstname->setRequired(true);
            $firstname->autoInsertNotEmptyValidator(true);
        }

        $firstname->addValidator($this->createStringLengthValidator($config, 'user', 'firstname'), true);
        $firstname->addValidator($this->createRegexValidator($config, 'user', 'firstname'), true);
        $this->addElement($firstname);


        // LastName
        $lastname = new Zend_Form_Element_Text('lastname');
        $lastname->setLabel('lastname');
        $lastname->setAttrib('title', $translator->translate('userLastnameTitle'));
        $lastname->setDescription($translator->translate('lastname_desc'));
        $lastname->setAttrib('size', '30');

        if ($config->user->lastname->validators->notempty->enabled) {
            $lastname->setRequired(true);
            $lastname->autoInsertNotEmptyValidator(true);
        }

        $lastname->addValidator($this->createStringLengthValidator($config, 'user', 'lastname'), true);
        $lastname->addValidator($this->createRegexValidator($config, 'user', 'lastname'), true);
        $this->addElement($lastname);


        // description
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('description');
        $description->setAttrib('title', $translator->translate('userDescriptionTitle'));
        $description->setDescription($translator->translate('description_desc'));
        $description->setAttrib('COLS', '28');
        $description->setAttrib('ROWS', '4');

        if ($config->user->description->validators->notempty->enabled) {
            $description->setRequired(true);
            $description->autoInsertNotEmptyValidator(true);
        }

        $description->addValidator($this->createStringLengthValidator($config, 'user', 'description'), true);
        $description->addValidator($this->createRegexValidator($config, 'user', 'description'), true);
        $this->addElement($description);


        // note
        $note = new Zend_Form_Element_Text('note');
        $note->setLabel('comment');
        $note->setAttrib('title', $translator->translate('userNoteTitle'));
        $note->setDescription($translator->translate('note_desc'));
        $note->setAttrib('size', '30');

        if ($config->user->note->validators->notempty->enabled) {
            $note->setRequired(true);
            $note->autoInsertNotEmptyValidator(true);
        }

        $note->addValidator($this->createStringLengthValidator($config, 'user', 'note'), true);
        $note->addValidator($this->createRegexValidator($config, 'user', 'note'), true);
        $this->addElement($note);


        // language
        $language = new Zend_Form_Element_Select('language');
        $language->setLabel('language');
        $language->addMultiOptions(array('de' => 'Deutsch', 'en' => 'English'));
        $language->setAttrib('title', $translator->translate('userLanguageTitle'));
        $language->setDescription($translator->translate('language_desc'));
        $language->setAttrib('style', 'width:160px');
        $this->addElement($language);


        //layout
        $layout = new Zend_Form_Element_Select('layout');
        $layout->setLabel('layout');
        $layout->addMultiOptions($layouts);
        $layout->setValue('default');
        $layout->setAttrib('title', $translator->translate('userLayoutTitle'));
        $layout->setDescription($translator->translate('layout_desc'));
        $layout->setAttrib('style', 'width:160px');
        $this->addElement($layout);


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

    public function addAttribute($prefix, $attributeId, $attributeName, $attributeDescription = "", $readOnly = false)
    {
        $attribute = new Zend_Form_Element_Checkbox($prefix . $attributeId);
        $attribute->setLabel($attributeName);
        if ($attributeDescription) {
            $attribute->setAttrib('title', $attributeDescription);
        }
        if ($readOnly === true) {
            $attribute->setAttrib('disabled', 'disabled');
        }
        $attribute->removeDecorator('Label');
        $this->addElement($attribute);
    }
}