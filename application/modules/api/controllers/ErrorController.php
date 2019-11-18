<?php

/**
 * This class handles all Errors occurring in the application.
 *
 *
 *
 */
class Api_ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        // TODO: implement me
    }


    /**
     * Access Attack
     */
    public function accessAction()
    {
        require_once 'BaseController.php';

        $error            = array();
        $error['status']  = 'error';
        $error['message'] = "Invalid API Key. Please login.";

        $message = BaseController::getXML($error);

        $this->getResponse()
            ->setHttpResponseCode(403)
            ->appendBody($message);
        $this->_helper->ViewRenderer->setNoRender(true);
        $this->getResponse()->sendResponse();
        exit;
    }

    /**
     *
     */
    public function timeoutAction()
    {
        require_once 'BaseController.php';

        $error            = array();
        $error['status']  = 'error';
        $error['message'] = "Timeout. API Key is invalid. Please login again.";

        $message = BaseController::getXML($error);

        $this->getResponse()
            ->setHttpResponseCode(403)
            ->appendBody($message);
        $this->_helper->ViewRenderer->setNoRender(true);
        $this->getResponse()->sendResponse();
        exit;
    }

}