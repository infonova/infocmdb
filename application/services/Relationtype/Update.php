<?php

/**
 *
 *
 *
 */
class Service_Relationtype_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2803, $themeId);
    }


    /**
     * get Update form
     *
     * @param unknown_type $relationTypeId
     *
     * @return Form_Relation_Update
     */
    public function getUpdateRelationTypeForm($citypes)
    {
        $relationConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/relationtype.ini', APPLICATION_ENV);
        $form           = new Form_Relationtype_Update($this->translator, $relationConfig);

        foreach ($citypes as $citype) {
            $form->addCiType($citype[Db_CiType::ID], $citype[Db_CiType::NAME]);
        }

        return $form;
    }

    public function getCiTypes()
    {
        $citypeDaoImpl = new Dao_CiType();
        return $citypeDaoImpl->getCiTypeRowset();
    }


    /**
     * updates a Relationtype by the given parameter
     *
     * @param unknown_type $relationTypeId
     * @param unknown_type $relation
     */
    public function updateRelationType($relationTypeId, $formData, $dbData)
    {
        try {
            $dbUpdate = false;

            foreach ($formData as $key => $value) {
                if ($formData[$key] != $dbData[$key])
                    $updateData[$key] = $value;
            }

            $relation = array();
            if ($updateData['name'] !== null)
                $relation[Db_CiRelationType::NAME] = trim($updateData['name']);
            if ($updateData['description'] !== null)
                $relation[Db_CiRelationType::DESCRIPTION] = trim($updateData['description']);
            if ($updateData['description2'] !== null)
                $relation[Db_CiRelationType::DESCRIPTION_OPTIONAL] = trim($updateData['description2']);
            if ($updateData['note'] !== null)
                $relation[Db_CiRelationType::NOTE] = trim($updateData['note']);
            if ($updateData['color'] !== null)
                $relation[Db_CiRelationType::COLOR] = $updateData['color'];
            if ($updateData['visualize'] !== null)
                $relation[Db_CiRelationType::VISUALIZE] = $updateData['visualize'];

            if (!empty($relation)) {
                $relationDaoImpl = new Dao_CiRelation();
                $rows            = $relationDaoImpl->updateRelation($relationTypeId, $relation);
                $dbUpdate        = true;
            }

            $mapping = $updateData;
            unset($mapping['name']);
            unset($mapping['description']);
            unset($mapping['description2']);
            unset($mapping['note']);
            unset($mapping['color']);
            unset($mapping['visualize']);


            $ciRelationDaoImpl = new Dao_CiRelation();
            $ciTypes           = $this->getCiTypes();
            foreach ($ciTypes as $ciType) {
                if ($mapping[$ciType[Db_User::ID]] === '1') {
                    $ciRelationDaoImpl->saveCiTypeRelation($ciType[Db_CiType::ID], $relationTypeId);
                    $dbUpdate = true;
                } elseif ($mapping[$ciType[Db_User::ID]] === '0' && $dbData[$ciType[Db_User::ID]]) {
                    $ciRelationDaoImpl->deleteCiTypeRelation($ciType[Db_CiType::ID], $relationTypeId);
                    $dbUpdate = true;
                }
            }
            return true;


        } catch (Exception_Relation $e) {
            throw new Exception_Relation_UpdateItemNotFound($e);
        } catch (Exception $e) {
            if (!($e instanceof Exception_Relation))
                throw new Exception_Relation_UpdateFailed($e);
        }
    }
}