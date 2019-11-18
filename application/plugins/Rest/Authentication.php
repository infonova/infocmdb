<?php

/**
 * Plugin_Authentication
 *
 *
 *
 */
class Plugin_Rest_Authentication extends Zend_Controller_Plugin_Abstract
{
    /**
     * preDispatch
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $params = $request->getParams();
        $apiKey = null;

        $module = $request->getModuleName();
        if ($module != 'api') {
            return true;
        }

        $controller = $request->getControllerName();
        if ($controller == 'login') {
            return true;
        }

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            return true;
        }

        if (isset($params['apikey'])) {
            $apiKey = $params['apikey'];
        }

        if (!$apiKey) {
            $apiKey = $request->getHeader('apikey');
        }

        $authInterface = new Dao_Authentication();
        $result        = $authInterface->getApiSession($apiKey);

        if (!$result) {
            $request->setModuleName('api')
                ->setControllerName('error')
                ->setActionName('access')
                ->setDispatched(true);

        } else if ($result[Db_ApiSession::VALID_TO] < time()) {
            $request->setModuleName('api')
                ->setControllerName('error')
                ->setActionName('timeout')
                ->setDispatched(true);
        }
    }
}