<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class SearchlistController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/searchlist_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/searchlist_en.csv', 'en');
            parent::addUserTranslation('searchlist');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }


    /**
     * shows the parent searh lists
     */
    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);

        $serviceGet = new Service_Searchlist_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ret        = $serviceGet->getSearchList();

        $ciTypes     = $ret['ciTypes'];
        $form        = $ret['form'];
        $searchLists = $ret['searchLists'];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();

            if ($form->isValid($formData)) {
                $notification = array();

                try {
                    $serviceUpdate = new Service_Searchlist_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
                    $serviceUpdate->updateSearchListStatus($formData, $searchLists);
                    $notification['success'] = $this->translator->translate('searchListUpdateStatusSuccess');
                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('searchListUpdateStatusFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('searchlist/index/');
            } else {
                $form->populate($formData);
            }
        }

        $this->view->ciTypes = $ciTypes;
        $this->view->form    = $form;
    }


    public function detailAction()
    {
        $ciTypeId = $this->_getParam('ciTypeId');

        $serviceUpdate = new Service_Searchlist_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ret           = $serviceUpdate->getDetailForm($ciTypeId);

        $form        = $ret['form'];
        $maxElements = $ret['maxElements'];
        $dbData      = $ret['dbData'];

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $notification = array();

                try {
                    $serviceUpdate->updateSearchListAttributes($ciTypeId, $formData, $maxElements);
                    $notification['success'] = $this->translator->translate('searchListUpdateAttributesSuccess');
                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('searchListUpdateAttributesFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('searchlist/index/');
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($dbData);
        }


        $this->view->ciTypeId    = $ciTypeId;
        $this->view->form        = $form;
        $this->view->maxElements = $maxElements;
    }

}