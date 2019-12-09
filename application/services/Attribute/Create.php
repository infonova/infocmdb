<?php

/**
 *
 *
 *
 */
class Service_Attribute_Create extends Service_Abstract
{

    private static $attributeNamespace = 'AttributeController';

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 102, $themeId);
    }

    public function getAllCiTypes()
    {
        $ciTypeDaoImpl = new Dao_CiType();
        return $ciTypeDaoImpl->getCiTypeRowset();
    }

    public function getAllRoles()
    {
        $attributeDaoImpl = new Dao_Attribute();
        return $attributeDaoImpl->getRolesForAttributes();
    }

    public function createAttribute($formData, $userId)
    {
        try {
            $attribute                                   = array();
            $attribute[Db_Attribute::NAME]               = trim($this->xssClean($formData['name']));
            $attribute[Db_Attribute::DESCRIPTION]        = trim($this->xssClean(
                $formData['description']));
            $attribute[Db_Attribute::NOTE]               = trim($this->xssClean($formData['note']));
            $attribute[Db_Attribute::HINT]               = Form_Decorator_MyTooltip::sanitizeHint($formData['hint']);
            $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID]  = $formData['attributeType'];
            $attribute[Db_Attribute::ATTRIBUTE_GROUP_ID] = $formData['displayType'];
            $attribute[Db_Attribute::ORDER_NUMBER]       = $formData['sorting'];
            $attribute[Db_Attribute::COLUMN]             = $formData['column'];
            $attribute[Db_Attribute::IS_UNIQUE]          = ($formData['text']['uniqueConstraint']) ? '1' : '0';
            $attribute[Db_Attribute::IS_NUMERIC]         = ($formData['text']['isNumeric']) ? '1' : '0';
            $attribute[Db_Attribute::IS_BOLD]            = ($formData['highlightAttribute']) ? '1' : '0';
            $attribute[Db_Attribute::IS_EVENT]           = ($formData['isevent']) ? '1' : '0';
            $attribute[Db_Attribute::IS_UNIQUE_CHECK]    = ($formData['text']['uniqueCheck']) ? '1' : '0';
            $attribute[Db_Attribute::WORKFLOW_ID]        = $formData['workflow_id'];

            $isAutocomplete = ($formData['citype']['ciTypeAutocomplete']) ? '1' : '0';

            if (!$isAutocomplete &&
                $formData['query']['ciTypeAutocomplete'] !== null)
                ($formData['query']['ciTypeAutocomplete']) ? '1' : '0';

            $attribute[Db_Attribute::IS_AUTOCOMPLETE] = $isAutocomplete;

            $attribute[Db_Attribute::TAG] = $formData['xml'];

            if ($formData['text']) {
                $attribute[Db_Attribute::INPUT_MAXLENGTH] = $formData['text']['inputLength'];
                $attribute[Db_Attribute::TEXTAREA_ROWS]   = $formData['text']['textfieldHeight'];
                $attribute[Db_Attribute::TEXTAREA_COLS]   = $formData['text']['textfieldWidth'];
                $attribute[Db_Attribute::REGEX]           = $formData['text']['regex'];
            }

            if ($formData['citype']) {
                $attribute[Db_Attribute::TEXTAREA_COLS]         = $formData['citype']['textfieldWidth'];
                $attribute[Db_Attribute::IS_PROJECT_RESTRICTED] = ($formData['citype']['ProjectRestriction']) ? '1' : '0';
            }

            if ($formData['query']) {

                $attribute[Db_Attribute::TEXTAREA_COLS]         = $formData['query']['textfieldWidth'];
                $attribute[Db_Attribute::IS_AUTOCOMPLETE]       = ($formData['query']['ciTypeAutocomplete']) ? '1' : '0';
                $attribute[Db_Attribute::IS_PROJECT_RESTRICTED] = ($formData['query']['ProjectRestriction']) ? '1' : '0';

                if ($formData['query']['autocomplete'] !== null) {
                    switch ($formData['query']['autocomplete']) {
                        case 'select_one':
                            $attribute[Db_Attribute::IS_AUTOCOMPLETE] = '0';
                            $attribute[Db_Attribute::IS_MULTISELECT]  = '0';
                            break;
                        case 'autocomplete_one':
                            $attribute[Db_Attribute::IS_AUTOCOMPLETE] = '1';
                            $attribute[Db_Attribute::IS_MULTISELECT]  = '0';
                            break;
                        case 'autocomplete_multiple':
                            $attribute[Db_Attribute::IS_AUTOCOMPLETE] = '1';
                            $attribute[Db_Attribute::IS_MULTISELECT]  = '1';
                            break;
                        case 'autocomplete_multiple_with_counter':
                            $attribute[Db_Attribute::IS_AUTOCOMPLETE] = '1';
                            $attribute[Db_Attribute::IS_MULTISELECT]  = '2';
                            break;
                    }
                }
            }

            if (isset($formData['query']['displayStyle']) && $formData['query']['displayStyle'] !== null) {
                if ($formData['query']['displayStyle'] === '') {
                    $attribute[Db_Attribute::DISPLAY_STYLE] = null;
                } else {
                    $attribute[Db_Attribute::DISPLAY_STYLE] = $formData['query']['displayStyle'];
                }
            }

            if ($formData['query']['isLight'] !== null)
                $attribute[Db_Attribute::IS_EVENT] = $formData['query']['isLight'];

            $attribute[Db_Attribute::IS_ACTIVE] = '1';
            $attribute[Db_Attribute::USER_ID]   = $userId;

            $attributeDaoImpl = new Dao_Attribute();

            $attributeId = $attributeDaoImpl->insertAttribute($attribute);

            if (!$attributeId) {
                throw new Exception_Attribute_InsertFailed();
            } else {
                try {
                    if ($formData['citype']) {
                        $attributeDaoImpl->deleteCiTypeAttributes($attributeId);
                        $attributeDaoImpl->insertCiTypeAttribute(
                            $formData['citype']['ciType'],
                            $formData['citype']['ciTypeAttributes'],
                            $attributeId);
                    }

                    if ($formData['query']['query']) {
                        $attributeDaoImpl->insertDefaultQuery($attributeId,
                            $formData['query']['query']);
                    }

                    if ($formData['filter']['filter']) {
                        $keywords  = array();
                        $statement = $formData['filter']['filter'];
                        $qId       = $attributeDaoImpl->insertDefaultQuery(
                            $attributeId, $statement);
                        preg_match_all("/:([^:]*):/", $statement, $keywords);

                        foreach ($keywords[1] as $val) {
                            $attributeDaoImpl->insertDefaultQueryParameter($qId,
                                $val);
                        }
                    }

                    if ($formData['queryPersist']) {
                        if ($formData['queryPersist']['usescript']) {
                            $adf = ':script#:' .
                                $formData['queryPersist']['scriptfilename'];
                        } else {
                            $adf = $formData['queryPersist']['query'];
                        }
                        $attributeDaoImpl->insertDefaultQuery($attributeId,
                            $adf);
                    }

                    if ($formData['info']['attributeInfo'])
                        $attributeDaoImpl->insertAttributeDefaultValuesById(
                            $formData['info']['attributeInfo'], $attributeId);

                    if ($formData['text']['defaultvalue'] != '') {
                        $attributeDaoImpl->insertAttributeDefaultValuesById(
                            $formData['text']['defaultvalue'], $attributeId);
                    }

                    $mapping = $formData;

                    $ciTypeDaoImpl = new Dao_CiType();
                    foreach ($mapping as $id => $value) {
                        if (strpos($id, 'citypeId_') === 0) {
                            if ($value)
                                $ciTypeDaoImpl->saveCiTypeAttribute(
                                    substr($id, strlen('citypeId_')),
                                    $attributeId,
                                    (($value == '1') ? '1' : '0'));
                        } elseif (strpos($id, 'roleId_') === 0) {
                            if ($value == '0') {
                                $read  = 0;
                                $write = 0;
                            } elseif ($value == '1') {
                                $read  = 1;
                                $write = 0;
                            } elseif ($value == '2') {
                                $read  = 1;
                                $write = 1;
                            }
                            $attributeDaoImpl->updateRolesByAttributeId(
                                $attributeId, substr($id, strlen('roleId_')),
                                $read, $write);
                        } elseif (strpos($id, 'option_') === 0 &&
                            !strpos($id, 'ordernumber')) {
                            $attributeDaoImpl->insertAttributeDefaultValuesById(
                                substr($id, strlen('option_')), $attributeId,
                                intval($mapping[$id . 'ordernumber']));
                        }
                    }

                    if ($formData['options']) {
                        $options = $formData['options'];
                        foreach ($options as $id => $value) {
                            if (strpos($id, 'option_') === 0 &&
                                !strpos($id, 'ordernumber')) {
                                $attributeDaoImpl->insertAttributeDefaultValuesById(
                                    $value, $attributeId,
                                    intval($options[$id . 'ordernumber']));
                            }
                        }
                    }
                } catch (Exception $e) {
                    throw new Exception_Attribute_InsertFailed($e);
                }

                return $attributeId;
            }
        } catch (Exception $e) {
            throw new Exception_Attribute_InsertFailed($e);
        }
    }

    public function getCreateAssignCiTypeForm($attributeId)
    {
        $form = new Form_Attribute_AssignCiType($this->translator);
        $form->setAction(
            APPLICATION_URL . 'attribute/citypeattribute/attributeId/' .
            $attributeId . '/');
        return $form;
    }

    public function getCreateAttributeForm($citypes, $roles)
    {
        $createAttributeConfig = new Zend_Config_Ini(
            APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);

        $attributeTypeDaoImpl = new Dao_AttributeType();
        $selectAT             = $attributeTypeDaoImpl->getAttributeTypeRowset();

        $attributeTypeList = array();

        foreach ($selectAT as $row) {
            $attributeTypeList[$row[Db_AttributeType::ID]] = $this->translator->translate(
                'name_' . $row[Db_AttributeType::NAME]);
        }

        $attributeGroupDaoImpl = new Dao_AttributeGroup();
        $selectVT              = $attributeGroupDaoImpl->getAttributeGroupRowset();

        // put the data in useable content
        $attributeGroupList = array();
        foreach ($selectVT as $row) {
            $attributeGroupList[$row[Db_AttributeGroup::ID]] = $row[Db_AttributeGroup::DESCRIPTION];
        }


        $form = new Form_Attribute_Create($this->translator, $attributeTypeList,
            (isset($attributeValueList) ? $attributeValueList : null), $attributeGroupList, $createAttributeConfig);

        foreach ($citypes as $citype) {
            $form->addCiType($citype[Db_CiType::ID], $citype[Db_CiType::NAME], $citype[Db_CiType::DESCRIPTION], $citype[Db_CiType::IS_ACTIVE]);
        }

        foreach ($roles as $role) {
            $form->addRole($role[Db_Role::ID], $role[Db_Role::NAME], $role[Db_Role::DESCRIPTION], $role[Db_Role::IS_ACTIVE]);
        }

        return $form;
    }

    public function getCiTypeAttributeFormElement($id)
    {
        $ciTypeDaoImpl = new Dao_CiType();
        $hierarchy     = $ciTypeDaoImpl->retrieveCiTypeHierarchy($id);

        $ciTypeList = '0';
        foreach ($hierarchy as $hier) {
            $ciTypeList .= ', ' . $hier;
        }
        $attributes = $ciTypeDaoImpl->getAttributesByCiTypeHierarchy(
            $ciTypeList);

        foreach ($attributes as $attribute) {
            $attributesSelect[$attribute['id']] = $attribute['name'];
        }
        if (isset($attributesSelect)) {
            $attributeElement = new Zend_Form_Element_MultiCheckbox(
                'ciTypeAttributes');
            $attributeElement->setLabel('attribute')->addMultiOptions(
                $attributesSelect);
        } else {
            $attributeElement = new Zend_Form_Element_MultiCheckbox(
                'ciTypeAttributes');
            $attributeElement->setLabel('attribute');
            // $this->view->render('No Items found');
        }
        return $attributeElement;
    }
}
