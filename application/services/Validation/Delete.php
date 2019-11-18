<?php

/**
 *
 *
 *
 */
class Service_Validation_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3304, $themeId);
    }


    public function deleteUpdate($validationId, $userId, $formdata)
    {
        $notification = array();
        $redirect     = 'validation/detail/type/update/validationId/' . $validationId;
        try {
            foreach ($formdata as $attributeId => $val) {
                if (is_numeric($attributeId)) {
                    $this->deleteValidationAttribute($attributeId, $userId);
                }
            }

            $validationServiceGet = new Service_Validation_Get($this->translator, $this->logger, $this->getThemeId());
            $checkValidation      = $validationServiceGet->checkAllAttributesValidated($validationId);
            if ($checkValidation) {
                $this->closeParent($validationId);
                $redirect = 'validation/index/';
            }

            $notification['success'] = $this->translator->translate('attributeDeleteSuccess');
        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
        return array(
            'redirect'     => $redirect,
            'notification' => $notification,
        );
    }


    public function deleteInsert($validationId, $userId, $formdata)
    {
        $notification = array();
        $redirect     = 'validation/detail/type/insert/validationId/' . $validationId;
        try {
            foreach ($formdata as $ciId => $val) {
                if (is_numeric($ciId)) {
                    $this->deleteValidationAttributesByCiNr($validationId, $ciId, $userId);
                }
            }

            $validationServiceGet = new Service_Validation_Get($this->translator, $this->logger, $this->getThemeId());
            $checkValidation      = $validationServiceGet->checkAllAttributesValidated($validationId);
            if ($checkValidation) {
                $this->closeParent($validationId);
                $redirect = 'validation/index/';
            }

            $notification['success'] = $this->translator->translate('attributeDeleteSuccess');
        } catch (Exception $e) {
            throw new Exception_Validation_MatchingFailed($e);
        }
        return array(
            'redirect'     => $redirect,
            'notification' => $notification,
        );
    }

    public function deleteFileValidation($validationId, $userId)
    {
        $redirect     = 'validation/index';
        $notification = array();

        $checkParent   = $this->deleteValidation($validationId);
        $checkChildren = $this->closeChildren($validationId, $userId);

        if ($checkParent && $checkChildren) {
            $notification['success'] = $this->translator->translate('importFileDeleteSuccess');
        } else {
            $notification['error'] = $this->translator->translate('importFileDeleteFailed');
        }

        return array(
            'notification' => $notification,
            'redirect'     => $redirect,
        );
    }


    public function deleteSingleInsert($validationId, $userId, $attributeId)
    {
        $notification = array();
        $redirect     = 'validation/detail/type/insert/validationId/' . $validationId;

        $this->deleteValidationAttribute($attributeId, $userId);

        $validationServiceGet = new Service_Validation_Get($this->translator, $this->logger, $this->getThemeId());
        $checkValidation      = $validationServiceGet->checkAllAttributesValidated($validationId);
        if ($checkValidation) {
            $this->closeParent($validationId);
            $redirect = 'validation/index/';
        }

        $notification['success'] = $this->translator->translate('attributeDeleteSuccess');

        return array(
            'redirect'     => $redirect,
            'notification' => $notification,
        );
    }


    /**
     * deletes a Validation by the given Validation Id
     *
     * @param $validationId the Validation to delete
     *
     * @throws Exception_Validation_DeleteFailed
     */
    public function deleteValidation($validationId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();

            $rows = $validationDaoImpl->deleteImportFile($validationId);
            if ($rows != 1) {
                throw new Exception();
            }
            return true;
        } catch (Exception $e) {
            throw new Exception_Validation_DeleteFailed($e);
        }
    }

    public function closeChildren($validationId, $userId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();
            return $validationDaoImpl->deleteImportFileAttributesByValidationId($validationId, $userId);
        } catch (Exception $e) {
            throw new Exception_Validation_DeleteFailed($e);
        }
    }

    /**
     * deletes a ValidationAttribute by the given Validation Attribute Id
     *
     * @param $attributeId the ValidationAttribute to delete
     *
     * @throws Exception_Validation_DeleteFailed
     */
    public function deleteValidationAttribute($attributeId, $userId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();

            $rows = $validationDaoImpl->deleteImportFileAttribute($attributeId, $userId);
            if ($rows != 1) {
                throw new Exception();
            }

            return true;
        } catch (Exception $e) {
            throw new Exception_Validation_DeleteFailed($e);
        }
    }

    /**
     * deletes a ValidationAttributes by the given Ci Number
     *
     * @param $attributeId the ValidationAttribute to delete
     *
     * @throws Exception_Validation_DeleteFailed
     */
    public function deleteValidationAttributesByCiNr($validationId, $ciNr, $userId)
    {
        try {
            $validationDaoImpl = new Dao_Validation();

            $rows = $validationDaoImpl->deleteImportFileAttributesByCiNr($validationId, $ciNr, $userId);
            return true;
        } catch (Exception $e) {
            throw new Exception_Validation_DeleteFailed($e);
        }
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
}