<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class AnnouncementController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/announcement_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/announcement_en.csv', 'en');
            parent::addUserTranslation('announcement');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }


    public function itemsperpageAction()
    {
        $itemCountPerPageSession                                   = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['announcement'] = $this->_getParam('rowCount');
        $this->_redirect('announcement/index');
        exit;
    }


    public function indexAction()
    {
        $this->logger->log('Announcement index action has been invoked', Zend_Log::DEBUG);
        $this->setupItemsCountPerPage('announcement');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->announcementPage)) {
            $page = $pageSession->announcementPage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                          = $this->_getParam('page');
                $pageSession->announcementPage = $page;
            }
        } else {
            $page                          = $this->_getParam('page');
            $pageSession->announcementPage = $page;
        }

        $orderBy   = $this->_getParam('orderBy', 'id');
        $direction = $this->_getParam('direction', 'ASC');

        $filter = $this->_getParam('filter');
        // persist filter in url
        if ($this->_hasParam('search')) {
            if (!$this->_getParam('search')) {
                $filterString = '';
            } else {
                $filterString = '/filter/' . urlencode($this->_getParam('search')) . '/';
            }
            $this->_helper->getHelper('Redirector')->gotoUrl(
                APPLICATION_URL .
                'announcement/index/page/' . $page .
                '/orderBy/' . $orderBy .
                '/direction/' . $direction .
                $filterString
            );
        }

        $announcementServiceGet = new Service_Announcement_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $announcementResult     = $announcementServiceGet->getAnnouncementList($page, $orderBy, $direction, $filter);

        $this->view->searchForm = $announcementServiceGet->getFilterForm($filter);
        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $announcementResult;
    }


    public function createAction()
    {
        $userId      = parent::getUserInformation()->getId();
        $cloneFromId = $this->_getParam('cloneFromId');

        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce.js');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce_init_' . $this->translator->getLocale() . '.js');

        $this->logger->log('User "' . parent::getUserInformation()->getId() . '" tries to create Announcement', Zend_Log::DEBUG);

        $serviceAnnouncementCreate = new Service_Announcement_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form                      = $serviceAnnouncementCreate->getCreateAnnouncementForm();
        $form->getElement('show_from_date')->setValue(date('Y-m-d H:i:s')); // populate with current date

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {

                $notification = array();
                try {
                    $announcementId = $serviceAnnouncementCreate->insertAnnouncement($form->getValues(), $userId);
                    $this->logger->log(
                        sprintf('User "%d" created announcement "%d"',
                            parent::getUserInformation()->getId(),
                            $announcementId
                        ),
                        Zend_Log::INFO
                    );
                    $notification['success'] = $this->translator->translate('announcementInsertSuccess');
                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('announcementInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('announcement/index');
                exit;
            } else {
                $form->populate($formData);
            }
        } else if ($cloneFromId != null) {
            $announcementServiceGet = new Service_Announcement_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $announcement           = $announcementServiceGet->getAnnouncement($cloneFromId);

            $dbData['name']           = 'copy_of_' . $announcement[Db_Announcement::NAME];
            $dbData['title_de']       = $announcement[Db_Announcement::TITLE_DE];
            $dbData['message_de']     = $announcement[Db_Announcement::MESSAGE_DE];
            $dbData['title_en']       = $announcement[Db_Announcement::TITLE_EN];
            $dbData['message_en']     = $announcement[Db_Announcement::MESSAGE_EN];
            $dbData['show_from_date'] = $announcement[Db_Announcement::SHOW_FROM_DATE];
            $dbData['show_to_date']   = $announcement[Db_Announcement::SHOW_TO_DATE];
            $dbData['type']           = $announcement[Db_Announcement::TYPE];
            $dbData['valid']          = $announcement[Db_Announcement::IS_ACTIVE];

            $form->populate($dbData);
            $this->logger->log('Cloning AnnouncementId:' . $cloneFromId, Zend_Log::INFO);
        }

        $this->_helper->viewRenderer('create');
        $this->view->headerText = $this->view->translate('announcementCreate');
        $this->view->form       = $form;
    }

    public function editAction()
    {
        $userId = parent::getUserInformation()->getId();

        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce.js');
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tiny_mce/tiny_mce_init_' . $this->translator->getLocale() . '.js');

        $announcementId = $this->_getParam('announcementId');
        $this->logger->log(
            sprintf('User "%d" tries to edit announcement "%d"',
                parent::getUserInformation()->getId(),
                $announcementId
            ),
            Zend_Log::DEBUG
        );

        $serviceAnnouncementUpdate = new Service_Announcement_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $formvalue                 = $serviceAnnouncementUpdate->getAnnouncementForUpdateById($announcementId);
        $form                      = $serviceAnnouncementUpdate->getUpdateAnnouncementForm($announcementId);

        $this->elementId = $formvalue['name'];

        if ($this->_request->isPost()) {
            $formdata = $this->_request->getPost();
            if ($form->isValid($formdata)) {

                $notification = array();
                try {
                    $serviceAnnouncementUpdate->updateAnnouncement($form->getValues(), $userId, $announcementId);
                    $this->logger->log(
                        sprintf('User "%d" edited announcement "%d"',
                            parent::getUserInformation()->getId(),
                            $announcementId
                        ),
                        Zend_Log::DEBUG
                    );
                    $notification['success'] = $this->translator->translate('announcementUpdateSuccess');
                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('announcementUpdateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('announcement/index');
            } else {
                $form->populate($formdata);
            }
        } else {
            //  rename is_active(column name) to valid(form name)
            $formvalue['valid'] = $formvalue['is_active'];
            unset($formvalue['is_active']);

            $form->populate($formvalue);
        }

        $this->_helper->viewRenderer('edit');
        $this->view->headerText     = $this->view->translate('announcementUpdate');
        $this->view->form           = $form;
        $this->view->announcementId = $announcementId;
    }


    public function deleteAction()
    {
        $announcementId = $this->_getParam('announcementId');
        $this->logger->log(
            sprintf('User "%d" tries to delete announcement "%d"',
                parent::getUserInformation()->getId(),
                $announcementId
            ),
            Zend_Log::INFO
        );

        $notification = array();
        try {
            $serviceAnnouncementDelete = new Service_Announcement_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $serviceAnnouncementDelete->deleteAnnouncement($announcementId);

            $this->logger->log(
                sprintf('Template "%d" deleted by user "%d"',
                    $announcementId,
                    parent::getUserInformation()->getId()
                ),
                Zend_Log::INFO
            );
            $notification['success'] = $this->translator->translate('announcementDeleteSuccess');
        } catch (Exception_Announcement_Unknown $e) {
            $this->logger->log(
                sprintf('User "%d" encountered an unknown Exception while deleting an announcement "%d"',
                    parent::getUserInformation()->getId(),
                    $announcementId
                ),
                Zend_Log::ERR
            );
            $notification['error'] = $this->translator->translate('announcementDeleteFailed');
        } catch (Exception_Announcement_DeleteFailed $e) {
            $this->logger->log(
                sprintf('User "%d" failed to delete announcement "%d"',
                    parent::getUserInformation()->getId(),
                    $announcementId
                ),
                Zend_Log::ERR
            );
            $notification['error'] = $this->translator->translate('announcementDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('announcement/index');
    }

    /*
     * Shows announcements to user after login
     * called in AbstractAppAction
     */
    public function displayAction()
    {
        $this->_helper->layout->setLayout('layout', false);

        $userId                 = parent::getUserInformation()->getId();
        $announcementServiceGet = new Service_Announcement_Get($this->translator, $this->logger, 0);
        $userServiceDelete      = new Service_User_Delete($this->translator, $this->logger, 0);


        $activeAnnouncementIds = $announcementServiceGet->getAllActiveAnnouncementIds();

        // user has either accepted or declined announcement
        if ($this->_request->isPost()) {

            $announcementIsAccepted = $this->_getParam('announcementAccepted');
            $announcementId         = $this->_getParam('announcementId');

            $userHasAlreadyAccepted = $announcementServiceGet->userHasAcceptedAnnouncement($userId, $announcementId);
            $announcement           = $announcementServiceGet->getAnnouncement($announcementId);

            // ensure only active announcements can be processed
            $announcementIsActive = false;
            foreach ($activeAnnouncementIds as $activeAnnouncementId) {
                if ($activeAnnouncementId === $announcementId) {
                    $announcementIsActive = true;
                    break;
                }
            }

            if ($announcementIsActive && !$userHasAlreadyAccepted) {
                $announcementServiceGet->userSetAnnouncementAction($announcementIsAccepted, $userId, $announcementId);

                //  user accepts announcement
                if ($announcementIsAccepted == '1') {
                    $message = sprintf('User "%d" has accepted announcement "%d"', $userId, $announcementId);
                    $this->logger->log($message, Zend_Log::INFO);
                } else {    //  user declines announcement

                    //  user declines agreement and account gets deactivated
                    if ($announcement['type'] === 'agreement') {

                        $userServiceDelete->deactivateUser($userId);
                        $message = 'User "%d" has declined announcement agreement "%d" and user has been deactivated';

                    } elseif ($announcement['type'] === 'question') {
                        $message = 'User "%d" has declined announcement question "%d" and will be logged out';
                    } else {
                        $message = 'User %d wants to decline announcement with id %d, but type is not supported';
                    }

                    $this->logger->log(sprintf($message, $userId, $announcementId), Zend_Log::INFO);

                    $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'login/logout/');
                }
            } else {
                $this->logger->log(
                    sprintf('Combination of user "%d" and announcement "%d" was not valid',
                        $userId,
                        $announcementId
                    ),
                    Zend_Log::INFO
                );
                $this->_redirect('announcement/display');
            }
        } // END isPost

        // show announcement - loop through active announcements, checking which one have not yet been accepted
        foreach ($activeAnnouncementIds as $activeAnnouncementId) {
            $userHasAlreadyAccepted = $announcementServiceGet->userHasAcceptedAnnouncement($userId, $activeAnnouncementId);

            if (!$userHasAlreadyAccepted) {
                $this->logger->log(
                    sprintf('Showing user "%d" announcement with id "%d"',
                        $userId,
                        $activeAnnouncementId
                    ),
                    Zend_Log::DEBUG
                );

                $language           = parent::getUserInformation()->getLanguage();
                $announcementResult = $announcementServiceGet->getAnnouncement($activeAnnouncementId, $language);

                return $this->view->result = $announcementResult;
            }
        }

        // all announcements accepted - from now on do not redirect to display action
        $userInformation = parent::getUserInformation();
        $userInformation->setDisplayAnnouncement(false);
        parent::storeUserInformation($userInformation);

        // redirect to original url
        $this->logger->log(
            sprintf('User "%d" has accepted all applying announcements and will be redirected to original url',
                $userId
            ),
            Zend_Log::DEBUG
        );
        $session = Zend_Registry::get('session');
        $this->_redirect($session->redirect);
    }
}