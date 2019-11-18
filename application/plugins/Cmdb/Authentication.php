<?php

/**
 * Plugin_Authentication
 *
 *
 *
 */
class Plugin_Cmdb_Authentication extends Zend_Controller_Plugin_Abstract
{
    /**
     * preDispatch
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();

        $module = $request->getModuleName();
        if ($module != 'cmdb') {
            return true;
        }


        // check the requested controller and add auth-exceptions (like login or error page)
        $controller = $this->getRequest()->getControllerName();
        if (($controller != 'login') && ($controller != 'error')) {
            if ($options['auth']['active']) {
                $params = $request->getParams();
                $this->checkSession($options, (isset($params['apikey'])) ? $params['apikey'] : null);
            }
        }
    }

    /**
     * checkSession
     */
    private function checkSession($options, $apiKey = null)
    {
        $session = Zend_Registry::get('session');

        // can not surly say, if that case will ever happen --> apikey is not set in cmdb module??
        if ($apiKey) {
            $authInterface = new Dao_Authentication();
            $result        = $authInterface->getApiSession($apiKey);

            if ($result[Db_ApiSession::VALID_TO] > time()) {
                $res = $authInterface->getApiSessionUser($apiKey);

                $sess               = new Zend_Session_Namespace($options['auth']['user']['namespace']);
                $sess->id           = $res[Db_User::ID];
                $sess->username     = $res[Db_User::USERNAME];
                $sess->password     = $res[Db_User::PASSWORD];
                $sess->lastname     = $res[Db_User::LASTNAME];
                $sess->valid        = $res[Db_User::IS_ACTIVE];
                $sess->description  = $res[Db_User::DESCRIPTION];
                $sess->note         = $res[Db_User::NOTE];
                $sess->themeId      = $res[Db_User::THEME_ID];
                $sess->ciDelete     = $res[Db_User::IS_CI_DELETE_ENABLED];
                $sess->relationEdit = $res[Db_User::IS_RELATION_EDIT_ENABLED];
                $sess->ldapAuth     = $res[Db_User::IS_LDAP_AUTH];
                $sess->language     = $res[Db_User::LANGUAGE];
                $sess->layout       = $res[Db_User::LAYOUT];
                $sess->lastAction   = time();
                $sess->ipAddress    = $_SERVER['REMOTE_ADDR'];

                $authInterface->setLastLogin($res[Db_User::USERNAME]);

                $session->username   = $res[Db_User::USERNAME];
                $session->lastAction = time();

                $sess->updateUserHistory = true;
            }
        }

        if (empty($session->username) || !$this->verifyLoginTimeout($session, $options)) {
            $params   = $this->getRequest()->getParams();
            $redirect = $params['controller'] . '/' . $params['action'];

            unset($params['controller']);
            unset($params['action']);
            unset($params['module']);

            if (count($params) > 0)
                foreach ($params as $key => $val) {
                    $redirect .= '/' . $key . '/' . $val;
                }

            $session->url = $redirect;
            $this->getResponse()->setRedirect(APPLICATION_URL . 'login/index')->sendResponse();
        }
    }


    private function verifyLoginTimeout($session, $options)
    {
        if ($options['auth']['login']['active']) {
            $loginTimeout = $options['auth']['login']['timeout'];
            $loginTimeout = time() - $loginTimeout;

            if ($session->lastAction < $loginTimeout) {
                return false;
            } else {
                $session->lastAction = time();
                return true;
            }
        } else {
            $session->lastAction = time();
            return true;
        }
    }
}
