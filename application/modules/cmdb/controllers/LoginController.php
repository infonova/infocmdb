<?php
require_once 'AbstractAppAction.php';
require_once APPLICATION_PATH . '/../library/composer/autoload.php'; // loading library with composer autoloader

/**
 * The LoginController is used to handle all Login/Logout requests.
 *
 *
 *
 */
class LoginController extends Zend_Controller_Action
{

    private static $regNamespace = 'LoginController';
    /** @var Zend_Translate_Adapter $translator */
    private $translator       = null;
    private $languagePath     = null;
    private $userLanguagePath = null;
    /** @var Zend_Log $logger */
    private $logger           = null;

    public function init()
    {
        Zend_Dojo::enableView($this->view);

        parent::init();
        try {
            $this->translator       = Zend_Registry::get('Zend_Translate');
            $this->languagePath     = Zend_Registry::get('Language_Path');
            $this->logger           = Zend_Registry::get('Log');
            $this->userLanguagePath = APPLICATION_PUBLIC . '/translation/';
            $locale                 = $this->translator->getLocale();
            $defaultLanguagePath    = $this->languagePath . '/' . $locale . '/';

            $this->translator->addTranslation($defaultLanguagePath . 'login_' . $locale . '.csv', $locale);
            $this->translator->addTranslation($defaultLanguagePath . 'user_' . $locale . '.csv', $locale);

            //user defined
            $userTranslation = $this->userLanguagePath . '/' . $locale . '/login_' . $locale . '.csv';

            if (is_file($userTranslation)) {
                $this->translator->addTranslation($userTranslation, $locale);
            }

        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }


    /**
     *  redirect to loginAction
     */
    public function indexAction()
    {
        $this->logger->log('UserControll index page invoked', Zend_Log::DEBUG);
        $this->redirect('login/login');
    }


    /**
     * This method handles the basic login functions. It provides the LoginForm and validates the form object.
     * redirects to authenticate after form validation was successfull
     *
     */
    public function loginAction()
    {
        $this->_helper->layout->setLayout('login');
        $this->view->headTitle('LOGIN');
        try {
            $individualizationConfig             = new Zend_Config_Ini(APPLICATION_PATH . '/configs/individualization.ini', APPLICATION_ENV);
            $colorConfig                         = new Zend_Config_Ini(APPLICATION_PATH . '/configs/color.ini', APPLICATION_ENV);
            $this->view->mainColor               = ($individualizationConfig->color->get('main')) ?: $colorConfig->get('main');
            $this->view->linkColor               = ($individualizationConfig->color->get('link')) ?: $colorConfig->get('links');
            $this->view->individualizationConfig = $individualizationConfig;
        } catch (Exception $ex) {
            $this->logger->log("LOGIN: Error reading config file: " . $ex, Zend_Log::ERR);
        }
        if (!isset($this->view->mainColor) || !$this->view->mainColor) {
            $this->view->mainColor = "#C21731";
        }
        if (!isset($this->view->linkColor) || !$this->view->linkColor) {
            $this->view->linkColor = "#8B0000";
        }

        $recoverTitel   = filter_var($this->_getParam('titel'), FILTER_SANITIZE_STRING);
        $recoverMessage = filter_var($this->_getParam('message'), FILTER_SANITIZE_STRING);

        if ($recoverTitel && $recoverMessage) {
            $this->view->exceptionTitle   = $this->translator->_($recoverTitel);
            $this->view->exceptionMessage = $this->translator->_($recoverMessage);
        }

        $this->logger->log('UserControll login page invoked', Zend_Log::DEBUG);
        try {
            $authConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/login.ini', APPLICATION_ENV);
        } catch (Exception $ex) {
            $this->logger->log("LOGIN: Error reading config file: " . $ex, Zend_Log::ERR);
        }
        if (!isset($authConfig) || !$authConfig) {
            $authConfig = null;
        }

        $sess     = Zend_Registry::get('session');
        $redirect = $sess->url;
        if ($redirect == 'login/index' || $redirect == 'login' || $redirect == 'index' || $redirect == 'index/index') {
            $redirect = null;
        }

        $options = $this->getInvokeArg('bootstrap')->getOptions();
        $opt     = array(
            'custom' => array(
                'timeout' => $options['auth']['form']['timeout'],
                'salt'    => $options['auth']['form']['salt'],
            ),
        );

        $form             = new Form_Login_Create($this->translator, $authConfig, $redirect, $opt);
        $this->view->form = $form;

        try {
            $viewConfig                = new Zend_Config_Ini(APPLICATION_PATH . '/configs/view.ini', APPLICATION_ENV);
            $this->view->pwrestebabled = $viewConfig->login->pwreset->enabled;
        } catch (Exception $ex) {
            $this->logger->log("LOGIN: Error reading config file: " . $ex, Zend_Log::ERR);
        }
        if (!isset($this->view->pwrestebabled) || !$this->view->pwrestebabled) {
            $this->view->pwrestebabled = false;
        }

        // AJAX form post
        if ($this->_request->isPost()) {
            // get form data
            $formData = $this->_request->getPost();

            $this->logger->log('Login Form Ajax Post', Zend_Log::DEBUG);

            // try to authenticate the user, returns Array
            $ret = $this->authenticate($formData, $formData['url']);
            // rendering json instead of html view
            // render json of the array, Javascript handles user redirect if login successfull and error messaging if login failed
            $this->_helper->json($ret);
            exit;

        }
        try {
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/individualization.ini', APPLICATION_ENV);
            if (isset($config->element->file->upload->icon) && strlen($config->element->file->upload->icon))
                $this->view->logo = '/_uploads/individualization/' . $config->element->file->upload->icon;
            else
                $this->view->logo = '/images/logo.jpg';

            $this->view->backgroundStyle = '';
            if (isset($config->element->file->upload->background->image) || $config->background->color) {
                $style = '';
                if (isset($config->background->color) && strlen($config->background->color))
                    $style = 'background-color:' . $config->background->color . ';';
                if (isset($config->element->file->upload->background->image) && strlen($config->element->file->upload->background->image)) {
                    $style .= "background-image:url('/_uploads/individualization/" . $config->element->file->upload->background->image . "');";
                    $style .= 'background-repeat:' . (($config->background->image->repeat) ? $config->background->image->repeat : 'no-repeat') . ';';
                }
                $this->view->backgroundStyle = ' style="' . $style . '"';
            }
        } catch (Exception $ex) {
            $this->logger->log("LOGIN: Error reading config file: " . $ex, Zend_Log::ERR);
        }


        $this->view->form = $form;
        $this->render();
    }


    /**
     * Authenticate a User by the given form object. It handles the BD validation and
     * registers the user in the current session if the login was successful.
     *
     * redirect to index page
     *
     */
    private function authenticate($values, $url = null)
    {
        $this->logger->log('authenticateAction invoked', Zend_Log::DEBUG);

        $usernameInput = $values['username'];
        $passwordInput = $values['password'];
        $verifyCode    = $values['verifyCode'];     // TFA code

        $options = $this->getInvokeArg('bootstrap')->getOptions();
        // use zend:auth for identification

        // array with data for json return message
        $returnArray = array(
            'success'  => false,
            'redirect' => 'login/login',
            'messages' => array(),
        );

        // helper library for handeling 2FA
        $tfa = new RobThree\Auth\TwoFactorAuth();

        // retrieve user info
        $userDaoImpl     = new Dao_User();
        $userInformation = $userDaoImpl->getUserByUsername($usernameInput);
        $user_config     = new Util_Config('forms/user.ini', APPLICATION_ENV);

        $password_expiration_enabled = $user_config->getValue('password.maxage.enabled', false);
        $password_expiration_enabled = $password_expiration_enabled === '1' ? true : false;

        $password_expiration_mail_notification = $user_config->getValue('password.maxage.notify.mail', false);
        $password_expiration_mail_notification = $password_expiration_mail_notification === '1' ? true : false;


        if (!$userInformation || $userInformation[Db_User::IS_ACTIVE] != '1') {
            // user is not active / does not exist -> trigger loginFailed and write error message to object and return object
            $msg                     = $this->loginFailed(null, true);
            $returnArray['messages'] = array('userError' => true, 'userErrorMessage' => $msg);
            return $returnArray;
        }

        $authInterface = new Dao_Authentication($this->logger);
        if ($userInformation && $userInformation[Db_User::IS_LDAP_AUTH]) {
            $this->logger->log('triggered LDAP auth with user: "' . $usernameInput . '"', Zend_Log::INFO);
            $authAdapter = $authInterface->authLdap($usernameInput, $passwordInput);
        } else {
            $this->logger->log('triggered DB auth with user: "' . $usernameInput . '"', Zend_Log::INFO);
            $authAdapter = $authInterface->authDatabase($userInformation, $passwordInput);
        }

        //Check userdata
        try {
            $auth       = Zend_Auth::getInstance();

            // validate credentials without persisting
            $authResult = $authAdapter->authenticate();
            $messages   = $authResult->getMessages();

            if ($messages[0])
                $this->logger->log($messages[0], Zend_Log::INFO);
            if (isset($messages[1]))
                $this->logger->log($messages[1], Zend_Log::INFO);
            if (isset($messages[2]))
                $this->logger->log($messages[2], Zend_Log::DEBUG);

            //Zend_Session::regenerateId();
            //Basic_Report::getInstance()->addM(Zend::dump($token, null, false));
            if ($authResult->isValid() === true) {
                // user is two factor auth
                if ($userInformation[Db_User::IS_TWO_FACTOR_AUTH] == 1) {
                    // no code given: prompt user to enter code by returning message with TFAenabled = true, and message for prompt (TFAMessage)
                    if ($verifyCode === ' ' || $verifyCode === '') {
                        $this->logger->log("User has TFA enabled; VerifyCode required User " . $usernameInput, Zend_Log::INFO);
                        $returnArray['messages'] = array('TFAenabled' => true, 'TFAMessage' => $this->translator->_('2FAenterCode'));
                        return $returnArray;
                    }
                    // code is incorrect: prompt user to enter code by returning message with TFAenabled = true, and message for prompt (TFAMessage)
                    if (!$tfa->verifyCode($userInformation[Db_User::SECRET], $verifyCode)) {
                        $this->logger->log("User entered incorrect TFA verifyCode User " . $usernameInput, Zend_Log::WARN);
                        $returnArray['messages'] = array('TFAenabled' => true, 'TFAMessage' => $this->translator->_('2FAErrorCodeInvalid'));
                        return $returnArray;
                    } else {
                        $this->logger->log('user authenticated with 2 factor User ' . $usernameInput, Zend_Log::INFO);
                    }
                }

                if ($password_expiration_enabled &&
                    !$userInformation[Db_User::IS_LDAP_AUTH] &&
                    !$userInformation[Db_User::PASSWORD_EXPIRE_OFF]) {

                    if ($userDaoImpl->isPasswordExpired($userInformation[Db_User::ID], $userInformation)) {
                        $msg                     = $this->passwordExpired($userInformation[Db_User::USERNAME]);
                        $returnArray['messages'] = array('userError' => true, "userErrorMessage" => $msg, "hideForm" => true);
                        return $returnArray;
                    }

                    $days_until_expiration = 0;
                    if ($userDaoImpl->isPasswordAboutToExpire($userInformation[Db_User::ID], $days_until_expiration, $userInformation)) {

                        $notification          = array();
                        $link                  = APPLICATION_URL . "user/usersettings";
                        $notification['error'] = sprintf($this->translator->_('passwordAboutToExpire'), $days_until_expiration, $link);
                        $this->_helper->FlashMessenger($notification);

                        if ($password_expiration_mail_notification) {

                            $current_user_settings = $userInformation[Db_User::SETTINGS];
                            $current_user_settings = json_decode($current_user_settings, true);

                            if (
                                isset($current_user_settings) &&
                                (
                                    !isset($current_user_settings["password_maxage_mail_sent"]) ||
                                    (
                                        isset($current_user_settings["password_maxage_mail_sent"]) &&
                                        $current_user_settings['password_maxage_mail_sent'] !== true
                                    )
                                )
                            ) {
                                $user_settings                              = array();
                                $user_settings['password_maxage_mail_sent'] = true;
                                $userDaoImpl->editUserSettings($userInformation[Db_User::ID], $user_settings);
                                $template = "passwordExpiredNotification.phtml";
                                if (!$userInformation[Db_User::EMAIL]) {
                                    $template = "passwordExpiredNotificationNoMail.phtml";
                                }
                                try {
                                    $this->handleSendPasswordExpireNotificationMail($userInformation, $days_until_expiration,
                                        $template);
                                } catch (Exception $e) {
                                    $this->logger->log($e->getMessage(), Zend_Log::ERR);
                                }
                            }
                        }
                    }
                }

                // set Zend_Auth Identity in session
                $auth->authenticate($authAdapter);
                $this->logger->log('user ' . $usernameInput . ' logged in', Zend_Log::DEBUG);

                $session             = Zend_Registry::get('session');
                $session->username   = $authResult->getIdentity();
                $session->lastAction = time();
                $session->freshLogin = true;
                $ipAddress           = $_SERVER['REMOTE_ADDR'];
                $session->ipAddress  = $ipAddress;
                $authInterface->setLastLogin($session->username);

                // retrieve user information
                $userDto = new Dto_UserDto();
                $userDto->setId($userInformation[Db_User::ID]);
                $userDto->setUsername($userInformation[Db_User::USERNAME]);
                $userDto->setPassword($userInformation[Db_User::PASSWORD]);
                $userDto->setFirstname($userInformation[Db_User::FIRSTNAME]);
                $userDto->setLastname($userInformation[Db_User::LASTNAME]);
                $userDto->setRoot($userInformation[Db_User::IS_ROOT]);
                $userDto->setValid($userInformation[Db_User::IS_ACTIVE]);
                $userDto->setDescription($userInformation[Db_User::DESCRIPTION]);
                $userDto->setNote($userInformation[Db_User::NOTE]);
                $userDto->setThemeId($userInformation[Db_User::THEME_ID]);
                $userDto->setCiDelete($userInformation[Db_User::IS_CI_DELETE_ENABLED]);
                $userDto->setRelationEdit($userInformation[Db_User::IS_RELATION_EDIT_ENABLED]);
                $userDto->setLdapAuth($userInformation[Db_User::IS_LDAP_AUTH]);
                $userDto->setLanguage($userInformation[Db_User::LANGUAGE]);
                $userDto->setLayout($userInformation[Db_User::LAYOUT]);
                $userDto->setLastAction(time());
                $userDto->setIpAddress($ipAddress);
                $userDto->setTwoFactorAuth($userInformation[Db_User::IS_TWO_FACTOR_AUTH]);
                /**
                 * Show unaccepted announcements
                 *
                 * @see AbstractAppAction::init()
                 */
                $userDto->setDisplayAnnouncement(true);


                $this->logger->log('Parsed all information', Zend_Log::DEBUG);

                // Register authentication in AuthController
                AbstractAppAction::storeUserInformation($userDto);
                AbstractAppAction::historizeUserLogin();

                // user successfully authenticated
                $returnArray['success']  = true;
                $returnArray['messages'] = null;

                // if url param is passed, return that to the user with the return object (javascript handles redirect)
                if (isset($url) && !empty($url)) {
                    $returnArray['redirect'] = $url;
                    return $returnArray;
                }

                $themeDaoImpl = new Dao_Theme();
                $theme        = $themeDaoImpl->getThemeStartPage($userDto->getThemeId());

                if ($theme && $theme[Db_Menu::FUNCTION_]) {
                    // redirect to user defined start page
                    $returnArray['redirect'] = $theme[Db_Menu::FUNCTION_];
                } else {
                    $returnArray['redirect'] = 'index/index';
                }
                // return the return object
                return $returnArray;
            } else {
                $this->logger->log('NOT LOGGED IN', Zend_Log::DEBUG);
                $msg = $this->loginFailed($authResult);
                // error authenticating user -> write message to return object and return the object
                $returnArray['messages'] = array('userError' => true, 'userErrorMessage' => $msg);
                return $returnArray;
            }
        } catch (Zend_Auth_Adapter_Exception $e) {
            $this->logger->log('Invalid Login for username "' . $usernameInput . '" with error messsage ' . $e, Zend_Log::INFO);
            $returnArray['messages'] = array('userError' => true, 'userErrorMessage' => $this->translator->_('exceptionDefault'));
            return $returnArray;
            //throw new Exception_Auth_AuthFailed($e);

        } catch (Exception $e) {
            $this->logger->log('Unexpected exception in login process for username "' . $usernameInput . '" with password "' . $passwordInput . '" and error message ' . $e, Zend_Log::WARN);
            $returnArray['messages'] = array('userError' => true, 'userErrorMessage' => $this->translator->_('exceptionDefault'));
            return $returnArray;
            //throw new Exception_Auth_Unknown($e);
        }
    }


    /**
     * generate the exception message on base of the auth result code.
     *
     * @param unknown_type $token
     *
     * @return string
     */
    private function loginFailed($token, $userInactive = false)
    {
        $session   = new Zend_Session_Namespace($this->regNamespace);
        $message   = ""; // error message returned
        $errorCode = null;
        if ($userInactive) {
            $errorCode = -99;
        } else {
            $errorCode = $token->getCode();
        }

        switch ($errorCode) {
            case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
            case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
            case -99:
                /** Ungültiger login -> generische Fehlermeldung "Benutzername / Passwort ist nicht gütlig" **/
                $this->logger->log('FAILURE_CREDENTIAL_INVALID', Zend_Log::DEBUG);
                $message = $this->translator->_('username') . " / " . $this->translator->_('password') . " " . $this->translator->_('exceptionInvalid');
                break;
            default:
                /** Was wegen anderen Fehlern machen **/
                $this->logger->log('default EXCEPTION', Zend_Log::DEBUG);
                $message = $this->translator->_('exceptionDefault');
                break;
        }

        $session->loginFailed = true;
        return $message;
        //$this->_redirect('index');
    }

    /**
     * handle password expiration
     *
     * @param Zend_Auth_Result $token
     *
     * @return string
     */
    private function passwordExpired($userName)
    {
        $session = new Zend_Session_Namespace($this->regNamespace);

        $this->logger->log('FAILURE_PASSWORD_EXPIRED for user ' . $userName, Zend_Log::DEBUG);
        $link = APPLICATION_URL . "login/recover";

        $message = sprintf($this->translator->_('passwordExpired'), $link);

        $session->loginFailed = true;
        return $message;
        //$this->_redirect('index');
    }


    /**
     * creating reset password request, calling handleSendRecoverMail() for mail transport
     * <ul>peforms various checks to see if client is eligible for passwort reset
     *  <li>check if username is valid and active</li>
     *  <li>check if username does not have an open / recent password reset request</li>
     *  <li>check if client has made too many reset requests</li>
     *  <li>check how many requests have been made within last hour ( < maximumRequestsHour) </li>
     * </ul>
     *
     */
    public function recoverAction()
    {
        $this->_helper->layout->setLayout('login');
        $this->view->headTitle('LOGIN - Reset Password');

        try {
            $individualizationConfig             = new Zend_Config_Ini(APPLICATION_PATH . '/configs/individualization.ini', APPLICATION_ENV);
            $colorConfig                         = new Zend_Config_Ini(APPLICATION_PATH . '/configs/color.ini', APPLICATION_ENV);
            $this->view->mainColor               = ($individualizationConfig->color->get('main')) ?: $colorConfig->get('main');
            $this->view->linkColor               = ($individualizationConfig->color->get('link')) ?: $colorConfig->get('links');
            $this->view->individualizationConfig = $individualizationConfig;

            $cmdb = $individualizationConfig->homelink->string->partA . $individualizationConfig->homelink->string->partB;
        } catch (Exception $ex) {
            $this->logger->log("LOGIN: Error reading config file: " . $ex, Zend_Log::ERR);
        }
        if (!isset($this->view->mainColor) || !$this->view->mainColor) {
            $this->view->mainColor = "#C21731";
        }
        if (!isset($this->view->linkColor) || !$this->view->linkColor) {
            $this->view->linkColor = "#8B0000";
        }

        if (!isset($cmdb) || !$cmdb) {
            $cmdb = 'infoCMDB';
        }

        try {
            $configLogin          = new Zend_Config_Ini(APPLICATION_PATH . '/configs/login.ini', APPLICATION_ENV);
            $maximumRequestsHour  = $configLogin->login->resetPassword->maxRequestsPerHour;
            $maxRequestsPerClient = $configLogin->login->resetPassword->maxRequestsPerClient;
            $maxRequestsPerUser   = $configLogin->login->resetPassword->maxRequestsPerUser;
        } catch (Exception $ex) {
            $this->logger->log("PASSWORD_RESET: Error reading config file: " . $ex, Zend_Log::ERR);
        }
        if (!isset($maximumRequestsHour) || !$maximumRequestsHour) {
            $maximumRequestsHour = 100;
        }

        if (!isset($maxRequestsPerClient) || !$maxRequestsPerClient) {
            $maxRequestsPerClient = 10;
        }

        if (!isset($maxRequestsPerUser) || !$maxRequestsPerUser) {
            $maxRequestsPerUser = 5;
        }

        $titel            = "loginPasswordRecoveryErrorTitel";
        $authDaoImpl      = new Dao_Authentication();
        $requestsLastHour = $authDaoImpl->getCountRequestsWithinHour();
        $clientKey        = $this->generateClientKey();
        $requestsByClient = $authDaoImpl->getActiveRequestCountByClientKey($clientKey);
        $clientId         = "UA: " . $_SERVER['HTTP_USER_AGENT'] . ", IP: " . $_SERVER['REMOTE_ADDR'];

        // enforcing global maximum of requests per hour set in login.ini
        if ($requestsLastHour && ($requestsLastHour['cnt'] >= $maximumRequestsHour)) {
            $this->logger->log("PASSWORD_RESET: too many requests within last hour", Zend_Log::WARN);

            $message = "loginPasswordRecoveryGlobalTimeout";
            $this->_redirect(APPLICATION_URL . 'login/login/titel/' . $titel . '/message/' . $message . '/');
        } else if ($requestsByClient["cnt"] >= $maxRequestsPerClient) {
            // number of requests coming from this client          
            $this->logger->log("PASSWORD_RESET: too many requests from client: " . $clientId . " ClientKey: " . $clientKey, Zend_Log::WARN);

            $message = "loginPasswordRecoveryClientTimeout";
            $this->_redirect(APPLICATION_URL . 'login/login/titel/' . $titel . '/message/' . $message . '/');
        }

        $form = new Zend_Form();
        $form->addDecorators(array(
            array('HtmlTag', array('tag' => 'table')),
        ));

        $username = new Zend_Form_Element_Text('username');
        $username->setAttrib('tabindex', '1');
        $username->setAttrib('placeholder', "Username");
        $username->setAttrib('class', 'authInput');
        $username->removeDecorator('Label');
        $username->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td')),
        ));
        //$username->addValidator(new Zend_Validate_Regex('/^[a-z0-9\.,\-_@]+$/i'));

        $submit = new Zend_Form_Element_Image('submit');
        $submit->setAttrib('onclick', 'form.submit();');
        $submit->removeDecorator('Label');
        $submit->setImage(APPLICATION_URL . 'images/arrow_right_k.png');
        $submit->setDecorators(array(
            'ViewHelper',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'rowspan' => '2')),
        ));

        $form->addElements(array($username, $submit));
        $this->view->form = $form;


        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {

                $this->logger->log("PASSWORD_RESET: requesting recovery link for user:  " . $formData['username'] . " Client: " . $clientKey, Zend_Log::INFO);

                $return      = null;
                $userDaoImpl = new Dao_User();
                $user        = $userDaoImpl->getUserByUsername($formData['username']);
                $mailMessage = null;

                // invalid username -> pretend that everything is fine (so that malicious user cannot brute force usernames 
                // log invalid request to LOG 
                if (!$user) {
                    $this->logger->log("PASSWORD_RESET: Invalid Username " . $formData['username'] . "\nClientId: " . $clientId, Zend_Log::ERR);
                    $return = true;
                } else {
                    $userId = $user[Db_User::ID];

                    $countRecentUserRequests = $authDaoImpl->getCountRecentPasswordResetsByUserId($userId);

                    if ($countRecentUserRequests && $countRecentUserRequests['cnt'] >= $maxRequestsPerUser) {
                        // there are too many open requests for this user
                        $return = "loginPasswordRecoveryRequestTimeout";
                        $this->logger->log("PASSWORD_RESET: user has requested too many changes in last hour: " . $user[Db_User::USERNAME] . " Client: " . $clientId, Zend_Log::WARN);

                    } else if (!$user[Db_User::EMAIL]) {
                        // user has no email address
                        $mailMessage = 'noMailAddress';
                        $this->logger->log("PASSWORD_RESET: user has no email address: " . $user[Db_User::USERNAME] . " \ntrying to notify admin...", Zend_Log::WARN);

                    } else if ($user[Db_User::IS_LDAP_AUTH] == 1) {
                        // user is authenticated via ldap -> password recovery not possible here
                        $mailMessage = "LdapUser";
                        $this->logger->log("PASSWORD_RESET: user is LDAP: " . $user[Db_User::USERNAME], Zend_Log::WARN);

                    } else if ($user[Db_User::IS_ACTIVE] == 0) {
                        // user is not active -> no password recovery, notify admin
                        $mailMessage = "userInactive";
                        $this->logger->log("PASSWORD_RESET: user is not set active: " . $user[Db_User::USERNAME], Zend_Log::WARN);

                    }
                }
                // there is a problem with the given user, but we don't want to display the information right in the application
                // -> potential hacker might try to gain information (eg which users exist / are inactive / are LDAP Auth)
                // to solve this: send mail with info to user / admin
                if (isset($mailMessage)) {
                    $mailTemplate = null;
                    switch ($mailMessage) {
                        case 'LdapUser':
                            $mailTemplate = 'pwrecoveryLdap.phtml';
                            break;
                        case 'userInactive':
                            $mailTemplate = 'pwrecoveryInactive.phtml';
                            break;
                        case 'noMailAddress':
                            $mailTemplate = 'pwrecoveryNoMail.phtml';
                            break;
                    }
                    $return = $this->handleSendRecoverMail($user, null, $mailTemplate);
                }
                // no error before -> create hash token and send mail
                if (!isset($return)) {
                    $this->logger->log("PASSWORD_RESET: creating token and sending to user " . $user[Db_User::USERNAME], Zend_Log::INFO);
                    $timeout = $configLogin->login->resetPassword->timeout;

                    if (!isset($timeout) || !$timeout) {
                        $timeout = 1;
                    }
                    $crypt = new Util_Crypt();
                    // random hash value used as reset token
                    $hash = $crypt->create_uniqid();
                    // get current time and add $timeout-Hours to time
                    $then = mktime(date("H") + $timeout, date("i"), date("s"), date("m"), date("d"), date("Y"));

                    $passwordRecover                               = array();
                    $passwordRecover[Db_PasswordReset::USER_ID]    = $userId;
                    $passwordRecover[Db_PasswordReset::HASH]       = $hash;
                    $passwordRecover[Db_PasswordReset::VALID_TO]   = date('Y-m-d H:i:s', $then);
                    $passwordRecover[Db_PasswordReset::IS_VALID]   = true;
                    $passwordRecover[Db_PasswordReset::CLIENT_KEY] = $clientKey;

                    // inserting new reset Request to db
                    $authDaoImpl->insertPasswordReset($passwordRecover);

                    // sending mail to user containing link with token (hash)
                    $return = $this->handleSendRecoverMail($user, $hash, 'pwrecovery.phtml');
                }


                $titel   = "";
                $message = "";
                if ($return === true) {
                    // everything OK
                    $titel   = 'loginPasswordRecoverySuccessTitel';
                    $message = 'loginPasswordRecoverySuccessMessage';
                } else {
                    // ERROR!!!
                    $titel   = 'loginPasswordRecoveryErrorTitel';
                    $message = $return;
                    $this->logger->log("PASSWORD_RESET: error occurred for user: " . $user[Db_User::USERNAME] . " message: " . $message, Zend_Log::WARN);
                }

                $this->_redirect(APPLICATION_URL . 'login/login/titel/' . $titel . '/message/' . $message . '/');
            } else {
                $form->populate($formData);
            }
        }

        try {
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/individualization.ini', APPLICATION_ENV);
        } catch (Exception $ex) {
            $this->logger->log("PASSWORD_RESET: Error reading config file: " . $ex, Zend_Log::ERR);
        }

        if (isset($config->element->file->upload->icon) && strlen($config->element->file->upload->icon)) {
            $this->view->backgroundImage = '/_uploads/individualization/' . $config->element->file->upload->icon;
        } else {
            $this->view->backgroundImage = '/images/logo.jpg';
        }

    }


    /**
     * sending password reset link to user
     *
     * @param Dao_User $user
     * @param string   $hash
     *
     * @return boolean|string
     */
    private function handleSendRecoverMail($user, $hash, $mailTemplate)
    {
        if (!isset($mailTemplate)) {
            return 'loginPasswordRecoveryUnexpectedException';
        }

        try {
            $returnMessage = null;

            try {
                $configLogin = new Zend_Config_Ini(APPLICATION_PATH . '/configs/login.ini', APPLICATION_ENV);
                $timeout     = $configLogin->login->resetPassword->timeout;

                $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/individualization.ini', APPLICATION_ENV);
                $cmdb   = $config->homelink->string->partA . $config->homelink->string->partB;

                $configApp = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
                $adminMail = $configApp->administrator->mail;
            } catch (Exception $ex) {
                $this->logger->log("PASSWORD_RESET: Error reading config file: " . $ex, Zend_Log::ERR);
            }

            $reciever = array();

            if (!isset($timeout) || !$timeout) {
                $timeout = 1;
            }
            $timeouttext = $timeout . " Stunde(n)";

            if (!isset($cmdb) || !$cmdb) {
                $cmdb = 'infoCMDB';
            }

            // user has no email -> notify admin
            if ($mailTemplate === 'pwrecoveryNoMail.phtml') {
                $recipient = $adminMail;
            } else {
                // user has email
                $recipient = $user[Db_User::EMAIL];
            }


            array_push($reciever, array('type' => 'mail', 'address' => $recipient));

            $gatewayConfig = new Util_Config('notification/mail.ini', APPLICATION_ENV);
            $gatewayClass  = $gatewayConfig->getValue('mail.sender.class', 'Notification_Gateway_Mail');

            $subject = "Password Recovery";

            $message = new Notification_Message_Default();
            $message->setSubject($subject);
            $message->addGateway($gatewayClass);
            $message->setGatewayConfig($gatewayClass, $gatewayConfig);
            $message->setReciever($reciever);
            $message->setTemplate($mailTemplate);

            $message->addBodyParam('hash', $hash);
            $message->addBodyParam('username', $user[Db_User::USERNAME]);
            $message->addBodyParam('cmdb', $cmdb);
            $message->addBodyParam('timeouttext', $timeouttext);
            $message->addBodyParam('host', APPLICATION_URL);

            if (!$message->send()) {
                // something went wrong when trying to sent the mail
                return 'loginPasswordRecoveryUnexpectedException';
            }

        } catch (Exception $e) {
            $returnMessage = 'loginPasswordRecoveryConnectionException';
            $this->logger->log("PASSWORD_RESET: ERROR SENDING MAIL! " . $e, Zend_Log::WARN);
        }


        if ($returnMessage) {
            return $returnMessage;
        } else {
            $this->logger->log("PASSWORD_RESET: successfully sent mail to: " . $recipient . "\nUsername: " . $user[Db_User::USERNAME], Zend_Log::INFO);
            return true;
        }
    }

    /**
     * Sends a mail notifying the user about his expiring password. Throws an exception on error, which should
     * be caught to prevent stopping the program flow.
     *
     * @param $user         array containing user data
     * @param $daysLeft     int days left until password expires
     * @param $mailTemplate string mail template to use
     *
     * @return bool true on success, throws exception on failure
     * @throws Exception throws an exception which is supposed to be put into the log
     */
    private function handleSendPasswordExpireNotificationMail($user, $daysLeft, $mailTemplate)
    {
        if (!isset($mailTemplate)) {
            throw new Exception("PASSWORD_EXPIRED_NOTIFICATION: no mail template defined");
        }
        try {
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/individualization.ini', APPLICATION_ENV);
            $cmdb   = $config->homelink->string->partA . $config->homelink->string->partB;

            $configApp = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
            $adminMail = $configApp->administrator->mail;
        } catch (Exception $ex) {
            throw new Exception("PASSWORD_EXPIRED_NOTIFICATION: Error reading config file: " . $ex);
        }
        try {
            $receiver = array();

            if (!isset($cmdb) || !$cmdb) {
                $cmdb = 'infoCMDB';
            }

            // user has no email -> notify admin
            if ($mailTemplate === 'passwordExpiredNotificationNoMail.phtml') {
                $recipient = $adminMail;
            } else {
                // user has email
                $recipient = $user[Db_User::EMAIL];
            }


            array_push($receiver, array('type' => 'mail', 'address' => $recipient));

            $gatewayConfig = new Util_Config('notification/mail.ini', APPLICATION_ENV);
            $gatewayClass  = $gatewayConfig->getValue('mail.sender.class', 'Notification_Gateway_Mail');

            $subject = "Password Expiration Notification";

            $message = new Notification_Message_Default();
            $message->setSubject($subject);
            $message->addGateway($gatewayClass);
            $message->setGatewayConfig($gatewayClass, $gatewayConfig);
            $message->setReciever($receiver);
            $message->setTemplate($mailTemplate);

            $message->addBodyParam('username', $user[Db_User::USERNAME]);
            $message->addBodyParam('cmdb', $cmdb);
            $message->addBodyParam('host', APPLICATION_URL);
            $message->addBodyParam('daysleft', $daysLeft);
            $message->addBodyParam('link', APPLICATION_URL . "user/usersettings");

            if (!$message->send()) {
                // something went wrong when trying to send the mail
                throw new Exception("PASSWORD_EXPIRED_NOTIFICATION: error on send mail");
            }

        } catch (Exception $e) {
            $this->logger->log("PASSWORD_EXPIRED_NOTIFICATION: failed to notify user about his expiring password", Zend_Log::WARN);
            throw $e;
        }
        $this->logger->log("PASSWORD_EXPIRED_NOTIFICATION: successfully sent mail to: " . $recipient . "\nUsername: " . $user[Db_User::USERNAME], Zend_Log::INFO);
        return true;
    }


    /** <b> handeling requests with password change token </b>
     *  <ul>returns error message if:
     *      <li>user tries to access url without hash parameter</li>
     *      <li>token timestamp is invalid</li>
     *      <li>token has already been used to change password</li>
     *      <li>ClientKey does not match clientKey in token database entry</li>
     *      <li>password reset form values are incorrect (username does not match, new passwords do not match)
     *  </ul>
     *  <ul>actions:
     *      <li>if password reset is successfull: change password, set token invalid, sent the user a success mail</li>
     *  </ul>
     */
    public function changeAction()
    {
        $this->_helper->layout->setLayout('login');
        $this->view->headTitle('LOGIN - Set Password');
        $clientId = "UA: " . $_SERVER['HTTP_USER_AGENT'] . ", IP: " . $_SERVER['REMOTE_ADDR'];

        $hash = filter_var($this->_getParam('hash'), FILTER_SANITIZE_STRING);

        // Coda Slider tries to request "./img/ajax-loader.gif" which results in APPLICATION_URL/login/change/hash/img/
        // -> to catch this request check if $hash == "img", if so: return false => stop request
        if (isset($hash) && $hash === "img") {
            return false;
        }

        $this->logger->log("PASSWORD_RESET: invoked login/change action with hash: " . $hash . " (" . $clientId . ")", Zend_Log::INFO);

        $authDaoImpl             = new Dao_Authentication();
        $userDaoImpl             = new Dao_User();
        $individualizationConfig = new Util_Config('individualization.ini', APPLICATION_ENV);
        $colorConfig             = new Util_Config('color.ini', APPLICATION_ENV);

        $clientKey = $this->generateClientKey(); // generate clientKey
        $cmdbName  = $individualizationConfig->getValue('homelink.string.partA', 'info') . $individualizationConfig->getValue('homelink.string.partB', 'CMDB');
        $mainColor = $individualizationConfig->getValue('color.main', $colorConfig->getValue('main', '#C21731'));
        $linkColor = $individualizationConfig->getValue('color.link', $colorConfig->getValue('links', '#8B0000'));

        $returnMessage = "";
        $returnTitle   = "loginPasswordRecoveryErrorTitel";


        // no hash given -> return with Error message
        if (!isset($hash) || !$hash || empty($hash)) {
            $returnMessage = "loginPasswordRecoveryHashInvalid";
            $this->logger->log("PASSWORD_RESET failed: empty hash (" . $clientId . ")", Zend_Log::CRIT);
            $this->_redirect(APPLICATION_URL . 'login/login/titel/' . $returnTitle . '/message/' . $returnMessage . '/');
        }

        $pwRecovery = $authDaoImpl->getActivePasswordResetByHash($hash);

        // various checks to see, if we even have to load the page
        // -> check if hash is valid
        // -> check if clientKey matches the clientKey written to the database
        try {
            // check if there is a change request and if the token has been used (is_valid = 0) -> return error message
            if (!$pwRecovery || (!$pwRecovery[Db_PasswordReset::USER_ID]) || ($pwRecovery[Db_PasswordReset::IS_VALID] != 1)) {
                $returnMessage = 'loginPasswordRecoveryHashInvalid';
                $this->logger->log("PASSWORD_RESET failed: invalid hash: " . $hash . " (" . $clientId . ")", Zend_Log::CRIT);

            } else if ($pwRecovery[Db_PasswordReset::CLIENT_KEY] != $clientKey) {
                // if the clientKey in the database does not match the current client -> error
                $returnMessage = 'loginPasswordRecoveryAccessDenied';
                $this->logger->log("PASSWORD_RESET: tried to access reset token that is locked to a different client. (" . $clientId . ", Hash: " . $hash . ")", Zend_Log::CRIT);
            }

        } catch (Exception $e) {
            $returnMessage = 'loginPasswordRecoveryUnexpectedException';
            $this->logger->log("PASSWORD_RESET: unexpected error!  " . json_encode($e), Zend_Log::CRIT);
        }

        // if there is a error message -> return to login page
        if ($returnMessage !== "") {
            $this->_redirect(APPLICATION_URL . 'login/login/titel/' . $returnTitle . '/message/' . $returnMessage . '/');
        }


        $user = $userDaoImpl->getUser($pwRecovery[Db_PasswordReset::USER_ID]); // get user based off of user_id from hash token

        $rowspan = 4; // helper variable for view: number of rows the submit button should span across

        // if user exists and has two factor auth enabled -> set $tfaEnabled true
        $tfaEnabled = false;
        if ($user && $user[Db_User::IS_TWO_FACTOR_AUTH]) {
            $tfaEnabled = true;
            $rowspan    = 6;
        }

        // creating the form for resetting the password
        $form = new Form_Login_PasswordRecovery($this->translator, null, $tfaEnabled);


        $notification = array();
        if ($this->_request->isGet()) {
            $notification['message'] = $this->translator->_('resetPasswordHelpText');
            $notification['error']   = false;
        }
        // handle FORM submit
        if ($this->_request->isPost()) {

            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                try {

                    // if user[username] is what user entered in form
                    if ($user[Db_User::USERNAME] === $formData['username']) {
                        // if passwords in form match
                        if ($formData['password'] === $formData['password_confirm']) {

                            // user has TFA enabled -> require valid code
                            if ($tfaEnabled) {
                                $tfa = new RobThree\Auth\TwoFactorAuth();

                                if (!$tfa->verifyCode($user[Db_User::SECRET], $formData['verify_code'])) {
                                    $this->logger->log("PASSWORD_RESET: TFA code is incorrect: " . $formData['username'] . " (" . $clientId . ")" . $clientKey, Zend_Log::INFO);
                                    // TFA code is incorrect -> set error message and stop execution of code (=> return;)
                                    $notification['message']  = $this->translator->translate('loginPasswordRecoveryResetFailed');
                                    $notification['error']    = true;
                                    $this->view->notification = $notification;
                                    $this->view->form         = $form;
                                    return;
                                } else {
                                    $this->logger->log("PASSWORD_RESET: TFA code correct user " . $formData['username'], Zend_Log::INFO);
                                }
                            }

                            // handle password change
                            $user[Db_User::PASSWORD]         = $formData['password'];
                            $now                             = new DateTime();
                            $user[Db_User::PASSWORD_CHANGED] = $now->format("Y-m-d H:i:s");

                            $settings = $user[Db_User::SETTINGS];

                            $new_settings                              = array();
                            $new_settings['password_maxage_mail_sent'] = false;

                            $settings                = Dao_User::editSettingString($settings, $new_settings);
                            $user[Db_User::SETTINGS] = $settings;

                            $userDaoImpl->updateUser($user, $user[Db_User::ID]);

                            // set hash invalid
                            $authDaoImpl->setPassswordResetInvalid($pwRecovery[Db_PasswordReset::ID]);

                            // sent success mail to user
                            $subject  = "Password successfully changed";
                            $reciever = array();
                            array_push($reciever, array('type' => 'mail', 'address' => $user[Db_User::EMAIL]));

                            $gatewayConfig = new Util_Config('notification/mail.ini', APPLICATION_ENV);
                            $gatewayClass  = $gatewayConfig->getValue('mail.sender.class', 'Notification_Gateway_Mail');

                            $message = new Notification_Message_Default();
                            $message->setSubject($subject);
                            $message->addGateway($gatewayClass);
                            $message->setGatewayConfig($gatewayClass, $gatewayConfig);
                            $message->setReciever($reciever);
                            $message->setTemplate('pwrecoverysuccess.phtml');

                            $message->addBodyParam('cmdb', $cmdbName);
                            $message->addBodyParam('host', APPLICATION_URL);
                            $message->send();

                            $this->logger->log("PASSWORD_RESET: password successfully changed: " . $formData['username'] . " (" . $clientId . ")" . $clientKey, Zend_Log::INFO);

                            // set success messages
                            $returnTitle   = "loginPasswordRecoverySuccessRecoveredTitel";
                            $returnMessage = "loginPasswordRecoverySuccessRecoveredMessage";
                        } else {
                            // passwords don't match
                            $notification['message'] = $this->translator->translate('loginPasswordRecoveryResetFailed');
                            $notification['error']   = true;
                            $this->logger->log("PASSWORD_RESET: new passwords do not match for user: " . $formData['username'] . " (" . $clientId . ")", Zend_Log::WARN);
                        }
                    } else {
                        // username is incorrect
                        $notification['message'] = $this->translator->translate('loginPasswordRecoveryResetFailed');
                        $notification['error']   = true;
                        $this->logger->log("PASSWORD_RESET: tried changing password with incorrect username: " . $formData['username'] . " (" . $clientId . ")", Zend_Log::WARN);
                    }
                } catch (Exception $e) {
                    $this->logger->log("PASSWORD_RESET: unexpected error!  " . json_encode($e), Zend_Log::CRIT);
                    $returnMessage = 'loginPasswordRecoveryUnexpectedException';
                }
            } else {
                $this->logger->log("PASSWORD_RESET: form is not valid: " . $formData['username'] . " (" . $clientId . ")", Zend_Log::INFO);
                // move all error message left to input fields
                $validationErrors       = $form->getMessages();
                $validationErrorsToShow = array();
                foreach ($validationErrors as $fieldName => $fieldErrors) {
                    foreach ($fieldErrors as $errorCode => $errorMessage) {
                        $validationErrorsToShow[] = $errorMessage;
                    }
                    $form->getElement($fieldName)->removeDecorator('Errors');
                }
                $notification['message']  = '<ul class="errors"><li>' . implode('</li><li>', $validationErrorsToShow) . '</li></ul>';
                $notification['error']    = false;
                $this->view->notification = $notification;
            }

        }

        // if there is a return message (change successfull, Exception -> error) -> return to login page with the message
        if ($returnMessage !== "") {
            $this->_redirect(APPLICATION_URL . 'login/login/titel/' . $returnTitle . '/message/' . $returnMessage . '/');
        }

        $this->view->mainColor               = $mainColor;
        $this->view->linkColor               = $linkColor;
        $this->view->individualizationConfig = $individualizationConfig;
        $this->view->notification            = $notification;
        $this->view->form                    = $form;
        $this->view->rowspan                 = $rowspan;     // helper variable, used for submit img td rowspan
        $this->view->actionUrl               = APPLICATION_URL . "/login/change/hash/" . $hash;    // URL for form action
    }


    /**
     * destroy the current Session
     *
     */
    public function logoutAction()
    {
        $allDevices = (bool) $this->getParam("allDevices", "1");

        AbstractAppAction::logout($allDevices);
        $this->redirect('login/login');
    }


    /**
     * used as a security messure to prevent multiple clients from accessing the same reset token
     * generating key based off of different ways of getting a clients IP Address
     *
     * @return string sha1 hash
     */
    public function generateClientKey()
    {
        $keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        $hash = "";
        foreach ($keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $hash .= $_SERVER[$key];
            }
        }

        return sha1($hash);
    }
}