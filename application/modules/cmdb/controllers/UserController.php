<?php
require_once 'AbstractAppAction.php';
require_once APPLICATION_PATH . '/../library/composer/autoload.php'; // loading library with composer autoloader

/**
 *
 *
 *
 */
class UserController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/user_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/user_en.csv', 'en');
            parent::addUserTranslation('user');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    /**
     *  The index action is used to display all configured user entries
     *  by using the pagination feature
     */
    public function itemsperpageAction()
    {
        $itemCountPerPageSession                           = new Zend_Session_Namespace('itemCountPerPage');
        $itemCountPerPageSession->itemCountPerPage['user'] = $this->_getParam('rowCount');
        $this->_redirect('user/index');
        exit;
    }

    public function indexAction()
    {
        $this->logger->log('User index action has been invoked', Zend_Log::DEBUG);

        $this->setupItemsCountPerPage('user');

        //Save Page in Session
        $pageSession = new Zend_Session_Namespace('page');

        if (isset($pageSession->userPage)) {
            $page = $pageSession->userPage;
            if ($this->_getParam('page') != $page && $this->_getParam('page') != null) {
                $page                  = $this->_getParam('page');
                $pageSession->userPage = $page;
            }
        } else {
            $page                  = $this->_getParam('page');
            $pageSession->userPage = $page;
        }

        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $filter = null;
        if ($this->_hasParam('search')) {
            if (!$this->_getParam('search')) {
                $filterString = '';
            } else {
                $filterString = '/filter/' . $this->_getParam('search') . '/';
            }
            $this->_helper->getHelper('Redirector')->gotoUrl(APPLICATION_URL . 'user/index/page/' . $page . '/orderBy/' . $orderBy . '/direction/' . $direction . $filterString);
        } elseif ($this->_getParam('filter')) {
            $filter = str_replace('*', '%', $this->_getParam('filter'));

            if (!$filter || $filter == 'Filter' || $filter == '%') {
                $filter = null;
            }
        }

        $filter = str_replace('"', '', $filter);
        $filter = str_replace("'", '', $filter);


        $userServiceGet = new Service_User_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $userResult     = $userServiceGet->getUserList($page, $orderBy, $direction, $filter);

        $this->view->page       = $page;
        $this->view->orderBy    = $orderBy;
        $this->view->direction  = $direction;
        $this->view->filter     = $filter;
        $this->view->paginator  = $userResult['paginator'];
        $this->view->searchForm = $userResult['searchForm']->setAction($this->view->url(array('filter' => null, 'page' => null)));
    }

// 	public function hashallplainpasswordsAction() {
// 			// Only Admins can call this function
// 		if (parent::getUserInformation()->getRoot() == '1' && $config->auth->password->encryption == 1)
// 		{
// 			$this->_helper->viewRenderer->setNoRender();
// 			$this->_helper->layout->disableLayout();
// 			$this->_helper->layout->setLayout('print', false);
// 			$get_var = (int) $this->_getParam('hash_all_plain_passwords'); // validate the url variable for extra security
// 			if ($get_var === 312)
// 			{
// 				$crypt = new Util_Crypt();
// 				$crypt->hash_all_plaintext_passwords();
// 			}
// 			exit();
// 		}
// 		else
// 		{
// 			die('Not authorized');
// 		}
// 	}

    public function createAction()
    {
        $this->logger->log('createAction page invoked', Zend_Log::DEBUG);
        $userServiceCreate = new Service_User_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $projects          = $userServiceCreate->getProjects();
        $roles             = $userServiceCreate->getRoles();
        $form              = $userServiceCreate->getCreateUserForm($projects, $roles);
        $cloneFromId       = $this->_getParam('cloneFromId');

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $userId                  = $userServiceCreate->createUser($formData, parent::getUserInformation()->getId());
                    $notification['success'] = $this->translator->translate('userCreateSuccess');

                } catch (Exception_User_InsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create User. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('userInsertFailed');
                } catch (Exception_User_MappingInsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to create User-Mapping. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('userMappingInsertFailed');
                } catch (Exception_User_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while creating new User', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('userInsertFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('user/index/');
            } else {
                $form->populate($formData);
            }
        } else if ($cloneFromId != null) {
            $userServiceGet = new Service_User_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $dbData         = $userServiceGet->getUserData($cloneFromId);
            $dbData['name'] = 'copy_of_' . $dbData['name'];
            $form->populate($dbData);
            $this->logger->log('Cloning UserId:' . $cloneFromId, Zend_Log::INFO);
        }

        $this->view->projects = $projects;
        $this->view->roles    = $roles;
        $this->view->form     = $form;
    }

    public function editAction()
    {
        $this->logger->log('editAction page invoked', Zend_Log::DEBUG);
        $userId            = $this->_getParam('userId');
        $this->elementId   = $userId;
        $userServiceUpdate = new Service_User_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $projects          = $userServiceUpdate->getProjects();
        $roles             = $userServiceUpdate->getRoles();
        $form              = $userServiceUpdate->getUpdateUserForm($projects, $roles);

        $userServiceGet  = new Service_User_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $dbData          = $userServiceGet->getUserData($userId);
        $this->elementId = $dbData['name'];

        // variable for view, check if user has TFA enabled
        $twoFactorAuth = false;
        if ($dbData[Db_User::IS_TWO_FACTOR_AUTH] == 1) {
            $twoFactorAuth = true;
        }
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);

                $notification = array();
                try {
                    $userServiceUpdate->updateUser($userId, $formData, $dbData, parent::getUserInformation()->getId());
                    //update session-info if this is the currently logged on user
                    $sessionUser = parent::getUserInformation();
                    if ($userId == $sessionUser->getId()) {
                        $newDbData = $userServiceGet->getUser($userId);
                        $sessionUser->setUsername($newDbData[Db_User::USERNAME]);
                        $sessionUser->setPassword($newDbData[Db_User::PASSWORD]);
                        $sessionUser->setFirstname($newDbData[Db_User::FIRSTNAME]);
                        $sessionUser->setLastname($newDbData[Db_User::LASTNAME]);
                        $sessionUser->setRoot($newDbData[Db_User::IS_ROOT]);
                        $sessionUser->setValid($newDbData[Db_User::IS_ACTIVE]);
                        $sessionUser->setDescription($newDbData[Db_User::DESCRIPTION]);
                        $sessionUser->setNote($newDbData[Db_User::NOTE]);
                        $sessionUser->setThemeId($newDbData[Db_User::THEME_ID]);
                        $sessionUser->setCiDelete($newDbData[Db_User::IS_CI_DELETE_ENABLED]);
                        $sessionUser->setRelationEdit($newDbData[Db_User::IS_RELATION_EDIT_ENABLED]);
                        $sessionUser->setLdapAuth($newDbData[Db_User::IS_LDAP_AUTH]);
                        $sessionUser->setLanguage($newDbData[Db_User::LANGUAGE]);
                        $sessionUser->setLayout($newDbData[Db_User::LAYOUT]);

                        AbstractAppAction::storeUserInformation($sessionUser);
                    }
                    $notification['success'] = $this->translator->translate('userUpdateSuccess');
                } catch (Exception_User_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update User. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('userUpdateFailed');
                } catch (Exception_User_MappingInsertFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update User-Mapping. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('userMappingUpdateFailed');
                } catch (Exception_User_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating User', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('userUpdateFailed');
                }

                parent::clearNavigationCache();
                parent::clearProjectCache();

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('user/index/');
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($dbData);
        }

        $this->view->projects      = $projects;
        $this->view->roles         = $roles;
        $this->view->form          = $form;
        $this->view->twoFactorAuth = $twoFactorAuth;
        $this->view->userId        = $userId;
    }

    public function detailAction()
    {
        $this->logger->log('detailAction page invoked', Zend_Log::DEBUG);
        $userId = $this->_getParam('userId');

        $userServiceGet = new Service_User_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $user           = $userServiceGet->getUser($userId);
        $roles          = $userServiceGet->getRoles($userId);
        $projects       = $userServiceGet->getProjects($userId);

        $this->elementId = $user[Db_User::USERNAME];

        $this->view->projects = $projects;
        $this->view->roles    = $roles;
        $this->view->user     = $user;
    }


    public function deleteAction()
    {
        $userId            = $this->_getParam('userId');
        $userServiceCreate = new Service_User_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        try {
            $statusCode = $userServiceCreate->deleteUser($userId);
            if ($statusCode) {
                switch ($statusCode) {
                    case 1:
                        $notification['success'] = $this->translator->translate('userDeleteSuccess');
                        break;
                    case 2:
                        $notification['success'] = $this->translator->translate('userDeactivationSuccess');
                        break;
                    default:
                        $notification['error'] = $this->translator->translate('userDeleteFailed');
                        break;
                }
            } else {
                $notification['error'] = $this->translator->translate('userDeleteFailed');
            }
        } catch (Exception_User_DeleteFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to delete User. No items where inserted!', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('userDeleteFailed');
        } catch (Exception_User_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while deleting User', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('userDeleteFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect(APPLICATION_URL . 'user/index');
    }

    public function activateAction()
    {
        $userId = $this->_getParam('userId');

        $notification = array();
        try {
            $userServiceCreate       = new Service_User_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $statusCode              = $userServiceCreate->activateUser($userId);
            $notification['success'] = $this->translator->translate('userActivationSuccess');
        } catch (Exception_User_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while activating User', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('userActivatingFailed');
        } catch (Exception_User_ActivateFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to activate User!', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('userActivatingFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect(APPLICATION_URL . 'user/index');
    }

    /**
     * reload user properties from database and store it in session.
     * used for language changes
     */
    public function refreshAction()
    {
        $language = $this->_getParam('language');

        $userDaoImpl     = new Dao_User();
        $userInformation = $userDaoImpl->getSingleUser(parent::getUserInformation()->getId());

        $userDto = new Dto_UserDto();
        $userDto->setId($userInformation->{Db_User::ID});
        $userDto->setUsername($userInformation->{Db_User::USERNAME});
        $userDto->setPassword($userInformation->{Db_User::PASSWORD});
        $userDto->setFirstname($userInformation->{Db_User::FIRSTNAME});
        $userDto->setLastname($userInformation->{Db_User::LASTNAME});
        $userDto->setRoot($userInformation->{Db_User::IS_ROOT});
        $userDto->setValid($userInformation->{Db_User::IS_ACTIVE});
        $userDto->setDescription($userInformation->{Db_User::DESCRIPTION});
        $userDto->setNote($userInformation->{Db_User::NOTE});
        $userDto->setThemeId($userInformation->{Db_User::THEME_ID});
        $userDto->setCiDelete($userInformation->{Db_User::IS_CI_DELETE_ENABLED});
        $userDto->setRelationEdit($userInformation->{Db_User::IS_RELATION_EDIT_ENABLED});
        $userDto->setLdapAuth($userInformation->{Db_User::IS_LDAP_AUTH});
        $userDto->setTwoFactorAuth($userInformation->{Db_User::IS_TWO_FACTOR_AUTH});

        if ($language) {
            $userDto->setLanguage($language);
        } else {
            $userDto->setLanguage($userInformation->{Db_User::LANGUAGE});
        }

        $userData                    = array();
        $userData[Db_User::LANGUAGE] = $language;

        $userDto->setLayout($userInformation->{Db_User::LAYOUT});
        $userDto->setLastAction(time());
        $userDto->setIpAddress(parent::getUserInformation()->getIpAddress());

        $userDaoImpl = new Dao_User();
        $userDaoImpl->updateUser($userData, $userDto->getId());
        parent::storeUserInformation($userDto);

        parent::clearNavigationCache();
        parent::clearProjectCache();
        $this->_redirect(APPLICATION_URL . 'index/index');
    }

    /**
     * change settings of logged in user
     * option for activating 2FA (=TFA = Two Factor Authentication)
     */
    public function usersettingsAction()
    {
        $userServiceGet    = new Service_User_Get($this->translator, $this->logger, 0);
        $userServiceUpdate = new Service_User_Update($this->translator, $this->logger, 0);
        $userId            = parent::getUserInformation()->getId();
        $dbData            = $userServiceGet->getUserData($userId);

        try {
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);
        } catch (Exception $ex) {
            $this->_helper->FlashMessenger("ERROR");
            $this->logger->log("usersettings error loading config file! " . $ex, Zend_Log::ERR);
            exit;
        }


        $form = new Form_User_OwnUserUpdate($this->translator, $config);
        #var_dump($form); die;
        // get session handler
        $session = Zend_Registry::get('session');
        try {
            // getting name of cmdb instance (for 2FA Label to be displayed in Google Authenticator App)
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/individualization.ini', APPLICATION_ENV);
            $cmdb   = $config->homelink->string->partA . $config->homelink->string->partB;
        } catch (Exception $ex) {
            $this->logger->log("usersettings error loading config file! " . $ex, Zend_Log::WARN);
        }

        if (!isset($cmdb) || !$cmdb) {
            $cmdb = 'infoCMDB';
        }

        // helper library for handeling 2FA
        $tfa = new RobThree\Auth\TwoFactorAuth($cmdb);

        // the user does not have TFA enabled
        if ($dbData['is_two_factor_auth'] == 0) {
            // generate secret and store to session (secret in session is only used if user chooses to activate 2FA)
            //$dbData['secret'] =
            $session->secret = $tfa->createSecret();
        } else {
            // user is_two_factor_auth is enabled -> set secret to empty string in session, user does not need it
            $session->secret = '';
        }

        // generating label for Google Authenticator, using <username>@<cmdbName>
        $label = $dbData['name'] . "@" . $cmdb;


        $qrText = $tfa->getQRText($label, $session->secret);
        // TFA Data needed in view
        // secret is displayed in case user does not want to use QR code but rather type the secret himself
        // QRText is used for JS-Library QRCode -> generating QR Code
        // is_two_factor_auth for chosing what is displayed in tab 2FA (instructions for activating or "already-enabled" message)
        $tfaData = array(
            'secret'             => $session->secret,
            'QRText'             => $qrText,
            "is_two_factor_auth" => $dbData['is_two_factor_auth'],
        );

        $formDb = array(
            "email"     => $dbData["email"],
            "password"  => $dbData["password"],
            "firstname" => $dbData["firstname"],
            "lastname"  => $dbData["lastname"],
            "language"  => $dbData["language"],
        );

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {

                $notification = array();
                try {
                    $userServiceUpdate->updateUser($userId, $formData, $dbData, $userId, true);
                    $notification['success'] = $this->translator->translate('userUpdateSuccess');
                } catch (Exception_User_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update User. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('userUpdateFailed');
                } catch (Exception_User_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating User', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('userUpdateFailed');
                }

                $this->_helper->FlashMessenger($notification);
                $this->_redirect('user/refresh/language/' . $formData["language"]);
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($formDb);
        }

        $this->view->tfaData = $tfaData;
        $this->view->form    = $form;
        $this->view->userId  = $userId;
    }

    /**
     * change password of a user
     */
    public function changePasswordAction()
    {
        $userServiceGet    = new Service_User_Get($this->translator, $this->logger, 0);
        $userServiceUpdate = new Service_User_Update($this->translator, $this->logger, 0);
        $userId            = $this->_getParam('userId');

        $editAllUsersAllowed = false;
        $menuDao             = new Dao_Menu();
        $menuList            = $menuDao->getActiveMenusByThemeId(parent::getUserInformation()->getThemeId());

        foreach ($menuList as $menu) {
            if ($menu[Db_Menu::NAME] === 'user') {
                $editAllUsersAllowed = true;
                break;
            }
        }

        if ($userId === parent::getUserInformation()->getId() || $editAllUsersAllowed) {
            $changePasswordAllowed = true;
        } else {
            $changePasswordAllowed = false;
        }

        if (!$changePasswordAllowed) {
            throw new Exception_AccessDenied();
        }

        try {
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);
        } catch (Exception $ex) {
            $this->_helper->FlashMessenger("ERROR");
            $this->logger->log("changePassword error loading config file! " . $ex, Zend_Log::ERR);
            exit;
        }


        $form = new Form_User_ChangePassword($this->translator, $config);
        #var_dump($form); die;
        // get session handler
        $session = Zend_Registry::get('session');

        $dbData = $userServiceGet->getUserData($userId);
        $formDb = array(
            "name"      => $dbData["name"],
            "password"  => $dbData["password"],
            "firstname" => $dbData["firstname"],
            "lastname"  => $dbData["lastname"],
        );

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {

                $notification = array();
                try {
                    $userServiceUpdate->updateUser($userId, $formData, $dbData, $userId, true);
                    $notification['success'] = $this->translator->translate('passwordChangeSuccess');
                } catch (Exception_User_UpdateFailed $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update user password. No items where inserted!', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('passwordChangeFailed');
                } catch (Exception_User_Unknown $e) {
                    $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating user password', Zend_Log::ERR);
                    $notification['error'] = $this->translator->translate('passwordChangeFailed');
                }

                $this->_helper->FlashMessenger($notification);
            } else {
                $form->populate($formData);
            }
        } else {
            $form->populate($formDb);
        }

        $this->view->form   = $form;
        $this->view->userDb = $dbData;
        $this->view->userId = $userId;
        if ($editAllUsersAllowed) {
            $this->view->cancelUrl = APPLICATION_URL . '/user/edit/userId/' . $userId;
        } else {
            $this->view->cancelUrl = APPLICATION_URL . '/user/usersettings/';
        }
    }

    /** AJAX function!
     * called in user/usersettings
     * if code is correct TFA (= 2FA = Two Factor Authentication) is activated for this user
     *
     * @return json {error: null|string, success: bool}
     */
    public function enableTfaAction()
    {
        $message = array('error' => null);

        // getting user data
        $userServiceGet    = new Service_User_Get($this->translator, $this->logger, 0);
        $userServiceUpdate = new Service_User_Update($this->translator, $this->logger, 0);
        $userId            = parent::getUserInformation()->getId();
        $user              = $userServiceGet->getUserData($userId);

        // copy needed for updating user
        $dbData = $user;

        $this->logger->log("2FA trying to activate 2FA for user: " . $userId, Zend_Log::NOTICE);

        // secret generated when calling usersettingsAction is stored in session->secret
        $session = Zend_Registry::get('session');
        // verifyCode user entered
        $verifyCode = $this->_request->getParam('TFAVerifyCode');

        // helper library for handeling 2FA
        $tfa = new RobThree\Auth\TwoFactorAuth();

        // user does not exist
        if (!$user[Db_User::NAME]) {
            $message['error'] = '2FAErrorNoUser';
        } else if ($user[Db_User::IS_TWO_FACTOR_AUTH] == 1) {
            // user already has the is_two_factor_auth flag
            $message['error'] = '2FAErrorAlreadyActive';
        } else if (!isset($session->secret)) {
            // the secret neccessary for 2FA is not set
            $message['error'] = '2FAErrorNoSecret';
        } else {
            // everything is fine -> check if verifyCode is valid
            $valid = $tfa->verifyCode($session->secret, $verifyCode);
            if ($valid) {
                // user entered valid code => write secret do user entry in DB and set is_two_factor_auth to true
                try {
                    // setting new values for user
                    $user[Db_User::SECRET]             = $session->secret;
                    $user[Db_User::IS_TWO_FACTOR_AUTH] = 1;

                    // updating user with new data
                    $userServiceUpdate->updateUser($userId, $user, $dbData, $userId, true);
                    // removing the secret from the session, it is only needed in the database when user tries to log in
                    $session->secret = null;
                    $this->logger->log("2FA activated for user " . $userId, Zend_Log::NOTICE);
                } catch (Exception $ex) {
                    // catching errors and logging
                    $message['error'] = 'UnexpectedError';
                    $this->logger->log("2FA ERROR activating 2FA for user UserId: " . $userId . " error " . $ex, Zend_Log::ERR);
                }
            } else {
                // user entered invalid code
                $message['error'] = '2FAErrorCodeInvalid';
            }
        }

        if (!isset($message['error'])) {
            // no error occurred -> message[error] is still null
            $message['success'] = true;
        } else {
            // error occurred. logging error message and returning translation of error to user
            $this->logger->log("2FA ERROR for UserId: " . $userId . " Error: " . $message['error'], Zend_Log::ERR);
            // translating error message (user_[locale].csv)
            $message['error']   = $this->translator->translate($message['error']);
            $message['success'] = false;

        }

        // rendering json instead of html view
        $this->_helper->json($message);
        exit;
    }

    /** AJAX function!
     * called in user/edit/userId/[id]
     * ADMIN!
     * setting is_two_factor_auth = 0 and secret = null
     *
     * @return json {error: null|string, success: bool)}
     */
    public function disableTfaAction()
    {
        $message       = array('error' => null);
        $userId        = $this->getParam('userId');
        $currentUserId = parent::getUserInformation()->getId();

        $this->logger->log("2FAADMINDISABLE trying to disable for UserId: " . $userId . " AdminUserId: " . $currentUserId, Zend_Log::NOTICE);

        if (!empty($userId)) {
            // getting user data
            try {
                // getting user data
                $userServiceGet    = new Service_User_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
                $userServiceUpdate = new Service_User_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
                $dbData            = $userServiceGet->getUserData($userId);

                // making copy of Database data, setting new values
                $userUpdate                              = $dbData;
                $userUpdate[Db_User::IS_TWO_FACTOR_AUTH] = 0;
                $userUpdate[Db_User::SECRET]             = false;

                // updating user data
                $userServiceUpdate->updateUser($userId, $userUpdate, $dbData, $currentUserId, true);
                $this->logger->log("2FAADMINDISABLE for UserId: " . $userId . " AdminUserId: " . $currentUserId, Zend_Log::NOTICE);
                $message['success'] = true;

            } catch (Exception $ex) {
                $this->logger->log("2FAADMINDISABLE ERROR for UserId: " . $userId . " Error: " . $ex, Zend_Log::ERR);
                $message['error'] = 'UnexpectedError';
            }

        } else {
            $message['error'] = "2FAErrorNoUser";
        }


        if (!isset($message['error'])) {
            // no error occurred -> message[error] is still null
            $message['success'] = true;
        } else {
            // error occurred. logging error message and returning translation of error to user
            $this->logger->log("2FAADMINDISABLE ERROR for UserId: " . $userId . " Error: " . $message['error'], Zend_Log::ERR);
            // translating error message (user_[locale].csv)
            $message['error']   = $this->translator->translate($message['error']);
            $message['success'] = false;

        }
        // rendering json instead of html view
        $this->_helper->json($message);
        exit;
    }

    /**
     * Updates the user setting json string using a json string from the ajax request.
     *
     * Supposed to be called via ajax.
     * The data object of the ajax request should look like this:
     * var data = {
     *   "settings": {
     *      "pinned_projects": [12, 13, 18],
     *      "user_language": "DE",
     *      "setting_you_want_to_edit": "value of the setting",
     *      "setting_with_array": ["value1", "value2"]
     *   }
     * };
     * Of course it should only contain the settings you want to manipulate.
     * Settings which are not inside the settings object are left as they were.
     * If a user does not have a setting yet it is added to his json string.
     *
     * @param settings string
     */
    public function updateusersettingsAction()
    {
        $settings = $this->_getParam("settings");
        // for some reason getParam already parses the json string into an array
//        $settings = json_decode($settings, true);
        $userId                = parent::getUserInformation()->getId();
        $userServiceUpdate     = new Service_User_Update($this->translator, $this->logger, 0);
        $current_settings_json = $userServiceUpdate->setUserSettings($userId, $settings);
        $this->flagPinnedProjects();
        echo $current_settings_json;
        exit;
    }
}

