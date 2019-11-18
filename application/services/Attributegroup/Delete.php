<?php

/**
 *
 *
 *
 */
class Service_Attributegroup_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2704, $themeId);
    }


    /**
     * deletes a View Type by the given View Type Id
     *
     * @param $attributeGroupId the View Type to delete
     *
     * @throws Exception_AttributeGroup_DeleteFailed
     */
    public function deleteAttributeGroup($attributeGroupId)
    {
        try {
            $status                = 0;
            $attributeGroupDaoImpl = new Dao_AttributeGroup();
            $attributeDaoImpl      = new Dao_Attribute();
            $countAttributeGroups  = $attributeDaoImpl->getCountAttributeGroupsByAttributeGroupId($attributeGroupId);
            //var_dump($countAttributeGroups['cnt']);die();
            if ($countAttributeGroups['cnt'] != 0) {
                $rows   = $attributeGroupDaoImpl->deactivateAttributeGroup($attributeGroupId);
                $status = 1;
            } else {
                $rows   = $attributeGroupDaoImpl->deleteAttributeGroup($attributeGroupId);
                $status = 2;
            }

            return $status;

        } catch (Exception $e) {
            throw new Exception_AttributeGroup_DeleteFailed($e);
        }
    }

    /**
     * activates an inactive view type by the given vieTypeId
     *
     * @param $attributeGroupId View Type to activate
     *
     * @throws Exception_AttributeGroup_ActivateFailed
     */
    public function activateAttributeGroup($attributeGroupId)
    {
        try {
            $attributeGroupDaoImpl = new Dao_AttributeGroup();
            $attributeGroupDaoImpl->activateAttributeGroup($attributeGroupId);
        } catch (Exception $e) {
            throw new Exception_AttributeGroup_ActivateFailed($e);
        }
    }

}