<?php

class Util_AttributeType_Type_Script extends Util_AttributeType_Type_Abstract
{

    private $_folder;

    const ATTRIBUTE_VALUES_ID = 'text';
    const ATTRIBUTE_TYPE_ID   = 13;


    public function __construct()
    {
        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $this->_folder = $config->file->upload->script->folder;

        if (!$this->_folder) {
            $this->_folder = 'script';
        }
    }

    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#setAttributeValue($attribute, $path)
     */
    public function setAttributeValue($attribute, $ciId, $path)
    {
        if (!$attribute['valueNote'])
            $attribute['valueNote'] = $attribute['value_text'];

        $attribute['value_text'] = '<a href="' . APPLICATION_URL . '/' . $path . $this->_folder . '/' . $attribute['value_text'] . '">' . $attribute['valueNote'] . '</a>';
        $attribute['noEscape']   = true;
        return $attribute;
    }

    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::ID] . $key . 'filename'];
        return array(
            'value' => $currentVal,
        );
    }

    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElementsForSingleEdit($ciAttribute)
     */
    public function getFormElementsForSingleEdit($ciAttribute, $userId = null, $ciId = null)
    {
        $hint      = $ciAttribute[Db_Attribute::HINT];
        $maxLength = $ciAttribute[Db_Attribute::INPUT_MAXLENGTH];

        $description = new Zend_Form_Element_Hidden($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] . 'description');
        $description->setLabel('scriptDescription');

        if ($maxLength)
            $description->setAttrib('maxlength', $maxLength);
        $description->setDecorators(array('ViewHelper', 'Errors'));
        $description->setValue($ciAttribute[Db_CiAttribute::NOTE]);

        $fileName = new Zend_Form_Element_Hidden($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] . 'filename');
        $fileName->setLabel('fileName');
        if ($maxLength)
            $fileName->setAttrib('maxlength', $maxLength);
        $fileName->setDecorators(array('ViewHelper', 'Errors'));
        $fileName->setValue($ciAttribute[Db_CiAttribute::VALUE_TEXT]);

        $link = new Zend_Form_Element_Image($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID]);
        $link->setImage(APPLICATION_URL . 'images/icon/upload.png');
        $link->setLabel($ciAttribute[Db_Attribute::DESCRIPTION]);
        $link->setAttrib('title', "upload");
        $link->setAttrib('linkname', 'upload');
        $link->setAttrib('class', "tu_iframe_500x120");
        $link->setAttrib('tabindex', '-1');
        $link->setAttrib('href', APPLICATION_URL . 'fileupload/index/filetype/script/attributeId/' . $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] . '/ciid/' . $ciAttribute[Db_CiAttribute::CI_ID]);
        $link->setAttrib('toptions', "effect = clip, layout = flatlook");
        $link->setDecorators(array('ViewHelper', 'Errors', 'Label', array(array('data' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element'))));


        if ($hint) {
            $link->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($description, $fileName, $link);
    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#getFormElements($ciAttribute)
     */
    public function getFormElements($ciAttribute, $key = null, $ciId = null, $isValidate = false, $userId = null)
    {
        $attributeId          = $ciAttribute[Db_Attribute::ID];
        $attributeName        = $ciAttribute[Db_Attribute::NAME];
        $attributeDescription = $ciAttribute[Db_Attribute::DESCRIPTION];
        $attributeNote        = $ciAttribute[Db_Attribute::NOTE];
        $attributeType        = $ciAttribute['type'];
        $attributeValue       = $ciAttribute['value'];
        $notNull              = $ciAttribute[Db_CiTypeAttribute::IS_MANDATORY];
        $isUnique             = $ciAttribute[Db_Attribute::IS_UNIQUE];
        $regex                = $ciAttribute['regex'];
        $write                = $ciAttribute['permission_write'];
        $maxLength            = $ciAttribute[Db_Attribute::INPUT_MAXLENGTH];
        $cols                 = $ciAttribute[Db_Attribute::TEXTAREA_COLS];
        $rows                 = $ciAttribute[Db_Attribute::TEXTAREA_ROWS];
        $hint                 = $ciAttribute[Db_Attribute::HINT];

        $description = new Zend_Form_Element_Hidden($attributeId . $key . 'description');
        $description->setLabel('scriptDescription');

        if ($maxLength)
            $description->setAttrib('maxlength', $maxLength);

        $fileName = new Zend_Form_Element_Hidden($attributeId . $key . 'filename');
        $fileName->setLabel('fileName');
        if ($maxLength)
            $fileName->setAttrib('maxlength', $maxLength);

        $link = new Zend_Form_Element_Image($attributeId . $key);
        $link->setImage(APPLICATION_URL . 'images/icon/upload.png');
        $link->setLabel($ciAttribute[Db_Attribute::DESCRIPTION]);
        $link->setAttrib('title', "upload");
        $link->setAttrib('linkname', 'upload');
        $link->setAttrib('class', "tu_iframe_500x120");
        $link->setAttrib('tabindex', '-1');
        $link->setAttrib('href', APPLICATION_URL . 'fileupload/index/filetype/script/attributeId/' . $attributeId . '/genId/' . $key);
        $link->setAttrib('toptions', "effect = clip, layout = flatlook");
        $link->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id' => 'script-row'))));


        if ($isUnique) {
            $description->addValidator(new Form_Validator_UniqueConstraint($attributeId, $ciId));
            $link->setLabel('(u) ' . $attributeDescription);
        }

        if ($notNull) {
            $description->setRequired(true);
            $description->setAutoInsertNotEmptyValidator(true);
        }

        if (!$write) {
            $link->setAttrib('class', 'disabled');
            $description->setAttrib('class', 'disabled');
            $fileName->setAttrib('class', 'disabled');
        }

        if ($attributeNote) {
            $link->removeDecorator('description');
            $link->setDescription($attributeNote);
            $link->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $link->setDescription($this->prepareHintForTooltip($hint));
        }

        return array($link, $description, $fileName);
    }


    public static function getFolder()
    {
        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);

        if (!$config->file->upload->script->folder) {
            return 'script';
        }
        return $config->file->upload->script->folder;
    }


    public function returnFormData($values, $attributeId = null)
    {
        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = $values[$attributeId . 'filename'];
        $data[Db_CiAttribute::NOTE]       = $values[$attributeId . 'description'];
        return $data;
    }

    public function addFormData($formData, $attribute, $values, $storedIDs)
    {
        $data = null;

        if (isset($values[$storedIDs[0]][Db_CiAttribute::VALUE_TEXT])) {
            $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_TEXT];
        } else if (isset($values[$storedIDs[0]][Db_CiAttribute::VALUE_DATE])) {
            $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_DATE];
        } else if (isset($values[$storedIDs[0]][Db_CiAttribute::VALUE_DEFAULT])) {
            $data = $values[$storedIDs[0]][Db_CiAttribute::VALUE_DEFAULT];
        }

        $formData[$attribute[Db_Attribute::ID] . $attribute['genId'] . 'filename']    = $data;
        $formData[$attribute[Db_Attribute::ID] . $attribute['genId'] . 'description'] = $values[$storedIDs[0]][Db_CiAttribute::NOTE];
        $formData[$attribute[Db_Attribute::NAME] . 'hidden']                          = $values[$storedIDs[0]]['ciAttributeId'];

        return $formData;
    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Type/Util_AttributeType_Type_Abstract#getCiEditData($values, $attribute, $key, $currentVal)
     */
    public function getCiEditData($values, $attribute, $key, $currentVal, $ciId)
    {
        $fileName = $values[$attribute[Db_Attribute::ID] . $key . 'filename'];
        $note     = $values[$attribute[Db_Attribute::ID] . $key . 'description'];

        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = $fileName;
        $data[Db_CiAttribute::NOTE]       = $note;

        return $data;
    }


    public function addCi($values, $attribute, $ciId)
    {
        $key        = $attribute['genId'];
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        $fileName = $values[$attribute[Db_Attribute::ID] . $key . 'filename'];
        $note     = $values[$attribute[Db_Attribute::ID] . $key . 'description'];

        if (!$fileName || !$note)
            return null;

        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = $fileName;
        $data[Db_CiAttribute::NOTE]       = $note;
        return $data;
    }


    public function getString(&$form, $attribute)
    {
        $varName            = $attribute['id'] . $attribute['genId'];
        $descriptionVarName = $attribute['id'] . $attribute['genId'] . 'description';
        $filenameVarName    = $attribute['id'] . $attribute['genId'] . 'filename';
        $class              = "";

        //Admin-Mode: show attribute-name
        if (Zend_Registry::get('adminMode') === true) {
            $attribute['note'] = $attribute['description'];
            $form->$varName->setLabel($attribute['name']);
        }

        if ($form->$filenameVarName->isRequired()) {
            $class = "required";
        }

        $returnString = "<td>
							<label title=\"" . htmlspecialchars($attribute['note']) . "\" class='" . $class . ">" . $form->$varName->getLabel() . "</label>
						</td>
						<td>" . $form->$varName->setDecorators(array('Errors', array('ViewHelper', array('tag' => '<div>'))));


        $value = $lang['doc'];

        $returnString .= $form->$descriptionVarName->setDecorators(array('Errors', array('ViewHelper', array('tag' => '<div>'))));

        if ($form->getValue($descriptionVarName)) {
            $value .= "<strong>" . $form->getValue($descriptionVarName) . "</strong> ";
        }

        $returnString .= $form->$filenameVarName->setDecorators(array('Errors', array('ViewHelper', array('tag' => '<div>'))));

        if ($form->getValue($filenameVarName)) {
            $value .= $form->getValue($filenameVarName);
        }

        $returnString .= "<div id='" . $attribute['id'] . $attribute['genId'] . "filenameupload'>" . $value . "</div>";

        if ($attribute['hint']) {
            $returnString .= $form->$varName->setDecorators(array(new Form_Decorator_MyTooltip()));
        }
        $returnString .= "</td>";

        return $returnString;
    }
}