<?php

/**
 * This class is used to create the login Form
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Ci_Create extends Form_AbstractAppForm
{

    public function __construct($translator, $sessionID, $ciTypeList, $ciConfig)
    {
        parent::__construct($translator);
        $this->setName('CreateForm');
        $this->setAttrib('id', 'CreateForm');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setAttrib('action', APPLICATION_URL . 'ci/create/formFinished/1/sessionID/' . $sessionID);
        $this->setAttrib('method', 'POST');


        if (is_null($ciTypeList)) {
            $ciTypeList = array();
        }

        $sessionIDElement = new Zend_Form_Element_Hidden('sessionID');
        $sessionIDElement->setValue($sessionID);
        $this->addElement($sessionIDElement);

        // parent Ci type ->option drop down
        $parentCiType = new Zend_Form_Element_Select('parentCiType');
        $parentCiType->setLabel('ciType');
        $parentCiType->addMultiOptions($ciTypeList);
        $parentCiType->setAttrib('onChange', 'javascript:updateCiForm(this.form);');
        $parentCiType->setRequired(true);
        $parentCiType->setAttrib('style', 'width:200px');
        $parentCiType->setAttrib('title', $this->_translator->translate('ciParentCiTypeTitle'));
        $this->addElement($parentCiType);


    }


    /**
     * adds a button/img/link to a attribute page.
     * There you can select an attribute and add it to your current form
     */
    public function addAttributeImgLink($attributeType, $attributeGroupId, $sessionID)
    {
        $translator = Zend_Registry::get('Zend_Translate');

        $img = new Zend_Form_Element_Image($attributeType . 'add');
        $img->setImage(APPLICATION_URL . 'images/icon/add.png');
        $img->setValue($attributeGroupId);
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
    public function addAttributeImgRemoveLink($attributeId, $sessionID)
    {
        $img = new Zend_Form_Element_Image($attributeId . 'delete');
        $img->setImage(APPLICATION_URL . 'images/icon/trash_14.png');
        $img->setAttrib('height', 15);


        $img->setAttrib('onClick', "javascript:removeAttributeWindow('" . $attributeId . "', '0', '" . $sessionID . "');");
        $img->setAttrib('title', "Feld Löschen");
        $img->setAttrib('class', "delete_icon");
        $img->setAttrib('height', 14);
        $img->setAttrib('tabindex', "-1");
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
        $project->setAttrib('title', $this->_translator->translate('ciProjectTitle'));

        $this->addElement($project);
    }


    /**
     * adds an attribute to the given form
     */
    public function addAttribute($attribute, $key, $isValidate = false, $userId = null)
    {
        if (!$attribute[Db_Attribute::NOTE]) {
            $attribute[Db_Attribute::NOTE] = "";
        }

        $attribute[Db_Attribute::NAME] = $attribute[Db_Attribute::NAME] . $key;

        $attributeType = Util_AttributeType_Factory::get($attribute['type']);
        $this->addElements($attributeType->getFormElements($attribute, $key, null, $isValidate, $userId));

        $hidden = new Zend_Form_Element_Hidden($attribute[Db_Attribute::NAME] . 'hidden');
        $hidden->clearDecorators();
        $hidden->addDecorator('ViewHelper');
        $this->addElement($hidden);
    }

    public function addSubmitButton()
    {

        $submit = new Zend_Form_Element_Submit('create');
        $submit->setLabel('create');
        $submit->setAttrib('class', 'standard_button');


        $cancel = new Zend_Form_Element_Button('cancel');
        $cancel->setLabel('cancel');
        $cancel->setAttrib('class', 'cancel_button');
        $cancel->setAttrib('onClick', "reset_create()");


        $this->addElements(array($submit, $cancel));

        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width:80%;')),
            array('Label', array('tag' => 'td')),
        ));


        $uploadPath = Util_FileUpload::getUploadPath('tmp');
        $icon       = new Zend_Form_Element_File('ciicon');
        $icon->setAttrib('class', 'hidden');
        $icon->setAttrib('onChange', 'changeCiIcon(this)');
        $icon->setDestination($uploadPath);
        $icon->addValidator('Extension', false, 'jpg,jpeg,png,gif');
        $icon->addValidator('ImageSize', false, array(10, 10, 30, 30));
        $this->addElement($icon);

        $iconDelete = new Zend_Form_Element_Hidden('ciicon_delete');
        $this->addElement($iconDelete);
    }

    public function addHiddenCIID()
    {

        $hciid = new Zend_Form_Element_Hidden('ciid');
        $this->addElement($hciid);


    }


}
