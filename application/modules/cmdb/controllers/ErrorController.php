<?php

/**
 * This class handles all Errors occurring in the application.
 *
 *
 *
 */
class ErrorController extends Zend_Controller_Action
{
    /**
     * @var Zend_Log
     */
    protected $logger;

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        $this->logger = Zend_Registry::get('Log');

        parent::__construct($request, $response, $invokeArgs);
    }

    public function errorAction()
    {

        $options      = $this->getInvokeArg('bootstrap')->getOptions();
        $isProduction = $options['isproduction'];

        $errors = $this->_getParam('error_handler');

        $this->logException($errors);

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Page not found';
                break;
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
                $this->handleAppException($errors);
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->message = 'Application error';
                break;
        }

        $this->view->exception = $errors->exception;
        $this->view->request   = $errors->request;

        if ($isProduction || APPLICATION_ENV === 'production') {
            $this->render('proderror');
        }
    }


    private function handleAppException($errors)
    {
        $this->getResponse()->setHttpResponseCode(400);
        $this->view->message = $errors->exception->getMessage();
        $translator          = Zend_Registry::get('Zend_Translate');
        $notification        = array();

        if ($errors->exception instanceof Exception_File) {
            $this->view->message = "File Error";
        } elseif ($errors->exception instanceof Exception_AccessDenied) {
            $notification['error'] = $translator->_('exceptionAccessDenied');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect('index/index');
        } elseif ($errors->exception instanceof Exception_InvalidParameter) {
            $notification['error'] = $translator->_('exceptionInvalidParameter');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect('index/index');
        }
    }

    private function logException($error)
    {
        $logLevel = Zend_Log::ERR;

        if (
            $error->exception instanceof Exception_File ||
            $error->exception instanceof Exception_AccessDenied ||
            $error->exception instanceof Exception_InvalidParameter
        ) {
            $logLevel = Zend_Log::WARN;
        }

        $logMessage = sprintf(
            "Exception thrown while requesting %s: %s",
            $error->request->getRequestUri(),
            $error->exception
        );
        $this->logger->log($logMessage, $logLevel);
    }


    /**
     * CSRF Attack
     */
    public function csrfForbiddenAction()
    {
        $this->getResponse()->setHttpResponseCode(403);
        $this->view->title   = '403 Forbidden';
        $this->view->message = 'Cross site request forgery detected. Request aborted';
        $this->render('error');
    }
}