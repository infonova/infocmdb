<?php

/**
 *
 *
 *
 */
class Service_Attribute_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 101, $themeId);
    }

    /**
     * Gets script located in executeablefolder with given name
     *
     * @param string $scriptname filename incl. ".pl"
     *
     * @return string file text
     */
    public function getScriptContent($scriptname)
    {
        $config         = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $useDefaultPath = $config->file->upload->path->default;
        $defaultFolder  = $config->file->upload->path->folder;

        $path = "";
        if ($useDefaultPath) {
            $path = APPLICATION_PUBLIC . $defaultFolder;
        } else {
            $path = $config->file->upload->path->custom;
        }

        $content = '';
        if (is_file($path . $config->file->upload->executeable->folder .'/'. $scriptname)) {
            $handle  = fopen($path . $config->file->upload->executeable->folder .'/'. $scriptname, 'r');
            $content = fread($handle, filesize($path . $config->file->upload->executeable->folder .'/'. $scriptname));
            fclose($handle);
        }

        // if content is not utf8 --> convert, cause file will be saved in utf8
        if (!preg_match('!!u', $content)) {
            $content = utf8_encode($content);
        }

        return $content;
    }

    public function getAttibuteData($attributeId)
    {
        try {
            $attributeDaoImpl = new Dao_Attribute();
            $attribute        = $attributeDaoImpl->getAttribute($attributeId);

            if (!$attribute) {
                throw new Exception_Attribute(array());
            }

            $dbFormData                          = array();
            $dbFormData['name']                  = trim($attribute[Db_Attribute::NAME]);
            $dbFormData['description']           = trim($attribute[Db_Attribute::DESCRIPTION]);
            $dbFormData['note']                  = trim($attribute[Db_Attribute::NOTE]);
            $dbFormData['hint']                  = trim($attribute[Db_Attribute::HINT]);
            $dbFormData['attributeType']         = $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID];
            $dbFormData['uniqueConstraint']      = $attribute[Db_Attribute::IS_UNIQUE];
            $dbFormData['isNumeric']             = $attribute[Db_Attribute::IS_NUMERIC];
            $dbFormData['displayType']           = $attribute[Db_Attribute::ATTRIBUTE_GROUP_ID];
            $dbFormData['sorting']               = $attribute[Db_Attribute::ORDER_NUMBER];
            $dbFormData['highlightAttribute']    = $attribute[Db_Attribute::IS_BOLD];
            $dbFormData['xml']                   = trim($attribute[Db_Attribute::TAG]);
            $dbFormData['uniqueCheck']           = $attribute[Db_Attribute::IS_UNIQUE_CHECK];
            $dbFormData['multiselect']           = $attribute[Db_Attribute::IS_MULTISELECT];
            $dbFormData['workflow_id']           = $attribute[Db_Attribute::WORKFLOW_ID];
            if(!empty($dbFormData['workflow_id'])) {
                $dbFormData['workflow_info'] = '<a ' .
                    'target="_blank" ' .
                    'href="' . APPLICATION_URL . '/workflow/detail/workflowId/' . $attribute[Db_Attribute::WORKFLOW_ID] . '" ' .
                    '>' .
                    $this->translator->translate('attributeGoToWorkflow') .
                    '</a>';
            }
            $dbFormData['isevent']               = $attribute[Db_Attribute::IS_EVENT];
            $dbFormData['column']                = $attribute[Db_Attribute::COLUMN];
            $dbFormData['attributeType']         = $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID];

            $dbFormData['citype']['textfieldWidth']     = $attribute[Db_Attribute::TEXTAREA_COLS];
            $dbFormData['citype']['ciTypeAutocomplete'] = $attribute[Db_Attribute::IS_AUTOCOMPLETE];
            $dbFormData['citype']['ProjectRestriction'] = $attribute[Db_Attribute::IS_PROJECT_RESTRICTED];
            $dbFormData['query']['autocomplete']        = 'select_one';
            if ($attribute[Db_Attribute::IS_MULTISELECT] == '1') {
                $dbFormData['query']['autocomplete'] = 'autocomplete_multiple';
            } elseif ($attribute[Db_Attribute::IS_MULTISELECT] == '2') {
                $dbFormData['query']['autocomplete'] = 'autocomplete_multiple_with_counter';
            } elseif ($attribute[Db_Attribute::IS_AUTOCOMPLETE] == '1') {
                $dbFormData['query']['autocomplete'] = 'autocomplete_one';
            }

            $dbFormData['query']['textfieldWidth']     = $attribute[Db_Attribute::TEXTAREA_COLS];
            $dbFormData['query']['isLight']            = $attribute[Db_Attribute::IS_EVENT];
            $dbFormData['query']['ProjectRestriction'] = $attribute[Db_Attribute::IS_PROJECT_RESTRICTED];
            $dbFormData['query']['displayStyle']       = $attribute[Db_Attribute::DISPLAY_STYLE];

            $dbFormData['inputLength']     = $attribute[Db_Attribute::INPUT_MAXLENGTH];
            $dbFormData['textfieldHeight'] = $attribute[Db_Attribute::TEXTAREA_ROWS];
            $dbFormData['textfieldWidth']  = $attribute[Db_Attribute::TEXTAREA_COLS];
            $dbFormData['regex']           = $attribute[Db_Attribute::REGEX];

            $value                      = $attributeDaoImpl->getAttributeDefaultValues($attributeId);
            $dbFormData['defaultvalue'] = $value[0][Db_AttributeDefaultValues::VALUE];


            if ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_Query::ATTRIBUTE_TYPE_ID) {
                $query                        = $attributeDaoImpl->getDefaultQuery($attributeId);
                $dbFormData['query']['query'] = $query[Db_AttributeDefaultQueries::QUERY];
            } elseif ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_QueryPersist::ATTRIBUTE_TYPE_ID) {
                $query                               = $attributeDaoImpl->getDefaultQuery($attributeId);
                $dbFormData['queryPersist']['query'] = $query[Db_AttributeDefaultQueries::QUERY];
            } elseif ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_Filter::ATTRIBUTE_TYPE_ID) {
                $query                          = $attributeDaoImpl->getDefaultQuery($attributeId);
                $dbFormData['filter']['filter'] = $query[Db_AttributeDefaultQueries::QUERY];
            } elseif ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_SelectQuery::ATTRIBUTE_TYPE_ID) {
                $query                            = $attributeDaoImpl->getDefaultQuery($attributeId);
                $dbFormData['query']['query']     = $query[Db_AttributeDefaultQueries::QUERY];
                $dbFormData['query']['listQuery'] = $query[Db_AttributeDefaultQueries::LIST_QUERY];
            } elseif ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_SelectPopup::ATTRIBUTE_TYPE_ID) {
                $query                          = $attributeDaoImpl->getDefaultQuery($attributeId);
                $dbFormData['filter']['filter'] = $query[Db_AttributeDefaultQueries::QUERY];
            } elseif ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_CiType::ATTRIBUTE_TYPE_ID
                || $attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_CiTypePersist::ATTRIBUTE_TYPE_ID) {
                $ciType = $attributeDaoImpl->getDefaultCiType($attributeId);
                if ($ciType) {
                    $dbFormData['citype']['ciType'] = $ciType[0][Db_AttributeDefaultCitype::CI_TYPE_ID];

                    $ciTypeAttributes = $attributeDaoImpl->getDefaultCiTypeAttributes($ciType[0][Db_AttributeDefaultCitype::ID]);
                    foreach ($ciTypeAttributes as $attr)
                        $dbFormData['citype']['ciTypeAttributes'][] = $attr[Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_ID];
                }
            } elseif ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == Util_AttributeType_Type_Info::ATTRIBUTE_TYPE_ID) {
                $value                               = $attributeDaoImpl->getAttributeDefaultValues($attributeId);
                $dbFormData['info']['attributeInfo'] = $value[0][Db_AttributeDefaultValues::VALUE];
            }

            $citypeDaoImpl = new Dao_CiType();
            $citypes       = $citypeDaoImpl->getCiTypesByAttributeId($attributeId);
            if ($citypes) {
                foreach ($citypes as $citype) {
                    if ($citype[Db_CiTypeAttribute::IS_MANDATORY])
                        $dbFormData['citypeId_' . $citype[Db_CiType::ID]] = 1;
                    else
                        $dbFormData['citypeId_' . $citype[Db_CiType::ID]] = 2;
                }
            }

            $roles = $attributeDaoImpl->getCurrentAttributeRolesByAttributeId($attribute[Db_Attribute::ID]);
            if ($roles) {
                foreach ($roles as $role) {
                    if ($role[Db_AttributeRole::PERMISSION_READ] && $role[Db_AttributeRole::PERMISSION_WRITE])
                        $dbFormData['roleId_' . $role[Db_Role::ID]] = 2;
                    elseif ($role[Db_AttributeRole::PERMISSION_READ])
                        $dbFormData['roleId_' . $role[Db_Role::ID]] = 1;
                }
            }

            return $dbFormData;
        } catch (Exception_Attribute $e) {
            throw new Exception_Attribute_RetrieveNotFound($e);
        } catch (Exception $e) {
            if (!($e instanceof Exception_Attribute))
                throw new Exception_Attribute_RetrieveFailed($e);
        }
    }

    public function getAttributesToOrder($attributeId)
    {

        $attributeDaoImpl = new Dao_Attribute();
        $attributes       = $attributeDaoImpl->getAttributeToOrder($attributeId);
        return $attributes;
    }

    public function getAttibute($attributeId)
    {
        try {
            $attributeDaoImpl = new Dao_Attribute();
            $attribute        = $attributeDaoImpl->getAttribute($attributeId);
            if (!$attribute) {
                throw new Exception_Attribute();
            }
            return $attribute;
        } catch (Exception_Attribute $e) {
            throw new Exception_Attribute_RetrieveNotFound($e);
        } catch (Exception $e) {
            if (!($e instanceof Exception_Attribute))
                throw new Exception_Attribute_RetrieveFailed($e);
        }
    }

    public function getCiTypes($attributeId)
    {
        try {
            $ciTypeDaoImpl = new Dao_CiType();
            return $ciTypeDaoImpl->getCiTypesByAttributeId($attributeId);
        } catch (Exception_Attribute $e) {
            throw new Exception_Attribute_RetrieveNotFound($e);
        } catch (Exception $e) {
            if (!($e instanceof Exception_Attribute))
                throw new Exception_Attribute_RetrieveFailed($e);
        }
    }

    public function getRoles($attributeId)
    {
        try {
            $attributeDaoImpl = new Dao_Attribute();
            return $attributeDaoImpl->getCurrentAttributeRolesByAttributeId($attributeId);
        } catch (Exception_Attribute $e) {
            throw new Exception_Attribute_RetrieveNotFound($e);
        } catch (Exception $e) {
            if (!($e instanceof Exception_Attribute))
                throw new Exception_Attribute_RetrieveFailed($e);
        }
    }


    /**
     * retrieves a list of attributes by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getAttributeList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $this->logger->log("Service_Attribute: getAttributeList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/attribute.ini', APPLICATION_ENV);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['attribute'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('Service_Attribute: getAttributeList page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }


        $attributeTypeDaoImpl = new Dao_Attribute();

        $form   = new Form_Filter($this->translator);
        $select = array();
        if ($filter) {
            $select                = $attributeTypeDaoImpl->getAttributesWithFilter($filter, $orderBy, $direction);
            $filterArray           = array();
            $filterArray['search'] = $filter;
            $form->populate($filterArray);
        } else {
            $select = $attributeTypeDaoImpl->getAttributes($orderBy, $direction);
        }

        unset($attributeTypeDaoImpl);


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $result               = array();
        $result['searchForm'] = $form;
        $result['paginator']  = $paginator;
        return $result;
    }

}
