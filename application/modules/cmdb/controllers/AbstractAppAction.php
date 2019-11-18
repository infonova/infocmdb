<?php

/**
 *
 *
 *
 */
class AbstractAppAction extends Zend_Controller_Action
{


    /**
     * @var Zend_Log
     */
    protected $logger           = null;
    private   $writer           = null;
    protected $translator       = null;
    protected $languagePath     = null;
    private   $userLanguagePath = null;

    protected        $_naviBar                 = null;
    protected        $_projectBar              = null;
    protected        $_individualizationConfig = null;
    protected        $_cache                   = null;
    protected static $cacheID                  = null;

    protected static $_userInformation = null;

    protected $controller;
    protected $action;
    protected $elementId;
    protected $headTitleParts = array();


    public function init()
    {
        parent::init();

        $controller = $this->getRequest()->getControllerName();
        $action     = $this->getRequest()->getActionName();

        $this->controller = $controller;
        $this->action     = $action;

        if (is_null($this->logger)) {
            $this->logger = Zend_Registry::get('Log');
        }

        $viewConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/view.ini', APPLICATION_ENV);
        Zend_Registry::set('viewConfig', $viewConfig);

        $config  = new Zend_Config_Ini(APPLICATION_PATH . '/configs/navigation.ini', APPLICATION_ENV);
        $session = Zend_Registry::get('session');

        $lockingConfig = new Util_Config('locking.ini', APPLICATION_ENV);
        Zend_Registry::set('lockingConfig', $lockingConfig);


        // fresh login --> delete file cache
        if ($session->freshLogin === true) {
            $this->prepareCache($config);
            $this->clearTranslationCache();
            $this->clearNavigationCache();
            $this->clearProjectCache();
            $this->clearIndividualizationCache();
            $session->freshLogin = false;
        }

        $this->translator       = Zend_Registry::get('Zend_Translate');
        $this->languagePath     = Zend_Registry::get('Language_Path');
        $this->userLanguagePath = APPLICATION_PUBLIC . '/translation/';
        $locale                 = new Zend_Locale();
        $this->view->localeLang = $locale->getLanguage();
        self::setTranslatorLocal();
        self::historizeUserRequest();

        $userInformation = self::getUserInformation();
        $layout          = $userInformation->getLayout();

        if (!$layout) {
            $layout = "default";
        }

        //admin mode handling
        $adminMode = $session->adminMode;
        if ($adminMode === true) {
            Zend_Registry::set('adminMode', true);
        } else {
            $adminMode = false;
            Zend_Registry::set('adminMode', false);
        }

        $this->view->adminMode = $adminMode;

        // check admin status
        $isRoot             = $userInformation->getRoot();
        $this->view->isRoot = $isRoot;

        $individualizationConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/individualization.ini', APPLICATION_ENV);
        $colorConfig             = new Zend_Config_Ini(APPLICATION_PATH . '/configs/color.ini', APPLICATION_ENV);
        $this->view->mainColor   = ($individualizationConfig->color->get('main')) ?: $colorConfig->get('main');
        $this->view->linkColor   = ($individualizationConfig->color->get('link')) ?: $colorConfig->get('links');

        $this->_helper->layout->setLayout($layout);

        $isConsole = $this->_getParam('isConsole');
        $options   = $this->getInvokeArg('bootstrap')->getOptions();
        if ((!isset($options['isConsole']) || !$options['isConsole']) && !$isConsole && !$this->getRequest()->isXmlHttpRequest()) {

            $maintenanceModeEnabled = file_exists(APPLICATION_PATH . '/../cmdb.lock');
            if ($maintenanceModeEnabled === true) {
                if ($action != 'maintenance') {
                    if ($session->disableMaintenanceForUser !== true) {
                        $this->_redirect('index/maintenance');
                    } else {
                        $this->_helper->FlashMessenger(array(
                            'error' => $this->translator->translate('maintenance_mode') . '!',
                        ));
                    }
                }
            } else {
                /**
                 * Shows announcements to user after login
                 *
                 * @see LoginController::authenticate()
                 */
                $displayAnnouncement = $userInformation->getDisplayAnnouncement();
                if ($displayAnnouncement && !($action == 'display' && $controller == 'announcement')) {
                    //  saves the original url for redirecting user after he accepted announcements
                    $url               = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
                    $session->redirect = $url;

                    $this->logger->log('Redirect user to read possible unaccepted announcements', Zend_Log::DEBUG);
                    $this->redirect('announcement/display/');
                }
            }

            $this->prepareCache($config);
            $this->enableNavigationCache($config);
            $this->enableProjectCache();
            $this->enableIndividualizationCache();
            $this->enablePinnedProjectsCache();

            // private messages
            $messageDaoImpl   = new Dao_Message();
            $countNewMessages = $messageDaoImpl->getCountNewMessages($userInformation->getId());
            $themeDescr       = $messageDaoImpl->getThemeDescriptionById($userInformation->getThemeId());
            unset($messageDaoImpl);

            $this->view->newMessages = $countNewMessages['cnt'];

            $this->view->navigation              = $this->_naviBar;
            $this->view->projects                = $this->_projectBar;
            $this->view->currentProjectId        = self::getCurrentProjectId();
            $this->view->individualizationConfig = $this->_individualizationConfig;

            $this->view->userName = $userInformation->getUsername();
            $this->view->roleName = $themeDescr[Db_Theme::DESCRIPTION];
            $this->view->language = $userInformation->getLanguage();

            $this->view->searchTerm = $this->translator->translate('searchTerm');


            if ($session->updateUserHistory) {
                self::historizeUserLogin();
                $session->updateUserHistory = false;
                unset($session->updateUserHistory);
            }

            $typeId = $this->_getParam('typeid');

            $this->view->projectTypeId    = $typeId;
            $this->view->headerSearchForm = $this->getSearchElementForm();
            $this->view->cmdbVersion      = $this->getCmdbVersion();
            $this->view->pinnedProjects   = $this->getPinnedProjects();

            $response = $this->getResponse();
            $response->insert('menu', $this->view->render('navigation/navi.phtml'));
            $response->insert('project', $this->view->render('navigation/project.phtml'));
            $response->insert('headline', $this->view->render('navigation/headline.phtml'));
        }
    }

    protected function getDefaultHeadTitleParts()
    {
        // ID prefix -->  <ID> |
        $firstTitlePartPrefix = '';
        if (!empty($this->elementId)) {
            $firstTitlePartPrefix = $this->elementId . ' | ';
        }

        // translation for controller and action
        $translationControllerKey     = 'pageTitleController' . ucfirst($this->controller);
        $translationControllerMessage = $this->translator->translate($translationControllerKey);
        $translationActionKey         = 'pageTitleAction' . ucfirst($this->action);
        $translationActionMessage     = $this->translator->translate($translationActionKey);

        // fallback if no controller translation -> controller name
        if ($translationControllerKey === $translationControllerMessage) {
            $translationControllerMessage = $this->controller;
        }

        // fallback if no action translation -> action name
        if ($translationActionKey === $translationActionMessage) {
            $translationActionMessage = $this->action;
        }

        $headTitleParts = array(
            $firstTitlePartPrefix . $translationControllerMessage,
            $translationActionMessage,
        );

        return $headTitleParts;
    }

    public function postDispatch()
    {
        parent::postDispatch();

        // browser title bar
        if (empty($this->headTitleParts)) {
            $this->headTitleParts = $this->getDefaultHeadTitleParts();
        }

        foreach ($this->headTitleParts as $part) {
            $this->view->headTitle($part);
        }
    }

    /**
     *
     * if a user is logged in, create a new login entry in the db with userid, ip and timestamp
     */
    public static function historizeUserLogin()
    {
        try {
            $userId    = self::getUserInformation()->getId();
            $ipAddress = self::getUserInformation()->getIpAddress();


            $userHistory                             = array();
            $userHistory[Db_UserHistory::USER_ID]    = $userId;
            $userHistory[Db_UserHistory::IP_ADDRESS] = $ipAddress;

            $userHistoryDao = new Dao_UserHistory();
            $historyId      = $userHistoryDao->createUserHistory($userHistory);

            $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            $options   = $bootstrap->getOptions();

            $sess              = new Zend_Session_Namespace($options['auth']['user']['namespace']);
            $sess->userHistory = $historyId;

            // update session (user_id)
            $sessionId = Zend_Session::getId();
            $userHistoryDao->updateUserSession($sessionId, $userId, $ipAddress);
        } catch (Exception $e) {
            // don't handle those exceptions
        }
    }

    public static function getUserHistory()
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();

        $sess = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        return $sess->userHistory;
    }

    /**
     *
     * log each user action for a user session in the db
     * TODO: make configurable??
     */
    public function historizeUserRequest()
    {
        try {

            $userHistoryId = self::getUserHistory();

            if (!$userHistoryId)
                return;

            $userHistory                                        = array();
            $userHistory[Db_UserHistoryAction::USER_HISTORY_ID] = $userHistoryId;
            $userHistory[Db_UserHistoryAction::ACTION]          = $this->getRequest()->getRequestUri();

            $userHistoryDao = new Dao_UserHistory();
            $userHistoryDao->createUserHistoryAction($userHistory);
        } catch (Exception $e) {
            // $this->logger->log($e); -> removed due to log-spam
        }
    }

    /**
     * @param Dto_UserDto $userDto
     */
    public static function storeUserInformation($userDto)
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();

        $sess                      = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        $sess->id                  = $userDto->getId();
        $sess->username            = $userDto->getUsername();
        $sess->password            = $userDto->getPassword();
        $sess->firstname           = $userDto->getFirstname();
        $sess->lastname            = $userDto->getLastname();
        $sess->root                = $userDto->getRoot();
        $sess->valid               = $userDto->getValid();
        $sess->description         = $userDto->getDescription();
        $sess->note                = $userDto->getNote();
        $sess->themeId             = $userDto->getThemeId();
        $sess->ciDelete            = $userDto->getCiDelete();
        $sess->relationEdit        = $userDto->getRelationEdit();
        $sess->ldapAuth            = $userDto->getLdapAuth();
        $sess->language            = $userDto->getLanguage();
        $sess->layout              = $userDto->getLayout();
        $sess->lastAction          = $userDto->getLastAction();
        $sess->ipAddress           = $userDto->getIpAddress();
        $sess->twoFactorAuth       = $userDto->getTwoFactorAuth();
        $sess->displayAnnouncement = $userDto->getDisplayAnnouncement();
    }


    public static function getUserInformation()
    {
        if (self::$_userInformation) {
            return self::$_userInformation;
        }
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();

        $sess = new Zend_Session_Namespace($options['auth']['user']['namespace']);

        $userDto = new Dto_UserDto();
        $userDto->setId($sess->id);
        $userDto->setUsername($sess->username);
        $userDto->setPassword($sess->password);
        $userDto->setFirstname($sess->firstname);
        $userDto->setLastname($sess->lastname);
        $userDto->setRoot($sess->root);
        $userDto->setValid($sess->valid);
        $userDto->setDescription($sess->description);
        $userDto->setNote($sess->note);
        $userDto->setThemeId($sess->themeId);
        $userDto->setCiDelete($sess->ciDelete);
        $userDto->setRelationEdit($sess->relationEdit);
        $userDto->setLdapAuth($sess->ldapAuth);
        $userDto->setLanguage($sess->language);
        $userDto->setLayout($sess->layout);
        $userDto->setLastAction($sess->lastAction);
        $userDto->setIpAddress($sess->ipAddress);
        $userDto->setTwoFactorAuth($sess->twoFactorAuth);
        $userDto->setDisplayAnnouncement($sess->displayAnnouncement);
        self::$_userInformation = $userDto;
        return $userDto;
    }

    public function storePinnedProjects($projects)
    {
        $options              = $this->getInvokeArg('bootstrap')->getOptions();
        $sess                 = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        $sess->pinnedProjects = $projects;
    }

    public function getPinnedProjects()
    {
        $options = $this->getInvokeArg('bootstrap')->getOptions();
        $sess    = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        if (isset($sess->pinnedProjects) && $sess->pinnedProjects !== "") {
            return $sess->pinnedProjects;
        } else {
            return json_encode(array());
        }
    }

    /**
     * Helper function which checks if pinned projects have changed.
     * If flagPinnedProjects has been called this returns true and if this function is called
     * again before flagPinnedProjects was called again it will return false.
     *
     * @return bool
     */
    public function pinnedProjectsChanged()
    {
        $options = $this->getInvokeArg('bootstrap')->getOptions();
        $sess    = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        if (isset($sess->pinnedProjectsChanged)) {
            return $sess->pinnedProjectsChanged;
        } else {
            // first call of function
            $sess->pinnedProjectsChanged = false;
            return true;
        }
    }

    /**
     * Helper function to signal that the pinned projects have changed and
     * that the cache has to be updated.
     */
    public function flagPinnedProjects()
    {
        $options                     = $this->getInvokeArg('bootstrap')->getOptions();
        $sess                        = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        $sess->pinnedProjectsChanged = true;
    }

    /**
     * Helper function to remove the signal that the pinned projects have changed.
     */
    public function unflagPinnedProjects()
    {
        $options                     = $this->getInvokeArg('bootstrap')->getOptions();
        $sess                        = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        $sess->pinnedProjectsChanged = false;
    }

    /**
     * sotres the current selected Project ID in Session
     *
     * @param int $projectId
     *
     * @return void
     * @throws Exception_AccessDenied
     * @throws Zend_Session_Exception
     */
    public function storeCurrentProjectId($projectId)
    {
        $options     = $this->getInvokeArg('bootstrap')->getOptions();
        $sess        = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        $projectList = $this->_projectBar;

        $isAllowed = false;

        // no project id = all projects of user
        if ($projectId === null) {
            $isAllowed = true;
        } else { // specific project selected
            foreach ($projectList as $project) {
                if ($projectId === $project[Db_Project::ID]) {
                    $isAllowed = true;
                    break;
                }
            }
        }

        if ($isAllowed === true) {
            $sess->currentProjectId = $projectId;
        } else {
            $msg = sprintf(
                "switching to project without permission - project id: %s, user_id: %s",
                $projectId,
                self::getUserInformation()->getId()
            );
            throw new Exception_AccessDenied($msg);
        }
    }

    /**
     * retrieves the Project ID from Session
     *
     * @return mixed the current selected Project ID, NULL.. if no project selected
     */
    public function getCurrentProjectId()
    {
        $options = $this->getInvokeArg('bootstrap')->getOptions();
        $sess    = new Zend_Session_Namespace($options['auth']['user']['namespace']);
        return $sess->currentProjectId;
    }

    /**
     * prepares cache for further caching
     *
     * @param mixed $config
     */
    private function prepareCache(&$config)
    {
        // retrieve navibar from session

        // front end options, cache for 30 secs
        $frontendOptions = array(
            'lifetime'                => $config->cache->lifetime,
            'automatic_serialization' => true,
            'cache_id_prefix'         => str_replace('/', '', APPLICATION_FOLDER),
        );

        // backend options
        $backendOptions = array(
            'cache_dir' => $config->cache->directory // Directory where to put the cache files
        );

        $out = "File";

        if ($config->cache->apc)
            $out = "APC";

        $this->_cache = Zend_Cache::factory('Page',
            $out,
            $frontendOptions,
            $backendOptions);

        self::$cacheID = self::getUserInformation()->getId();
    }


    /**
     * enables Navigation Cache
     */
    private function enableNavigationCache(&$config)
    {
        // cache navigation
        if (!($this->_naviBar = $this->_cache->load(self::$cacheID . 'navi'))) {
            // create navigation tree
            $this->_naviBar = Util_Navigation::createNavigationTree($this->translator, self::getUserInformation(), self::getCurrentProjectId(), $config);
            $this->_cache->save($this->_naviBar);
        }
    }

    /**
     * enables Project Cache
     */
    private function enableProjectCache()
    {
        // cache project list
        if (!($this->_projectBar = $this->_cache->load(self::$cacheID . 'project'))) {
            // create navigation tree
            $this->_projectBar = Util_Project::createProjectList($this->translator, self::getUserInformation());
            if (count($this->_projectBar) == 1)
                $this->storeCurrentProjectId($this->_projectBar[0][Db_Project::ID]);
            $this->_cache->save($this->_projectBar);
        }
    }

    /**
     * Checks if the pinned projects have changed. If yes updates the cache with the new data,
     * if not it just returns the cache.
     */
    private function enablePinnedProjectsCache()
    {
        if ($this->pinnedProjectsChanged()) {
            $userGet  = new Service_User_Get($this->translator, $this->logger, 0);
            $settings = $userGet->getUserSettings(self::getUserInformation()->getId());
            if (is_null($settings)) {
                $this->storePinnedProjects(json_encode(array()));
            } else {
                $settings        = json_decode($settings, true);
                $pinned_projects = $settings['pinned_projects'];
                if (is_null($pinned_projects) || $pinned_projects === "") {
                    $this->storePinnedProjects(json_encode(array()));
                } else {
                    $this->storePinnedProjects(json_encode($pinned_projects));
                }
            }
            $this->unflagPinnedProjects();
        }
    }


    private function enableIndividualizationCache()
    {
        if (!($this->_individualizationConfig = $this->_cache->load(self::$cacheID . 'individualization'))) {
            // load individualization config
            $this->_individualizationConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/individualization.ini', APPLICATION_ENV);
            $this->_cache->save($this->_individualizationConfig);
        }
    }

    private function getSearchElementForm()
    {
        $form = new Zend_Form('searchShort');
        $form->setTranslator($this->translator);
        $form->setName('searchShort');
        $form->setAction(APPLICATION_URL . 'search/index');
        $form->setMethod('GET');

        $searchField = new Zend_Form_Element_Text('searchstring');
        $searchField->setLabel('searchstring');
        $searchField->setAttrib('class', 'attribute_search_string');

        $form->addElement($searchField);

        $searchButton = new Zend_Form_Element_Submit('searchShortButton');
        $searchButton->setAttrib('class', 'attribute_search_button attribute_search_button_color');
        /*$searchButton->setLabel('search');*/
        $searchButton->setLabel('searchGlobal');
        $searchButton->setDecorators(array(
            'ViewHelper',
            'Errors',
        ));

        $searchButton->setRequired(true);
        $form->addElement($searchButton);

        return $form;

    }


    protected function clearNavigationCache()
    {
        $this->_cache->remove(self::$cacheID . 'navi');
    }

    protected function clearProjectCache()
    {
        $this->_cache->remove(self::$cacheID . 'project');
    }

    protected function clearIndividualizationCache()
    {
        $this->_cache->remove(self::$cacheID . 'individualization');
    }

    protected function clearTranslationCache()
    {
        $directorypath = APPLICATION_PATH . '/../data/cache/locales';
        $directory     = scandir($directorypath);

        foreach ($directory as $file) {
            if (is_file($directorypath .'/'. $file)) {
                unlink($directorypath .'/'. $file);
            }
        }
    }


    public function __call($method, $arguments)
    {
        if (!method_exists($this, $method)) {
            $this->_redirect('/index');
        }
    }

    public function setTranslatorLocal()
    {
        $userLanguage = self::getUserInformation();
        $this->translator->setLocale($userLanguage->getLanguage());
    }

    protected function addUserTranslation($translation)
    {
        $fileDE = $this->userLanguagePath . '/de/' . $translation . '_de.csv';
        $fileEN = $this->userLanguagePath . '/en/' . $translation . '_en.csv';

        if (is_file($fileDE))
            $this->translator->addTranslation($fileDE, 'de');

        if (is_file($fileEN))
            $this->translator->addTranslation($fileEN, 'en');
    }

    public static function logout(bool $allDevices = false)
    {
        $lockingDao = new Dao_Lock();
        $adminDao   = new Dao_Admin();
        $userId     = self::getUserInformation()->getId();

        try {
            if (!empty($userId)) {
                $lockingDao->deleteLocksOfUser(self::getUserInformation()->getId());
                if ($allDevices === true) {
                    $adminDao->deleteSessionsOfUser($userId);
                }
            }

            if (Zend_Session::sessionExists()) {
                Zend_Session::destroy();
            }
        } catch (Exception $e) {
            Zend_Registry::get('Log')->log($e, Zend_Log::CRIT);
        }
    }

    public function getCmdbVersion()
    {
        $filename = APPLICATION_PATH . '/../version_info.txt';

        $version['version']           = 'DEV';
        $version['major']             = 'DEV';
        $version['minor']             = '';
        $version['patch level']       = '';
        $version['commits since tag'] = '';
        $version['build number']      = md5(date('Y-m-d H:i:s'));

        if (file_exists($filename)) {
            $versionString = file_get_contents($filename);

            if ($versionString != '' && $versionString != 'rw_local' && preg_match("/^v([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9]+?))?-(.*?)$/", $versionString, $versionParts)) {
                $version['version']           = $versionParts[0];
                $version['major']             = $versionParts[1];
                $version['minor']             = $versionParts[2];
                $version['patch level']       = $versionParts[3];
                $version['commits since tag'] = $versionParts[4];
                $version['build number']      = $versionParts[5];

            }
        }

        return $version;
    }

    public function _getParam($Param = false, $default = null)
    {
        $value = parent::_getParam($Param, $default);
        #trim strings --> number, boolean, null, ... don't need to be trimmed
        if (is_string($value)) {
            $value = trim($value);
        }
        return $value;
    }

    public function setupItemsCountPerPage($configIdentifier, $typeId = null)
    {
        // init session namespace
        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');

        // init session variable if not set
        if (!isset($itemCountPerPageSession->itemCountPerPage)) {
            $itemCountPerPageSession->itemCountPerPage = array();
        }

        // set item count per page for given list
        if (!isset($itemCountPerPageSession->itemCountPerPage[$configIdentifier]) || (!empty($typeId) && !isset($itemCountPerPageSession->itemCountPerPage[$configIdentifier][$typeId]))) {
            $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/' . $configIdentifier . '.ini', APPLICATION_ENV);
            $generalConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination.ini', APPLICATION_ENV);


            $itemCountPerPage = 25; // last fallback if no config is set
            if (isset($config->pagination->itemsCountPerPage)) {
                $itemCountPerPage = $config->pagination->itemsCountPerPage;
            } elseif (isset($generalConfig->pagination->itemsCountPerPage)) {
                $itemCountPerPage = $generalConfig->pagination->itemsCountPerPage;
            }

            if (empty($typeId)) {
                $itemCountPerPageSession->itemCountPerPage[$configIdentifier] = $itemCountPerPage;
            } else {
                $itemCountPerPageSession->itemCountPerPage[$configIdentifier][$typeId] = $itemCountPerPage;
                $itemCountPerPageSession->itemCountPerPage['options']['typeId']        = $typeId;
            }


            $itemsPerPageOptions = array(10, 25, 50, 75, 100, 500); // last fallback if no config is set
            if (isset($config->pagination->itemsPerPageOptions)) {
                $itemsPerPageOptions = $config->pagination->itemsPerPageOptions;
            } elseif (isset($generalConfig->pagination->itemsPerPageOptions)) {
                $itemsPerPageOptions = $generalConfig->pagination->itemsPerPageOptions;
            }
            $itemCountPerPageSession->itemPerPageOptions[$configIdentifier] = $itemsPerPageOptions;

        }

        $itemCountPerPageSession->itemCountPerPage['options']['execution_class'] = $configIdentifier;

        return $itemCountPerPageSession;
    }

    public function refreshlockAction()
    {
        $lock_id = $this->_getParam('lock_id');
        $result  = false;

        $lock = Util_Locking::getById($lock_id);
        if ($lock->isEnabled()) {
            $heldBy = $lock->getHeldBy();
            $userId = self::getUserInformation()->getId();
            if ($heldBy == $userId) {
                $result = $lock->refresh();
            }
        } else {
            // should only happen if locking was enabled before and now is disabled
            $result = true;
        }

        $response = array(
            'success' => $result,
            'lock'    => $lock->getAsArray(),
        );

        echo json_encode($response);
        exit;
    }

    /**
     * @param Util_Locking $lock
     * @param              $notifications
     */
    protected function lockErrorMessage($lock, $forceEditUrl = null)
    {
        /** @var Zend_Controller_Action_Helper_Url $urlHelper */
        $urlHelper = $this->_helper->getHelper('url');
        $user_service_get = new Service_User_Get($this->translator, $this->logger, 0);

        $username = 'unknown';
        $heldBy   = $lock->getHeldBy();
        if (!empty($heldBy)) {
            $lock_holder = $user_service_get->getUser($lock->getHeldBy());
            $username    = $lock_holder[Db_User::USERNAME];
        }

        if ($forceEditUrl === null) {
            $params = $this->getRequest()->getParams();
            $params['forcelock'] = 'true';

            $forceEditUrl = $urlHelper->url($params);
        }

        $lockMessage = $this->translator->translate("lockHeld");
        $lockedSince = $lock->getLockedSince()->format('Y-m-d H:i:s');

        $notifications['error'] = sprintf($lockMessage, $username, $lockedSince, $forceEditUrl);
        $this->_helper->FlashMessenger($notifications);
    }
}
