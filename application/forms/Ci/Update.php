<?php

/**
 * This class is used to create the login Form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Ci_Update extends Form_AbstractAppForm
{

    public function __construct($translator, $ciId, $sessionID, $options = null, $tabIndex = null)
    {
        parent::__construct($translator, $options);
        $this->setName('CreateForm');
        $this->setAttrib('id', 'CreateForm');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAttrib('action', APPLICATION_URL . 'ci/edit/ciid/' . $ciId . '/sessionID/' . $sessionID . '/tab_index/' . $tabIndex);
        $this->setAttrib('method', 'POST');
        //$this->setAttrib('onSubmit', 'return false');

        $ci = new Zend_Form_Element_Hidden('ciid');
        $ci->setValue($ciId);
        $ci->clearDecorators();
        $this->addElement($ci);

        $session = new Zend_Form_Element_Hidden('sessionID');
        $session->setValue($sessionID);
        $session->clearDecorators();
        $this->addElement($session);


    }


    /**
     * adds a button/img/link to a attribute page.
     * There you can select an attribute and add it to your current form
     */
    public function addAttributeImgLink($attributeType, $attributeGroupId, $ciId, $sessionID)
    {
        $translator = Zend_Registry::get('Zend_Translate');

        $img = new Zend_Form_Element_Image($attributeType . 'add');
        $img->setImage(APPLICATION_URL . 'images/icon/add.png');
        $img->setAttrib('onclick', 'loadAttributeForm(' . $attributeGroupId . '); return false');

        if ($attributeType == 'general') {
            $img->setAttrib('title', $translator->translate('add_attribute'));
        } else {
            $img->setAttrib('title', $translator->translate('attribute_of_type') . ' ' . $attributeType . ' ' . $translator->translate('add'));
        }
        $this->addElement($img);
    }

    /**
     * adds a delete button/img/link to a specified attribute.
     */
    public function addAttributeImgRemoveLink($attributeId, $sessionID, $ciId)
    {
        $img = new Zend_Form_Element_Image($attributeId . 'delete');
        $img->setImage(APPLICATION_URL . 'images/icon/trash_14.png');
        $img->setAttrib('height', 15);

        $isUpdate = 1;
        $img->setAttrib('onClick', "javascript:removeAttributeWindow('" . $attributeId . "', '" . $isUpdate . "', '" . $sessionID . "', '" . $ciId . "');");
        $img->setAttrib('title', "Feld Löschen");
        $img->setAttrib('class', "delete_icon");
        $img->setAttrib('height', 14);
        $img->setAttrib('tabindex', '-1');
        $img->clearDecorators();
        $img->addDecorator('Image');

        $img->addDecorators(array(
            array('HtmlTag', array('tag' => 'dt', 'class' => 'formDeleteButton', 'openOnly' => false, 'align' => 'right')),
        ));
        $this->addElement($img);
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


    /**
     * adds the project selection to the form
     */
    public function addProjectSelection($projectList)
    {
        // ad projects
        if (is_null($projectList)) {
            $projectList = array();
        }

        // PROJECT ->option drop down
        $project = new Zend_Form_Element_Select('project');
        $project->setLabel('project');
        $project->addMultiOptions($projectList);
        $project->setRequired(true);
        $project->autoInsertNotEmptyValidator(true);
        $project->setAttrib('style', 'width:200px');
        $project->setAttrib('title', $this->translator->translate('ciProjectTitle'));

        $this->addElement($project);
    }

    /**
     * adds an attribute to the given form
     */
    public function addAttribute($attribute, $key, $ciId, $isValidate = false, $userId = null)
    {
        if (!$attribute[Db_Attribute::NOTE]) {
            $attribute[Db_Attribute::NOTE] = "";
        }

        $attribute[Db_Attribute::NAME] = $attribute[Db_Attribute::NAME] . $key;

        $attributeType = Util_AttributeType_Factory::get($attribute['type']);
        $this->addElements($attributeType->getFormElements($attribute, $key, $ciId, $isValidate, $userId));

        $hidden = new Zend_Form_Element_Hidden($attribute[Db_Attribute::NAME] . 'hidden');
        $hidden->clearDecorators();
        $hidden->addDecorator('ViewHelper');
        $this->addElement($hidden);
    }

    public function addSubmitButton($ciId)
    {
        // add reset and submit button
        $submit = new Zend_Form_Element_Submit('create');
        $submit->setLabel('create');
        $submit->setAttrib('class', 'standard_button');


        $cancel = new Zend_Form_Element_Submit('cancel');
        $cancel->setLabel('cancel');
        $cancel->setAttrib('class', 'cancel_button');
        $cancel->setAttrib('name', 'cancel');


        $cancel_top = new Zend_Form_Element_Button('cancel_top');
        $cancel_top->setLabel('cancel');
        $cancel_top->setAttrib('class', 'cancel_button');
        $cancel_top->setAttrib('onClick', "$('#cancel').click()");

        $submit_top = new Zend_Form_Element_Button('create_top');
        $submit_top->setLabel('create');
        $submit_top->setAttrib('class', 'standard_button');
        $submit_top->setAttrib('onclick', "$('#create').click()");

        $this->addElements(array($submit, $cancel, $submit_top, $cancel_top));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Tooltip',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'width' => '15px')),
            array('Label', array('tag' => 'td')),
        ));


        $uploadPath = Util_FileUpload::getUploadPath('tmp');
        $icon       = new Zend_Form_Element_File('ciicon');
        $icon->setAttrib('class', 'hidden');
        $icon->setAttrib('onChange', 'changeCiIcon(this)');
        $icon->addValidator('Extension', false, 'jpg,jpeg,png');
        $icon->addValidator('ImageSize', false, array(10, 10, 30, 30));
        $icon->setDestination($uploadPath);
        $this->addElement($icon);

        $iconDelete = new Zend_Form_Element_Hidden('ciicon_delete');
        $this->addElement($iconDelete);
    }
}
