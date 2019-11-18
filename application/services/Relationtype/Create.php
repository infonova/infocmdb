<?php

/**
 *
 *
 *
 */
class Service_Relationtype_Create extends Service_Abstract
{

    private static $relationNamespace = 'RelationController';

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2802, $themeId);
    }


    public function createRelationType($formData)
    {
        try {
            $relationtype                                          = array();
            $relationtype[Db_CiRelationType::NAME]                 = trim($formData['name']);
            $relationtype[Db_CiRelationType::DESCRIPTION]          = trim($formData['description']);
            $relationtype[Db_CiRelationType::DESCRIPTION_OPTIONAL] = trim($formData['description2']);
            $relationtype[Db_CiRelationType::NOTE]                 = trim($formData['note']);
            $relationtype[Db_CiRelationType::COLOR]                = $formData['color'];
            $relationtype[Db_CiRelationType::VISUALIZE]            = $formData['visualize'];
            $relationtype[Db_CiRelationType::IS_ACTIVE]            = '1';

            $relationDaoImpl = new Dao_CiRelation();
            $relationId      = $relationDaoImpl->insertRelationType($relationtype);

            if (!$relationId) {
                throw new Exception();
            } else {
                try {
                    $mapping = $formData;
                    unset($mapping['name']);
                    unset($mapping['description']);
                    unset($mapping['description2']);
                    unset($mapping['note']);
                    unset($mapping['color']);
                    unset($mapping['visualize']);

                    foreach ($mapping as $id => $value) {
                        if ($value)
                            $relationDaoImpl->saveCiTypeRelation($id, $relationId);
                    }
                } catch (Exception $e) {
                    throw new Exception_Relation_CitypeInsertFailed($e);
                }

                return $relationId;
            }
        } catch (Exception $e) {
            throw new Exception_Relation_InsertFailed($e);
        }


    }

    /**
     * get Relationtype create form
     *
     * @return Form_Relationtype_Create
     */
    public function getCreateRelationTypeForm($citypes)
    {
        $relationConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/relationtype.ini', APPLICATION_ENV);
        $form           = new Form_Relationtype_Create($this->translator, $relationConfig);

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

}