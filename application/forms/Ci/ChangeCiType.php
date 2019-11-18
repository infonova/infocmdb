<?php

class Form_CI_ChangeCiType extends Form_AbstractAppForm
{
    public function __construct($translator, $ciTypeList, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setName('changeCiType');
        $this->setAttrib('enctype', 'multipart/form-data');

        $parentCiType = new Zend_Form_Element_Select('parentCiType');
        $parentCiType->setLabel('ciType');
        $parentCiType->addMultiOptions($ciTypeList);
        $parentCiType->setAttrib('onChange', 'javascript:updateCiForm(this.form);');
        $parentCiType->setRequired(true);
        $parentCiType->setAttrib('style', 'width:200px');
        $this->addElement($parentCiType);
    }


    public function finalizeForm($isCiAttach)
    {
        $submit = new Zend_Form_Element_Submit('save');
        $submit->setLabel('changeCiType');
        $submit->setAttrib('class', 'standard_button');
        $submit->setRequired(true);
        $submit->autoInsertNotEmptyValidator(true);

        if ($isCiAttach)
            $this->addElement($submit);
    }


    /**
     * adds a CI TYPE child element to the form
     *
     * @param unknown_type $ciTypeList
     * @param unknown_type $count
     *
     * @return unknown_type
     */
    public function addChild($ciTypeList, $count)
    {
        $child = new Zend_Form_Element_Select('child_' . $count);
        //$child->setLabel('child_'.$count);
        $child->addMultiOptions($ciTypeList);
        $child->setAttrib('onChange', 'javascript:updateCiForm(this.form);');
        $child->setAttrib('style', 'width:200px');

        $this->addElement($child);
    }
}