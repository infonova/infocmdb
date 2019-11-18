<?php

/**
 *
 *
 *
 */
class Service_Attributegroup_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2703, $themeId);
    }


    /**
     *
     * @param int $attributeGroupId
     */
    public function getUpdateAttributeGroupForm($attributeGroupId)
    {
        $createAttributeGroupConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/attributegroup.ini', APPLICATION_ENV);

        $attributeGroupDaoImpl = new Dao_AttributeGroup();
        $select                = $attributeGroupDaoImpl->getAttributeGroupRowset();

        // put the data in useable content
        $arrayCopy       = array();
        $arrayCopy[null] = ' ';
        foreach ($select as $row) {
            if ($row[Db_AttributeGroup::ID] != $attributeGroupId)
                $arrayCopy[$row[Db_AttributeGroup::ID]] = $row[Db_AttributeGroup::NAME];
        }

        return new Form_Attributegroup_Update($this->translator, $arrayCopy, $createAttributeGroupConfig, $attributeGroupId);
    }


    /**
     * updates a View Type by the given View Type Id and values
     *
     * @param int   $attributeGroupId
     * @param array $values
     *
     * @throws Exception_AttributeGroup_UpdateItemNotFound if no items are updated
     * @throws Exception_AttributeGroup_UpdateFailed on all other errors
     */
    public function updateAttributeGroup($attributeGroupId, array $attributeGroup)
    {
        try {
            $attributeGroupDaoImpl = new Dao_AttributeGroup();
            $rows                  = $attributeGroupDaoImpl->updateAttributeGroup($attributeGroupId, $attributeGroup);
            if ($rows < 1) {
                throw new Exception_AttributeGroup();
            }
        } catch (Exception_AttributeGroup $e) {
            throw new Exception_AttributeGroup_UpdateItemNotFound($e);
        } catch (Exception $e) {
            if (!($e instanceof Exception_AttributeGroup))
                throw new Exception_AttributeGroup_UpdateFailed($e);
        }
    }
}