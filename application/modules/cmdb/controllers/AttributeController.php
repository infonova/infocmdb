<?php
require_once 'AbstractAppAction.php';

/**
 */
class AttributeController extends AbstractAppAction
{

    private static $attributeNamespace = 'AttributeController';

    private $sessionID;

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation(
                $this->languagePath . '/de/attribute_de.csv',
                'de');
            $this->translator->addTranslation(
                $this->languagePath . '/en/attribute_en.csv',
                'en');
            $this->translator->addTranslation(
                $this->languagePath . '/de/attributetype_de.csv',
                'de');
            $this->translator->addTranslation(
                $this->languagePath . '/en/attributetype_en.csv',
                'en');
            parent::addUserTranslation('attribute');
            parent::addUserTranslation('attributetype');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function itemsperpageAction()
    {
        $itemCountPerPageSession                                = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['attribute'] = $this->_getParam('rowCount');
        $this->_redirect('attribute/index');
        exit;
    }

    public function indexAction()
    {
        $this->logger->log('Attribute index action has been invoked',
            Zend_Log::DEBUG);

        $this->setupItemsCountPerPage('attribute');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->attributePage)) {
            $page = $pageSession->attributePage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                       = $this->_getParam('page');
                $pageSession->attributePage = $page;
            }
        } else {
            $page                       = $this->_getParam('page');
            $pageSession->attributePage = $page;
        }

        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $filter = null;
        if ($this->_hasParam('search')) {
            if (!$this->_getParam('search')) {
                $filterString = '';
            } else {
                $filterString = '/filter/' . $this->_getParam('search') . '/';
            }
            $this->_helper->getHelper('Redirector')->gotoUrl(
                APPLICATION_URL . 'attribute/index/page/' . $page .
                '/orderBy/' . $orderBy . '/direction/' . $direction .
                $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '', $this->_getParam('filter'));
            $filter = str_replace('%', '', $filter);

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);

        $attributeService = new Service_Attribute_Get($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $attributeResult  = $attributeService->getAttributeList($page, $orderBy,
            $direction, $filter);

        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $attributeResult['paginator'];
        $this->view->searchForm = $attributeResult['searchForm']->setAction(
            $this->view->url(
                array(
                    'filter' => $filter,
                    'page'   => null,
                )));
    }

    public function deleteAction()
    {
        $attributeId = $this->_getParam('attributeId');

        if (is_null($attributeId)) {
            throw new Exception_InvalidParameter();
        }

        $attributeService = new Service_Attribute_Delete($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $statusCode       = $attributeService->deleteAttribute($attributeId);

        $notification = array();
        if ($statusCode) {
            switch ($statusCode) {
                case 1:
                    $notification['success'] = $this->translator->translate(
                        'attributeDeleteSuccess');
                    break;
                case 2:
                    $notification['success'] = $this->translator->translate(
                        'attributeDactivateSuccess');
                    break;
                default:
                    $notification['error'] = $this->translator->translate(
                        'attributeDeleteFailed');
                    break;
            }
        } else {
            $notification['error'] = $this->translator->translate(
                'attributeDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('attribute/index/');
    }

    public function activateAction()
    {
        $attributeId = $this->_getParam('attributeId');

        $notification = array();
        try {
            $attributeService = new Service_Attribute_Update($this->translator,
                $this->logger, parent::getUserInformation()->getThemeId());
            $attributeService->activateAttribute($attributeId);
            $notification['success'] = $this->translator->translate(
                'attributeActivateSuccess');
        } catch (Exception_Attribute_ActivationFailed $e) {
            $this->logger->log(
                'User "' . parent::getUserInformation()->getId() .
                '" was unable to activate Attribute "' .
                $attributeId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate(
                'attributeActivateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('attribute/index/');
    }

    public function assigncitypeattributeAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $id = $this->_getParam('id');

        $cloneFromId = $this->_getParam('cloneFromId');

        $form = Util_AttributeType_Type_CiType::getIndividualWizardFormParts(
            $this->translator);
        $form->addAttributes($id);
        if ($cloneFromId != null) {
            $attributeServiceGet = new Service_Attribute_Get($this->translator,
                $this->logger, parent::getUserInformation()->getThemeId());
            $dbData              = $attributeServiceGet->getAttibuteData($cloneFromId);
            $form->populate($dbData);
        }

        $this->view->form = $form->ciTypeAttributes;
    }

    // TODO: REMOVE!!

    /**
     *
     * @deprecated
     *
     *
     */
    public function citypeattributeAction()
    {
        $attributeId = $this->_getParam('attributeId');

        $attributeService = new Service_Attribute_Create($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $form             = $attributeService->getCreateAssignCiTypeForm($attributeId);

        $this->view->form = $form;

        // new attribute added
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($formData['ciType'] != null &&
                count($formData['ciTypeAttributes']) > 0) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);
                $attributeService->insertCiTypeAttributeSession($formData,
                    parent::getUserInformation()->getId());
                $this->_redirect('attribute/wizard-citype/hasCiTypes/1');
            } else {
                $form->populate($formData);
            }
        }
    }

    /**
     * defines the content of the "individual" tab in the attribute wizard
     */
    public function individualwizardtabAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $attributeType = $this->_getParam('attributeTypeId');

        $class = Util_AttributeType_Factory::get($attributeType);
        $form  = $class->getIndividualWizardFormParts($this->translator);

        if (Util_AttributeType_Type_Checkbox::ATTRIBUTE_TYPE_ID ==
            $class::ATTRIBUTE_TYPE_ID ||
            Util_AttributeType_Type_Radio::ATTRIBUTE_TYPE_ID ==
            $class::ATTRIBUTE_TYPE_ID ||
            Util_AttributeType_Type_Select::ATTRIBUTE_TYPE_ID ==
            $class::ATTRIBUTE_TYPE_ID)
            $this->view->options = true;

        $this->view->form = $form;
    }

    public function attributetypehintAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $attributeType = $this->_getParam('attributeTypeId');

        $daoAttributeType = new Dao_AttributeType();
        $at               = $daoAttributeType->getAttributetype($attributeType);

        $description = $this->translator->translate(
            'note_' . $at[Db_AttributeType::NAME]);
        $output      = '<img class="elementHint" onmouseover="Tip(\'' . $description .
            '\')" onmouseout="UnTip()" src="' . APPLICATION_URL .
            '/images/icon/info.png" alt="info">';

        echo $output;
        exit();
    }

    public function createAction()
    {
        $this->logger->log('create attribute Action page invoked',
            Zend_Log::DEBUG);
        $attributeServiceCreate = new Service_Attribute_Create($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $citypes                = $attributeServiceCreate->getAllCiTypes();
        $roles                  = $attributeServiceCreate->getAllRoles();
        $form                   = $attributeServiceCreate->getCreateAttributeForm($citypes,
            $roles);
        // get default individual class and form defined in Form_Attribute_Create
        $class          = Util_AttributeType_Factory::get($form->getValue('attributeType'));
        $individualform = $class->getIndividualWizardFormParts($this->translator);
        $cloneFromId    = $this->_getParam('cloneFromId');

        $this->view->storedAttributesOptions = array();

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();

            $class          = Util_AttributeType_Factory::get($formData['attributeType']);
            $individualform = $class->getIndividualWizardFormParts(
                $this->translator);

            $mainform = $form->isValid($formData);
            $subform  = (!$individualform || $individualform->isValid($formData));

            if ($subform && $mainform) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $attributeId             = $attributeServiceCreate->createAttribute(
                        $formData, parent::getUserInformation()->getId());
                    $notification['success'] = $this->translator->translate(
                        'attributeInsertSuccess');
                    parent::clearNavigationCache();
                } catch (Exception_Attribute_InsertFailed $e) {
                    $this->logger->log(
                        'User "' . parent::getUserInformation()->getId() .
                        '" failed to create Attribute. No items where inserted!',
                        Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate(
                        'attributeInsertFailed');
                } catch (Exception_Attribute_Unknown $e) {
                    $this->logger->log(
                        'User "' . parent::getUserInformation()->getId() .
                        '" encountered an unknown error while creating new Attribute',
                        Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate(
                        'attributeInsertFailed');
                } catch (Exception_Attribute $e) {
                }
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('attribute/index');
            } else {
                $this->view->validatedFormOptions = $formData['options'];
                $form->populate($formData);
                if (isset($individualform)) {
                    $individualform->populate($formData);
                }
            }
        } else if ($cloneFromId != null) {
            $attributeServiceGet = new Service_Attribute_Get($this->translator,
                $this->logger, parent::getUserInformation()->getThemeId());
            $dbData              = $attributeServiceGet->getAttibuteData($cloneFromId);
            $class               = Util_AttributeType_Factory::get($dbData['attributeType']);
            $individualform      = $class->getIndividualWizardFormParts(
                $this->translator,
                $options = array(
                    'attributeID' => $cloneFromId,
                ));
            $dbData['name']      = 'copy_of_' . $dbData['name'];
            $form->populate($dbData);
            if (isset($individualform)) {
                $individualform->populate($dbData);
            }
            $attributeDaoImpl                    = new Dao_Attribute();
            $storedAttributesOptions             = $attributeDaoImpl->getAttributeDefaultValues(
                $cloneFromId, false);
            $this->view->storedAttributesOptions = $storedAttributesOptions;

            $this->logger->log('Cloning AttributeId:' . $cloneFromId, Zend_Log::INFO);


        }

        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce.js');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce_init_' . $this->translator->getLocale() . '.js');

        $this->view->roles                   = $roles;
        $this->view->citypes                 = $citypes;
        $this->view->individualform          = $individualform;
        $this->view->form                    = $form;
        $this->view->filterOptionsPermission = array(
            5 => $this->translator->translate('noFilter'),
            1 => $this->translator->translate('setFilterOptional'),
            2 => $this->translator->translate('setFilterMandatory'),
            4 => $this->translator->translate('setFilterMandatoryOROptional'),
            3 => $this->translator->translate('setFilterX'));
        $this->view->defaultOptionPermission = 5;
    }

    public function mailAction()
    {
        $atributeId = $this->_getParam('attributeId');
        $orderBy    = $this->_getParam('orderBy');

        if (!$atributeId) {
            throw new Exception_InvalidParameter();
        }

        $this->view->attributeId = $atributeId;

        // mapping zwischen attribut und mail herstellen
        $attributeDaoImpl = new Dao_Attribute();
        $attribute        = $attributeDaoImpl->getSingleAttributeWithType($atributeId);

        $mailDaoImpl = new Dao_Mail();

        switch ($attribute[Db_AttributeType::NAME]) {

            case 'select':
            case 'checkbox':
            case 'radio':
                $mail             = $mailDaoImpl->getAttributeMailMappingForDefaultValues(
                    $atributeId, $orderBy);
                $this->view->mail = $mail;
                $this->render('mail-select');
                break;
            default:
                $mail             = $mailDaoImpl->getAttributeMailMapping($atributeId,
                    $orderBy);
                $this->view->mail = $mail;
                $this->render('mail-default');
                break;
        }
    }

    public function removeoptionAction()
    {
        $attributeId = $this->_getParam('attributeId');
        $optionId    = $this->_getParam('optionId');

        $attributeService = new Service_Attribute_Option($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $deleted          = $attributeService->removeOption($attributeId, $optionId);
        if ($deleted) {
            if ($deleted == Service_Attribute_Option::DELETED)
                $notification['success'] = $this->translator->translate(
                    'attributeOptionDeleteSuccess');

            if ($deleted == Service_Attribute_Option::CANNOT_DELETE)
                $notification['error'] = $this->translator->translate(
                    'attributeOptionDeleteFailure');

            if ($deleted == Service_Attribute_Option::DEACTIVATED)
                $notification['success'] = $this->translator->translate(
                    'attributeOptionDeactivateSuccess');
        }
        $this->_helper->FlashMessenger($notification);
        $this->_redirect(
            'attribute/edit/attributeId/' . $attributeId . '/tab/2');
    }

    public function removemailAction()
    {
        $attributeId = $this->_getParam('attributeId');
        $mappingId   = $this->_getParam('mappingId'); // mail attribute mapping id

        if (!$mappingId || !$attributeId) {
            throw new Exception_InvalidParameter();
        }

        $attributeServiceCreate = new Service_Attribute_Create($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $attributeServiceCreate->removeAttributeMailMapping($mappingId);

        $this->_redirect('attribute/mail/attributeId/' . $attributeId);
    }

    /**
     * adds options to a given attribute (zb: for drop down lists)
     *
     * @return unknown_type
     */
    public function optionwizardAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $attributeId = $this->_getParam('attributeId');
        $isChange    = $this->_getParam('isChange');
        $isNew       = $this->_getParam('isNew');

        if (is_null($attributeId)) {
            throw new Exception_InvalidParameter();
        }

        // select all stored options
        $attributeDaoImpl        = new Dao_Attribute();
        $storedAttributesOptions = $attributeDaoImpl->getAttributeDefaultValues(
            $attributeId, false);

        $form       = new Form_Attribute_OptionNew($this->translator, $attributeId);
        $optionForm = new Form_Attribute_OptionChange($this->translator,
            $storedAttributesOptions, $attributeId, false);
        // attributeDefaultvalues

        $this->view->form                    = $form;
        $this->view->optionForm              = $optionForm;
        $this->view->storedAttributesOptions = $storedAttributesOptions;
        $this->view->attributeId             = $attributeId;

        $state = $this->_getParam('state');

        if ($state) {
            if ($state == Service_Attribute_Option::DELETED)
                $notification['success'] = $this->translator->translate(
                    'attributeOptionDeleteSuccess');

            if ($state == Service_Attribute_Option::CANNOT_DELETE)
                $notification['error'] = $this->translator->translate(
                    'attributeOptionDeleteFailure');

            $this->_helper->FlashMessenger($notification);
        }
        if ($isChange) {
            // attribute value changed
            if ($this->_request->isPost()) {
                $formData = $this->_request->getPost();
                if ($optionForm->isValid($formData)) {
                    $this->logger->log('Form is valid', Zend_Log::DEBUG);

                    $attributeDaoImpl = new Dao_Attribute();
                    foreach ($storedAttributesOptions as $data) {
                        $save = $data[Db_AttributeDefaultValues::ID] . 'save';
                        if ($formData[$save]) {
                            // update adv
                            $attributeDaoImpl->updateDefaultOption(
                                $data[Db_AttributeDefaultValues::ID],
                                $formData[$data[Db_AttributeDefaultValues::ID]],
                                $formData[$data[Db_AttributeDefaultValues::ID] .
                                'ordernumber']);
                        }
                    }
                    $notification = array(
                        'success' => $this->translator->translate(
                            'attributeOptionChangeSuccess'),
                    );
                    $this->_helper->FlashMessenger($notification);
                    $this->_redirect(
                        'attribute/edit/attributeId/' . $attributeId .
                        '/tab/2');
                } else {
                    $optionForm->populate($formData);
                }
            }
        }
        if ($isNew) {
            // new attribute added
            if ($this->_request->isPost()) {
                $formData = $this->_request->getPost();
                if ($form->isValid($formData)) {
                    $this->logger->log('Form is valid', Zend_Log::DEBUG);

                    $attributeService = new Service_Attribute_Option(
                        $this->translator, $this->logger,
                        parent::getUserInformation()->getThemeId());
                    $attributeService->insertNewOption($form->getValues(),
                        $attributeId);
                    $notification = array(
                        'success' => $this->translator->translate(
                            'attributeOptionAddedSuccess'),
                    );
                    $this->_helper->FlashMessenger($notification);
                    $this->_redirect(
                        'attribute/edit/attributeId/' . $attributeId .
                        '/tab/2');
                } else {
                    $form->populate($formData);
                }
            }
        }
    }

    /**
     * adds options to a given attribute (zb: for drop down lists)
     *
     * @return unknown_type
     */
    public function addoptionAction()
    {
        $this->_helper->layout->setLayout('clean', false);
        $option = $this->_getParam('option');
        $order  = $this->_getParam('order');

        $storedAttributesOptions = array(
            array(
                'id'    => null,
                'value' => $option,
                'order' => $order,
            ),
        );

        $optionForm = new Form_Attribute_IndividualOptionsChange(
            $this->translator, $storedAttributesOptions);

        $this->view->optionName = preg_replace('/\W/', '', $option);
        $this->view->optionForm = $optionForm;
    }

    public function activateoptionAction()
    {
        $attributeId = $this->_getParam('attributeId');
        $optionId    = $this->_getParam('optionId');

        if (is_null($attributeId) or is_null($optionId)) {
            throw new Exception_InvalidParameter();
        }

        $notification = array();
        try {
            $attributeService = new Service_Attribute_Option($this->translator,
                $this->logger, parent::getUserInformation()->getThemeId());
            $attributeService->activateOption($optionId);
            $notification['success'] = $this->translator->translate(
                'optionActivateSuccess');
        } catch (Exception_Attribute_ActivationFailed $e) {
            $this->logger->log(
                'User "' . parent::getUserInformation()->getId() .
                '" was unable to activate Option "' . $optionId .
                '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate(
                'optionActivateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect(
            'attribute/edit/attributeId/' . $attributeId . '/tab/2');
    }

    public function editAction()
    {
        $this->logger->log('editAction page invoked', Zend_Log::DEBUG);
        $attributeId     = $this->_getParam('attributeId');
        $this->elementId = $attributeId;

        $tab                    = $this->_getParam('tab');
        $attributeServiceUpdate = new Service_Attribute_Update($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $citypes                = $attributeServiceUpdate->getAllCiTypes();
        $roles                  = $attributeServiceUpdate->getAllRoles();
        $form                   = $attributeServiceUpdate->getUpdateAttributeForm($citypes,
            $roles);

        if (is_null($attributeId)) {
            throw new Exception_InvalidParameter();
        }
        $attributeServiceGet = new Service_Attribute_Get($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $dbData              = $attributeServiceGet->getAttibuteData($attributeId);

        $this->elementId = $dbData[Db_Attribute::NAME];
        $class           = Util_AttributeType_Factory::get($dbData['attributeType']);

        $individualform = $class->getIndividualWizardFormParts(
            $this->translator,
            $options = array(
                'attributeID' => $attributeId,
            ));

        if ($class::ATTRIBUTE_TYPE_ID ==
            Util_AttributeType_Type_CiType::ATTRIBUTE_TYPE_ID ||
            $class::ATTRIBUTE_TYPE_ID ==
            Util_AttributeType_Type_CiTypePersist::ATTRIBUTE_TYPE_ID) {
            $ctId = $dbData['citype']['ciType'];

            if ($this->_request->isPost()) {
                $formData = $this->_request->getPost();

                if ($formData['citype']['ciType']) {
                    $ctId = $formData['citype']['ciType'];
                }
            }

            $individualform->addAttributes($ctId);
        }

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($formData['attributeType'] != $dbData['attributeType']) {
                $class          = Util_AttributeType_Factory::get(
                    $formData['attributeType']);
                $individualform = $class->getIndividualWizardFormParts(
                    $this->translator);
            }

            $mainform = $form->isValid($formData);
            $subform  = (!$individualform || $individualform->isValid($formData));

            if ($subform && $mainform) {
                // $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $check = $attributeServiceUpdate->updateAttribute(
                        $attributeId, $formData, $dbData);
                    if ($check) {
                        $notification['success'] = $this->translator->translate(
                            'attributeUpdateSuccess');
                    }
                } catch (Exception_Attribute_InsertFailed $e) {
                    $this->logger->log(
                        'User "' . parent::getUserInformation()->getId() .
                        '" failed to update Attribute. No items where inserted!',
                        Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate(
                        'attributeUpdateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect(APPLICATION_URL . 'attribute/index');
            } else {

                // workaround, no clue why it doesn't work otherwise,
                // especially because it acutally DOES come from the form...
                $form->populate($formData);
                if ($individualform) {
                    $individualform->populate($formData);
                }
            }
        } else {
            $form->populate($dbData);
            if ($individualform) {
                $individualform->populate($dbData);
            }
        }

        if ($class::ATTRIBUTE_TYPE_ID ==
            Util_AttributeType_Type_Select::ATTRIBUTE_TYPE_ID ||
            $class::ATTRIBUTE_TYPE_ID ==
            Util_AttributeType_Type_Checkbox::ATTRIBUTE_TYPE_ID ||
            $class::ATTRIBUTE_TYPE_ID ==
            Util_AttributeType_Type_Radio::ATTRIBUTE_TYPE_ID) {
            $individualform = null;
        }

        if (!isset($tab) || empty($tab)) {
            $tab = 0;
        }

        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce.js');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce_init_' . $this->translator->getLocale() . '.js');

        $this->view->attributeId             = $attributeId;
        $this->view->roles                   = $roles;
        $this->view->citypes                 = $citypes;
        $this->view->individualform          = $individualform;
        $this->view->form                    = $form;
        $this->view->tab                     = $tab;
        $this->view->filterOptionsPermission = array(
            5 => $this->translator->translate('noFilter'),
            1 => $this->translator->translate('setFilterOptional'),
            2 => $this->translator->translate('setFilterMandatory'),
            4 => $this->translator->translate('setFilterMandatoryOROptional'),
            3 => $this->translator->translate('setFilterX'));
        $this->view->defaultOptionPermission = 4;
    }

    public function detailAction()
    {
        $attributeId = $this->_getParam('attributeId');

        if (is_null($attributeId)) {
            throw new Exception_InvalidParameter();
        }

        $this->elementId = $attributeId;

        $attributeServiceGet = new Service_Attribute_Get($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $attribute           = $attributeServiceGet->getAttibute($attributeId);
        $types               = $attributeServiceGet->getCiTypes($attributeId);
        $roles               = $attributeServiceGet->getRoles($attributeId);

        $this->elementId = $attribute[Db_Attribute::NAME];

        $scriptName = $attributeDto[Db_Attribute::SCRIPT_NAME];

        $this->view->roles       = $roles;
        $this->view->types       = $types;
        $this->view->scriptName  = $scriptName;
        $this->view->attributeId = $attributeId;
        $this->view->attribute   = $attribute;
    }

    public function ordercitypeattributeAction()
    {
        $id = $this->_getParam('attributeId');

        $attributeService = new Service_Attribute_Order($this->translator,
            $this->logger, parent::getUserInformation()->getThemeId());
        $attributeElement = $attributeService->getCiTypeAttributeOrderForm($id);

        if ($this->_request->isPost()) {
            $formData    = $this->_request->getPost();
            $orderstring = $formData['orderstring'];

            $ret = $attributeService->orderCiTypeAttribute($id, $orderstring);

            $notification = array();
            if ($ret) {
                $notification['success'] = $this->translator->translate(
                    'attributeOrderSuccess');
            } else {
                $notification['error'] = $this->translator->translate(
                    'attributeOrderFailed');
            }

            $this->_helper->FlashMessenger($notification);
            $this->_redirect(APPLICATION_URL . 'attribute/index');
        }

        $this->view->attributes  = $attributeElement;
        $this->view->attributeId = $id;
    }

    public function autocompleteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $attributeGroupId = $this->_getParam('attributeGroup');
        $query            = $this->_getParam('name', '');

        $attributeDaoImpl = new Dao_Attribute();
        $results          = $attributeDaoImpl->getActiveAttributesAutoComplete($query,
            $attributeGroupId);

        $data = new Zend_Dojo_Data('id', $results, 'name');

        // Send our output
        $this->_helper->autoCompleteDojo($data);
        echo $data->toJson();
    }

    public function autocompleteactiveAction()
    {
        $term             = $this->_getParam('term', '');
        $type             = (string) $this->_getParam('type', 'name');
        $attributeGroupId = (int) $this->_getParam('attributeGroupId');
        $resultLimit      = 20;
        $attributeDaoImpl = new Dao_Attribute();
        $attributeNames   = array();

        if ($type == 'id') {

            $attribute = $attributeDaoImpl->getAttribute((int) $term);

            $attributeNames[] = array(
                'label' => $attribute['name'],
                'value' => $attribute['name'],
                'id'    => $attribute['id'],
            );

        } else {

            $attributeList = $attributeDaoImpl->getActiveAttributesAutoComplete((string) $term, $attributeGroupId, $resultLimit + 1);

            $i = 0;
            foreach ($attributeList as $attrKey => $attribute) {
                $i++;

                if ($i <= $resultLimit) {
                    $attributeNames[] = array(
                        'label' => $attribute['name'],
                        'value' => $attribute['name'],
                        'id'    => $attribute['id'],
                    );
                } else {
                    $attributeNames[] = array(
                        'label' => '...',
                        'value' => '',
                        'id'    => '',
                    );
                    break;
                }
            }
        }
        $this->_helper->json($attributeNames);
    }

    /**
     * this method creates a form with attributes to select by the given
     * view type id.
     * (used for ci create form)
     */
    public function addattributeformAction()
    {
        $attributeGroupId = (int) $this->_getParam('attributegroupid');
        $ciid             = (int) $this->_getParam('ciid'); // necessary for edit
        $this->sessionID  = $this->_getParam('sessionID');

        //when form is submitted
        if ($this->_request->isPost()) {
            $formData        = $this->_request->getPost();
            $this->sessionID = $formData['sessionID'];

            $attributeDaoImpl = new Dao_Attribute();
            $attributeList    = $attributeDaoImpl->getActiveAttributes($attributeGroupId);

            $autocompleteForm = new Form_Attribute_Add($this->translator,
                $attributeList, $this->sessionID, $attributeGroupId);

            $el = $autocompleteForm->getElement('autoAttribute');
            $el->setRequired(true);

            if ($autocompleteForm->isValid($formData)) {

                $attributeDaoImpl = new Dao_Attribute();

                if (isset($this->sessionID) && $this->sessionID != '') {

                    $attributeDaoImpl->addAttributesToTempTable(
                        $formData['autoAttribute'],
                        $this->sessionID,
                        array(),
                        parent::getUserInformation()->getId(),
                        false);

                    echo "successful";
                    exit();

                } else {

                    throw new Exception_InvalidParameter();
                }
            } else {
                $autocompleteForm->populate($formData);
            }

        } else {
            $this->_helper->layout->setLayout('popup');

            $attributeDaoImpl = new Dao_Attribute();
            $attributeList    = $attributeDaoImpl->getActiveAttributes(
                $attributeGroupId);

            $autocompleteForm = new Form_Attribute_Add($this->translator,
                $attributeList, $this->sessionID, $attributeGroupId);
        }

        $this->view->attributes       = $attributeList;
        $this->view->form             = $autocompleteForm;
        $this->view->ciid             = $ciid;
        $this->view->attributegroupId = $attributeGroupId;
        $this->view->sessionID        = $this->sessionID;

        $this->_helper->layout->disableLayout();
    }
}