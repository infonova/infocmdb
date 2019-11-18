<?php

class Plugin_Installer extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();


        $controller = $this->getRequest()->getControllerName();
        if (($controller != 'installer') && ($controller != 'error')) {
            $params = $request->getParams();
            $this->getResponse()->setRedirect(APPLICATION_URL . 'installer/index')->sendResponse();
        }
    }
}