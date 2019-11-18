<?php

class Form_Ci_UserMapping extends Form_AbstractAppForm
{
    public function __construct($translator, $ciId, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('adduser');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAction(APPLICATION_URL . 'ci/user/ciid/' . $ciId);
    }


    /**
     * adds an attribute to the given form
     */
    public function addUser($userId, $userName, $userDescription = "")
    {
        $user = new Zend_Form_Element_Checkbox($userId);
        $user->setLabel($userName);
        $user->setAttrib('title', $userDescription);
        $user->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));
        $this->addElement($user);
    }


    public function addSubmitButton()
    {
        // add submit button
        $submit = new Zend_Form_Element_Submit('createAssign');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');
        $submit->setRequired(true);
        $this->addElements(array($submit));
    }
}