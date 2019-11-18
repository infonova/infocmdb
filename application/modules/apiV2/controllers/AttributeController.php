<?php

require_once 'V2BaseController.php';

class ApiV2_AttributeController extends V2BaseController
{

    /**
     * @throws Exception_AttributeType_InvalidClassName
     * @throws Exception_Attribute_RetrieveFailed
     * @throws Exception_Attribute_UpdateFailed
     * @throws Zend_Controller_Response_Exception
     * @throws Zend_Form_Exception
     */
    public function update(int $id)
    {
        try {
            // fetch request parameter
            $updateData      = $this->getJsonParam('attribute', array());
            $updateDataArray = (array)$updateData;

            // init helpers
            $user                   = parent::getUserInformation();
            $attributeServiceGet    = new Service_Attribute_Get($this->translator, $this->logger, $user->getThemeId());
            $attributeServiceUpdate = new Service_Attribute_Update($this->translator, $this->logger, $user->getThemeId());

            // init variables
            $dbData         = $attributeServiceGet->getAttibuteData($id);
            $validationData = array_merge($dbData, $updateDataArray);
            /** @var Util_AttributeType_Type_Abstract $attributeType */
            $attributeType  = Util_AttributeType_Factory::get($dbData['attributeType']);
            $ciTypes        = $attributeServiceUpdate->getAllCiTypes();
            $roles          = $attributeServiceUpdate->getAllRoles();
            $form           = $attributeServiceUpdate->getUpdateAttributeForm($ciTypes, $roles);
            $individualForm = $attributeType->getIndividualWizardFormParts(
                $this->translator,
                $options = array(
                    'attributeID' => $id,
                )
            );

            // logic
            $mainFormValid = $form->isValid($validationData);
            $subFormValid  = (!$individualForm || $individualForm->isValid($validationData));

            if ($subFormValid && $mainFormValid) {
                $attributeServiceUpdate->updateAttribute($id, $updateDataArray, $dbData);
                $this->outputContent('OK');
            } else {
                $errors = array_merge($form->getMessages(), $individualForm->getMessages());
                $this->outputValidationError($errors);
            }
        } catch (Exception_Attribute_RetrieveNotFound $e) {
            $this->outputHttpStatusNotFound();
        }
    }
}