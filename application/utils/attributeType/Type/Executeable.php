<?php

class Util_AttributeType_Type_Executeable extends Util_AttributeType_Type_Abstract
{

    private $_folder;

    const ATTRIBUTE_VALUES_ID = 'text';
    const ATTRIBUTE_TYPE_ID   = 14;
    const ALLOW_EMPTY = true;


    /**
     * returns individual parts for create and update attribute wizard
     */
    public static function getIndividualWizardFormParts($translator, $options = null)
    {
        $workflows    = new Dao_Workflow();
        $workflowList = $workflows->getActiveWorkflows();

        $options = array(
            'workflows' => $workflowList
        );
        return new Form_Attribute_IndividualScript($translator, $options);
    }


    public static function getFolder()
    {
        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);

        if (!$config->file->upload->executeable->folder) {
            return 'executeable';
        }
        return $config->file->upload->executeable->folder;
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


        $input = new Zend_Form_Element_Text($attributeName);
        $input->setLabel($attributeDescription);

        if ($maxLength)
            $input->setAttrib('maxlength', $maxLength);

        if ($cols)
            $input->setAttrib('size', $cols);

        if ($isUnique) {
            $input->addValidator(new Form_Validator_UniqueConstraint($attributeId, $ciId));
            $input->setLabel('(u) ' . $input->getLabel());
        }

        if ($notNull) {
            $input->setRequired(true);
            $input->setAutoInsertNotEmptyValidator(true);
        }

        if (!$write) {
            $input->setAttrib('disabled', true);
            $input->setAttrib('class', 'disabled');
        }

        if ($regex) {
            $input->addValidator('regex', false, array($regex));
        }

        if ($attributeNote) {
            $input->removeDecorator('description');
            $input->setDescription($attributeNote);
            $input->addDecorator(new Form_Decorator_MyTooltip());
        }

        if ($hint) {
            $input->setDescription($this->prepareHintForTooltip($hint));
        }

        $input->setAttrib('title', $attributeNote);
        $input->setAttrib('onmouseout', "UnTip()");

        return array($input);
    }

    public function getCurrentAttributeValue($values, $attribute, $key, $ciId)
    {
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        return array(
            'value'      => $currentVal,
            'allowEmpty' => self::ALLOW_EMPTY,
            'skipUpdate' => true,
        );
    }

    public function addCi($values, $attribute, $ciId)
    {
        $key        = $attribute['genId'];
        $currentVal = $values[$attribute[Db_Attribute::NAME] . $key];

        $data = array();
        return $data;
    }

    public function getString(&$form, $attribute)
    {

        return '';


    }

    public static function insertMissingAttributes($ciId, $historyid = null)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $attributes       = $attributeDaoImpl->getAttributesByAttributeTypeCiID($ciId, Util_AttributeType_Type_Executeable::ATTRIBUTE_TYPE_ID);
        $historyDao       = new Dao_History();

        foreach ($attributes as $attr) {
            if (empty($attr[Db_CiAttribute::ID])) {
                if (!$historyid) {
                    $historyid = $historyDao->createHistory(0, Enum_History::CI_UPDATE);
                }

                //if no id -> create ci-attribute-entry
                $data[Db_CiAttribute::CI_ID]        = $ciId;
                $data[Db_CiAttribute::ATTRIBUTE_ID] = $attr['attribute_id'];
                $data[Db_CiAttribute::IS_INITIAL]   = '1';
                $data[Db_CiAttribute::HISTORY_ID]   = $historyid;
                $attributeDaoImpl->insertCiAttribute($data);
            }
        }
        $attributeDaoImpl = null;

    }


}