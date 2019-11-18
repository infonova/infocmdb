<?php

class ApiV2_Bootstrap extends Zend_Application_Module_Bootstrap
{
    public function _initConstants()
    {
        if (!defined("APPLICATION_APIV2_BASEURL")) {
            define("APPLICATION_APIV2_BASEURL", APPLICATION_URL . 'apiV2');
        }
    }

    public function _initRoute()
    {
        //getting an instance of zend front controller.
        $frontController = Zend_Controller_Front::getInstance();
        $module          = $frontController->getRequest()->getModuleName();
        $controller      = $frontController->getRequest()->getControllerName();

        if ($module == 'apiV2' && $controller !== 'auth') {
            //initializing a Zend_Rest_Route
            $restRoute = new Zend_Rest_Route ($frontController);
            //let all actions to use Zend_Rest_Route.
            $frontController->getRouter()->addRoute('default', $restRoute);
        }
    }

}