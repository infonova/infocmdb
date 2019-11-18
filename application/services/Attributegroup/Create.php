<?php

/**
 *
 *
 *
 */
class Service_Attributegroup_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2702, $themeId);
    }

    /**
     *
     * @param int $attributeGroupId
     */
    public function getCreateAttributeGroupForm()
    {
        $createAttributeGroupConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/attributegroup.ini', APPLICATION_ENV);

        $attributeGroupDaoImpl = new Dao_AttributeGroup();
        $select                = $attributeGroupDaoImpl->getAttributeGroupRowset();

        // put the data in useable content
        $arrayCopy       = array();
        $arrayCopy[null] = ' ';
        foreach ($select as $row) {
            $arrayCopy[$row[Db_AttributeGroup::ID]] = $row[Db_AttributeGroup::NAME];
        }

        return new Form_Attributegroup_Create($this->translator, $arrayCopy, $createAttributeGroupConfig);
    }


    /**
     * creates a View Type by the given values
     *
     * @param array $values
     */
    public function createAttributeGroup($attributeGroup)
    {
        try {
            $attributeGroupDaoImpl = new Dao_AttributeGroup();
            $primary               = $attributeGroupDaoImpl->insertAttributeGroup($attributeGroup);

            if (!$primary) {
                throw new Exception();
            } else {
                return $primary;
            }
        } catch (Exception $e) {
            throw new Exception_AttributeGroup_InsertFailed($e);
        }
    }
}