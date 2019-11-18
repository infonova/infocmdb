<?php

/**
 *
 * Enter description here ...
 *
 *
 */
class Service_Validation_Create extends Service_Abstract
{

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED   = 'completed';

    const TYPE_INSERT = 'insert';
    const TYPE_UPDATE = 'update';

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3302, $themeId);
    }


    public function createInsertValidation($name, $ciType, $project)
    {
        $data                                      = array();
        $data[Db_ImportFileValidation::NAME]       = $name;
        $data[Db_ImportFileValidation::TYPE]       = self::TYPE_INSERT;
        $data[Db_ImportFileValidation::STATUS]     = self::STATUS_IN_PROGRESS;
        $data[Db_ImportFileValidation::CI_TYPE_ID] = $ciType;
        $data[Db_ImportFileValidation::PROJECT_ID] = $project;

        $daoValidation = new Dao_Validation();
        return $daoValidation->createValidation($data);
    }

    public function createUpdateValidation($name)
    {
        $data                                  = array();
        $data[Db_ImportFileValidation::NAME]   = $name;
        $data[Db_ImportFileValidation::TYPE]   = self::TYPE_UPDATE;
        $data[Db_ImportFileValidation::STATUS] = self::STATUS_IN_PROGRESS;

        $daoValidation = new Dao_Validation();
        return $daoValidation->createValidation($data);
    }

    /**
     *
     * @param type $validationId
     * @param type $ciId
     * @param type $attributeId
     * @param type $ciTypeId
     * @param type $projectId
     * @param type $value
     * @param type $userId
     * @param type $uniqueId
     * @param type $note
     *
     * @return type
     */
    public function addValidationAttribute($validationId, $ciId, $attributeId, $ciTypeId, $projectId, $value, $userId, $uniqueId = null, $note = null)
    {
        $data                                                   = array();
        $data[Db_ImportFileValidationAttributes::VALIDATION_ID] = $validationId;
        $data[Db_ImportFileValidationAttributes::CI_ID]         = $ciId;

        $data[Db_ImportFileValidationAttributes::ATTRIBUTE_ID] = $attributeId;
        $data[Db_ImportFileValidationAttributes::VALUE]        = $value;
        $data[Db_ImportFileValidationAttributes::USER_ID]      = $userId;

        $data[Db_ImportFileValidationAttributes::PROJECT_ID] = $projectId;
        $data[Db_ImportFileValidationAttributes::CI_TYPE_ID] = $ciTypeId;

        if ($uniqueId)
            $data[Db_ImportFileValidationAttributes::UNIQUE_ID] = $uniqueId;

        if ($note)
            $data[Db_ImportFileValidationAttributes::NOTE] = $note;

        $daoValidation = new Dao_Validation();
        return $daoValidation->addValidationAttribute($data);
    }

    public function updateValidation($id, $citype, $project)
    {
        $data                                      = array();
        $data[Db_ImportFileValidation::CI_TYPE_ID] = $citype;
        $data[Db_ImportFileValidation::PROJECT_ID] = $project;


        $daoValidation = new Dao_Validation();
        return $daoValidation->updateValidation($id, $data);
    }
}