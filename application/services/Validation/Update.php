<?php

/**
 *
 *
 *
 */
class Service_Validation_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3303, $themeId);
    }


    public function closeParent($validationId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();
            $validationDaoImpl->matchImportFile($validationId);
        } catch (Exception $e) {
            throw new Exception_Validation_CloseParentFailed($e);
        }
    }


    public function match($validationId, $userId)
    {
        $notification = array();
        $redirect     = 'validation/index';

        try {
            $validationDaoImpl = new Dao_Validation();
            $validation        = $validationDaoImpl->getValidation($validationId);
        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }

        $type = $validation[Db_ImportFileValidation::TYPE];

        if ($type == 'update') {
            return $this->matchFileUpdate($validationId, $userId);
        } else {
            return $this->matchFileInsert($validationId, $userId);
        }


        return array(
            'redirect'     => $redirect,
            'notification' => $notification,
        );
    }


    /**
     *
     * Enter description here ...
     *
     * @param unknown_type $validationId
     * @param unknown_type $type -> validationAttributeId
     * @param unknown_type $formdata
     */
    public function matchUpdate($validationId, $userId, $formdata)
    {
        $notification = array();
        $redirect     = 'validation/detail/type/update/validationId/' . $validationId;

        try {
            // get Attributes
            $attributeIdList = null;
            $cnt             = 0;
            foreach ($formdata as $key => $value) {
                if (is_numeric($key)) {
                    if ($attributeIdList)
                        $attributeIdList .= ', ';
                    $attributeIdList .= $key;
                    $cnt++;
                }
            }

            if ($cnt <= 0) {
                return array(
                    'redirect'     => $redirect,
                    'notification' => $notification,
                );
            }


            $validationDaoImpl = new Dao_Validation();
            $attributeList     = $validationDaoImpl->getIdleAttributesByIdList($validationId, $attributeIdList);


            $historyId       = null;
            $ciServiceUpdate = new Service_Ci_Update($this->translator, $this->logger, $this->getThemeId());
            foreach ($attributeList as $validationAttribute) {

                if ($validationAttribute['ciAttributeId']) {
                    $historyId = $ciServiceUpdate->updateValidatedAttributeValue($validationAttribute, null, $userId, null);
                } else {
                    $historyId = $ciServiceUpdate->createValidatedAttribute($validationAttribute, null, $userId, null);
                }

                $this->matchValidationAttribute($validationAttribute[Db_ImportFileValidationAttributes::ID], $userId);
            }

            $validationServiceGet = new Service_Validation_Get($this->translator, $this->logger, $this->getThemeId());
            $checkValidation      = $validationServiceGet->checkAllAttributesValidated($validationId);
            if ($checkValidation) {
                $this->closeParent($validationId);
                $redirect = 'validation/index/';
            }

            $notification['success'] = $this->translator->translate('importFileMatchSuccess');

        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }

        return array(
            'redirect'     => $redirect,
            'notification' => $notification,
        );
    }


    public function matchInsert($validationId, $userId, $formdata)
    {
        $notification = array();
        $redirect     = 'validation/detail/type/insert/validationId/' . $validationId;
        try {
            $ciServiceCreate = new Service_Ci_Create($this->translator, $this->logger, $this->getThemeId());

            foreach ($formdata as $curCiId => $val) {
                if (is_numeric($curCiId)) {
                    $ciId = $ciServiceCreate->createCiFromValidation($validationId, $userId, $curCiId);

                    if ($ciId)
                        $notification['success'] = $this->translator->translate('attributesMatchSuccess');
                    else
                        $notification['error'] = $this->translator->translate('attributesMatchFailed');

                    if (!$this->matchValidationAttributes($validationId, $curCiId, $userId)) {
                        $notification['error'] = $this->translator->translate('attributesMatchFailed');
                    }

                }
            }

            $validationServiceGet = new Service_Validation_Get($this->translator, $this->logger, $this->getThemeId());
            $checkValidation      = $validationServiceGet->checkAllAttributesValidated($validationId);

            if ($checkValidation) {
                $this->closeParent($validationId);
                $redirect = 'validation/index';
            }

        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
        return array(
            'redirect'     => $redirect,
            'notification' => $notification,
        );
    }


    /**
     *
     * match whole update file
     *
     * @param unknown_type $validationId
     * @param unknown_type $userId
     */
    public function matchFileUpdate($validationId, $userId)
    {
        $notification = array();
        $redirect     = 'validation/index';

        try {
            $validationDaoImpl = new Dao_Validation();
            $attributeList     = $validationDaoImpl->getIdleValidationAttributesForUpdate($validationId);

            $historyId = null;
            foreach ($attributeList as $validationAttribute) {
                $ciServiceUpdate = new Service_Ci_Update($this->translator, $this->logger, $this->getThemeId());

                if ($validationAttribute[Db_ImportFileValidationAttributes::UNIQUE_ID]) {
                    $historyId = $ciServiceUpdate->updateValidatedAttributeValue($validationAttribute, null, $userId, $historyId);
                } else {
                    $historyId = $ciServiceUpdate->createValidatedAttribute($validationAttribute, null, $userId, $historyId);
                }
            }
            $notification['success'] = $this->translator->translate('importFileMatchSuccess');

            $checkParent   = $this->matchValidation($validationId);
            $checkChildren = $this->closeChildren($validationId, $userId);

            if (!$checkParent || !$checkChildren) {
                $notification['error'] = $this->translator->translate('importFileMatchFailed');
            }

        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
        return array(
            'redirect'     => $redirect,
            'notification' => $notification,
        );
    }


    public function matchFileInsert($validationId, $userId)
    {
        $notification = array();
        $redirect     = 'validation/index';

        try {
            $validationDaoImpl = new Dao_Validation();
            $ciNrList          = $validationDaoImpl->getImportFileNewCisByValidationId($validationId);
            $ciServiceCreate   = new Service_Ci_Create($this->translator, $this->logger, $this->getThemeId());


            foreach ($ciNrList as $newCi) {
                $ciId = $ciServiceCreate->createCiFromValidation($validationId, $userId, $newCi[Db_ImportFileValidationAttributes::CI_ID]);
            }

            $notification['success'] = $this->translator->translate('importFileMatchSuccess');

            $checkParent   = $this->matchValidation($validationId);
            $checkChildren = $this->closeChildren($validationId, $userId);

            if (!$checkParent || !$checkChildren) {
                $notification['error'] = $this->translator->translate('importFileMatchFailed');
            }

        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
        return array(
            'redirect'     => $redirect,
            'notification' => $notification,
        );
    }


    /**
     * updates a Validation by the given Validation Id
     *
     * @param $validationId the Validation to update
     *
     * @throws Exception_Validation_MatchingFailed
     */
    public function matchValidation($validationId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();

            $rows = $validationDaoImpl->matchImportFile($validationId);
            if ($rows != 1) {
                throw new Exception();
            }
            return true;
        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
    }

    /**
     * updates a Validation by the given Validation Id
     *
     * @param $validationId the Validation to update
     *
     * @throws Exception_Validation_MatchingFailed
     */
    public function closeChildren($validationId, $userId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();

            return $validationDaoImpl->matchImportFileAttributesByValidationId($validationId, $userId);
        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
    }

    /**
     * updates a ValidationAttribute by the given Validation Attribute Id
     *
     * @param $attributeId the ValidationAttribute to update
     *
     * @throws Exception_Validation_MatchingFailed
     */
    public function matchValidationAttribute($attributeId, $userId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();

            $rows = $validationDaoImpl->matchImportFileAttribute($attributeId, $userId);
            if ($rows != 1) {
                throw new Exception();
            }
            return true;
        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
    }

    /**
     * creates a new ci by the given Ci Number
     *
     * @param $ciNr the number of the new ci to create
     *
     * @throws Exception_Validation_MatchingFailed
     */
    public function matchValidationAttributes($validationId, $ciNr, $userId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();

            $rows = $validationDaoImpl->matchImportFileAttributes($validationId, $ciNr, $userId);
            return true;
        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
    }

    /**
     * updates a ValidationAttribute by the given Validation Attribute Id
     *
     * @param $attributeId the ValidationAttribute to update
     *
     * @throws Exception_Validation_MatchingFailed
     */
    public function overwriteValidationAttribute($attributeId, $userId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();

            $rows = $validationDaoImpl->overwriteImportFileAttribute($attributeId, $userId);
            if ($rows != 1) {
                throw new Exception();
            }
            return true;
        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
    }
}