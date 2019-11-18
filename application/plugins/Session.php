<?php

/**
 *
 * setup cmdb session handling.
 *
 * if db-session-storage enabled, session is stored in the user_Session table
 * otherwise the default session storage is used
 *
 * properties retrieved from the session.ini file
 *
 */
class Plugin_Session extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $config    = new Zend_Config_Ini(APPLICATION_PATH . '/configs/session.ini', APPLICATION_ENV);
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();

        $sessionConfig = array(
            'name'           => 'user_session',
            'primary'        => 'id',
            'modifiedColumn' => 'modified',
            'dataColumn'     => 'data',
            'lifetimeColumn' => 'lifetime',
        );

        if (!Zend_Session::isStarted()) {
            Zend_Session::setOptions($config->toArray());
        }

        if ($options['auth']['session']['db'] && is_null(Zend_Session::getSaveHandler())) {
            Zend_Session::setSaveHandler(new Util_SessionSaveHandler($sessionConfig));
        }

        $session = new Zend_Session_Namespace($options['auth']['namespace']);


        if ($options['auth']['login']['active'])
            $session->setExpirationSeconds($options['auth']['login']['timeout']);

        Zend_Registry::set('session', $session);
    }
}