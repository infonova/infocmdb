<?php

class Util_AttributeType_Type_Attachment extends Util_AttributeType_Type_Abstract
{

    protected $_folder;
    protected $_browserOpen;

    const ATTRIBUTE_VALUES_ID = 'text';
    const ATTRIBUTE_TYPE_ID   = 12;


    public function __construct()
    {
        $config             = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $this->_folder      = $config->file->upload->attachment->folder;
        $this->_browserOpen = $config->file->upload->attachment->browser;

        if (!$this->_folder) {
            $this->_folder = 'attachment';
        }

        if (!$this->_browserOpen) {
            $this->_browserOpen = true;
        }

    }


    /**
     * (non-PHPdoc)
     * @see application/utils/attributeType/Util_AttributeType_Abstract#setAttributeValue($attribute, $path)
     */
    public function setAttributeValue($attribute, $ciId, $path = null)
    {
        if (!$attribute['valueNote'])
            $attribute['valueNote'] = $attribute['value_text'];

        $ext = strtolower(array_pop(explode(".", $attribute['valueNote'])));

        $attribute['value_text'] = '<i class="fa" data-ext="' . $ext . '" style="font-size: 18px;"></i> <a href="' .
            APPLICATION_URL . 'download/ci/ciattributeid/' . $attribute['ciAttributeId'] . '/file/' .
            (rawurlencode($attribute['value_text'])) . '" target="_blank">' .
            $attribute['valueNote'] . '</a>';
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
        $description = new Zend_Form_Element_Hidden($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] . 'description');
        //$description->setLabel('scriptDescription');
        //$description->setDecorators(array('ViewHelper',	'Errors'));
        $description->setValue($ciAttribute[Db_CiAttribute::NOTE]);
        $description->setAttrib('class', 'file_description');
        $description->removeDecorator('Label');

        $fileName = new Zend_Form_Element_Hidden($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] . 'filename');
        //$fileName->setLabel('fileName');
        //$fileName->setDecorators(array('ViewHelper', 'Errors'));
        $fileName->setValue($ciAttribute[Db_CiAttribute::VALUE_TEXT]);
        $fileName->setAttrib('class', 'file_name');
        $fileName->removeDecorator('Label');

        $link = new Zend_Form_Element_Hidden($ciAttribute[Db_CiAttribute::ATTRIBUTE_ID]);
        //$link->setLabel($ciAttribute[Db_Attribute::DESCRIPTION]);
        $link->setAttrib('title', "upload");
        $link->setAttrib('linkname', 'upload');
        $link->setAttrib('class', 'dzData');
        $link->setAttrib('data-href', APPLICATION_URL . 'fileupload/ciattachment/filetype/attachment/attributeId/' . $ciAttribute[Db_CiAttribute::ATTRIBUTE_ID] . '/ciid/' . $ciAttribute[Db_CiAttribute::CI_ID]);
        $link->removeDecorator('Label');

        $hint = $ciAttribute[Db_Attribute::HINT];

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
        $description->setAttrib('class', 'file_description');

        if ($maxLength) {
            $description->setAttrib('maxlength', $maxLength);
        }

        $fileName = new Zend_Form_Element_Hidden($attributeId . $key . 'filename');
        $fileName->setLabel('fileName');
        $fileName->setAttrib('class', 'file_name');
        //$fileName->setAttrib('data-url', APPLICATION_URL.'fileupload/unlinkfile');

        if ($maxLength) {
            $fileName->setAttrib('maxlength', $maxLength);
        }

        $link = new Zend_Form_Element_Hidden($attributeId . $key);
        $link->setLabel($attributeDescription);
        $link->setAttrib('title', "upload");
        $link->setAttrib('linkname', 'upload');
        $link->setAttrib('class', 'dzData');
        $link->setAttrib('data-href', APPLICATION_URL . 'fileupload/ciattachment/filetype/attachment/attributeId/' . $attributeId . '/genId/' . $key . '/ciid/' . $ciId);
        //$link->setDecorators(array('ViewHelper', 'Errors', array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')), array('Label', array('tag' => 'td')), array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'id'=>'script-row'))));


        if ($isUnique) {
            $description->addValidator(new Form_Validator_UniqueConstraint($attributeId, $ciId));
            $link->setLabel('(u) ' . $attributeDescription);
        }

        if ($notNull) {
            $fileName->setRequired(true);
            $fileName->setAutoInsertNotEmptyValidator(true);
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
            $hint = $this->prepareHintForTooltip($hint);
            $link->setDescription($hint);
        }

        return array($link, $description, $fileName);
    }


    public static function getFolder()
    {
        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);

        if (!$config->file->upload->attachment->folder) {
            return 'attachment';
        }
        return $config->file->upload->attachment->folder;
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
        $key = $attribute['genId'];

        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        $fileName = $values[$attribute[Db_Attribute::ID] . $key . 'filename'];
        $note     = $values[$attribute[Db_Attribute::ID] . $key . 'description'];

        if (!$fileName || !$note)
            return null;

        // move file to ci folder
        $config         = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $useDefaultPath = $config->file->upload->path->default;
        $defaultFolder  = $config->file->upload->path->folder;

        $path = "";
        if ($useDefaultPath) {
            $path = APPLICATION_PUBLIC . $defaultFolder;
        } else {
            $path = $config->file->upload->path->custom;
        }

        $enabled     = $config->file->upload->attachment->enabled;
        $folder      = $config->file->upload->attachment->folder;
        $maxfilesize = $config->file->upload->attachment->maxfilesize;

        // setting default value
        if (!$enabled) {
            $enabled = false;
        }
        if (!$folder) {
            $folder = 'attachment';
        }

        if ($fileName) {
            if (!file_exists($path . $folder .'/'. $ciId)) {
                mkdir($path . $folder .'/'. $ciId);
            }

            if (!rename($path . $folder .'/'. $fileName, $path . $folder .'/'. $ciId .'/'. $fileName)) {
                $this->logger - log("failed to move $fileName from '" . $path . $folder . "' to " . $path . $folder .'/'. $ciId . '/', Zend_Log::ERR);
                //throw new Exception_File_RenamingFailed();
            }

        }

        // Lucene Document Search - currently disabled cause of huge amount of data
        //$this->addDocumentToFilesearch($path.$folder.'/'.$ciId.'/', $fileName, $fileName, $ciId);


        $data                             = array();
        $data[Db_CiAttribute::VALUE_TEXT] = $fileName;
        $data[Db_CiAttribute::NOTE]       = $note;
        return $data;
    }


    public function getString(&$form, $attribute)
    {
        if (!($attribute[Db_AttributeRole::PERMISSION_READ] === '1' || $attribute[Db_AttributeRole::PERMISSION_WRITE] === '1')) {
            return "";
        }

        $varName            = $attribute['id'] . $attribute['genId'];
        $descriptionVarName = $attribute['id'] . $attribute['genId'] . 'description';
        $filenameVarName    = $attribute['id'] . $attribute['genId'] . 'filename';
        $class              = "";

        //Admin-Mode: show attribute-name
        if (Zend_Registry::get('adminMode') === true) {
            $attribute['note'] = $attribute['description'];
            $link              = "<a href='" . APPLICATION_URL . "attribute/edit/attributeId/" . $attribute['id'] . "'><img class='image' src='" . APPLICATION_URL . "images/navigation/settings.png'></a>";
            $form->$varName->setLabel($link . ' ' . $attribute['name']);
        }

        if ($attribute[Db_AttributeRole::PERMISSION_READ] && !$attribute[Db_AttributeRole::PERMISSION_WRITE]) {


            $ciId                                  = $form->ciid->getValue();
            $attribute[Db_CiAttribute::VALUE_TEXT] = $form->$filenameVarName->getValue();
            $retArray                              = $this->setAttributeValue($attribute, $ciId);


            return '<td>' . $attribute[Db_Attribute::DESCRIPTION] . '</td><td>' . $retArray[Db_CiAttribute::VALUE_TEXT] . '</td>';

        }

        if ($form->$filenameVarName->isRequired()) {
            $class = "required";
        }

        $returnString = "<td>
							<label title=\"" . htmlspecialchars($attribute['note']) . "\" class='" . $class . "'>" . $form->$varName->getLabel() . "</label>
						</td>
						<td>" . $form->$varName->setDecorators(array('Errors', array('ViewHelper', array('tag' => '<div>'))));


        $translator = $this->createTranslator();
        $value      = $translator->translate('currentDocument');

        $returnString .= $form->$descriptionVarName->setDecorators(array('Errors', array('ViewHelper', array('tag' => '<div>'))));

        if ($form->getValue($descriptionVarName)) {
            $value .= ": <strong>" . $form->getValue($descriptionVarName) . "</strong> ";
        }

        $returnString .= $form->$filenameVarName->setDecorators(array('Errors', array('ViewHelper', array('tag' => '<div>'))));

        /* div for dropzone display */
        $returnString .= $form->$filenameVarName->setDecorators(array(
            array(
                'HtmlTag', array(
                'tag'   => 'div',
                'class' => 'dropzone',
                'id'    => 'dropzone',
            ),
            ),
        ));

        if ($attribute['hint']) {
            $returnString .= $form->$varName->setDecorators(array(new Form_Decorator_MyTooltip()));
        }

        if ($form->getValue($filenameVarName)) {
            $value .= $form->getValue($filenameVarName);
        }

        $returnString .= "</td>";

        return $returnString;
    }

    private function addDocumentToFilesearch($dir, $file, $title, $ciId = null)
    {
        try {
            $stat = stat("'" . $dir . $file . "'");
            // store the information in array and add to index
            $data = array();

            // needed 
            $data['Filename'] = $file;
            $data['key']      = $file;

            if (!$ciId)
                $ciId = '0';

            $data['CIID'] = $ciId;

            // optional
            $data['Title']        = $title;
            $data['Subject']      = $file;
            $data['Author']       = $stat['uid'];
            $data['CreationDate'] = date('Y-m-d H:i:s', $stat['mtime']);
            $data['ModDate']      = date('Y-m-d H:i:s', $stat['mtime']);


            $fileType = end(explode(".", $file));


            $fileSearch = new Util_Search_File();
            $fileSearch->createDocument($data, $fileType, $dir .'/'. $file);
        } catch (Exception $e) {
            $logger = Zend_Registry::get('Log');
            $logger->log($e, Zend_Log::ERR);
        }
    }

}