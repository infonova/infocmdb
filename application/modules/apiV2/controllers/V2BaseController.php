<?php

require_once APPLICATION_PATH . '/../library/composer/autoload.php'; // loading library with composer autoloader

/**
 * @OA\Info(
 *     title="InfoCMDB apiV2",
 *     description="Documentation for apiV2 usage",
 *     version="1.0.0",
 * )
 * @OA\Server(url=APPLICATION_APIV2_BASEURL)
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     name="BearerAuth",
 *     securityScheme="apiV2_auth",
 * )
 */
abstract class V2BaseController extends Zend_Rest_Controller
{
    /**
     * @var Util_Log
     */
    protected $logger = null;

    /**
     * @var Zend_Translate
     */
    protected $translator = null;

    /**
     * @var array db-user-row
     */
    protected $user = array();

    /**
     * @var string jwt-token
     */
    protected $token = '';

    /**
     * @var bool identifies if action needs authentication
     */
    protected $needsAuth = true;

    /**
     * @var array routes which do not need authentication
     */
    protected $publicRoutes = array(
        'auth/token',
    );

    /**
     * @var bool|null|stdClass request data as json object
     */
    protected $jsonData = null;


    // TODO: implement options for api requests
    protected $options = array(
        self::OPTION_TRIGGER_WORKFLOWS => true,
    );

    const OPTION_TRIGGER_WORKFLOWS = 'trigger_workflow';

    /**
     * Init controller
     * @throws Zend_Exception
     */
    public function init()
    {
        // disable views
        $this->_helper->viewRenderer->setNoRender(true);

        // init components
        $this->logger     = Zend_Registry::get('Log');
        $this->translator = Zend_Registry::get('Zend_Translate');
        $this->translator->getAdapter()->setLocale('en');
        $this->_helper->layout->disableLayout();
        $this->initAuthentication();
        $this->initRequestParameters();

        parent::init();
    }

    /**
     * Authenticate and Authorize routes
     * @throws Zend_Controller_Exception
     * @throws Zend_Controller_Response_Exception
     * @throws Zend_Log_Exception
     * @throws Zend_Session_Exception
     */
    public function initAuthentication()
    {
        $request      = $this->getRequest();
        $currentRoute = $request->getControllerName() . '/' . $request->getActionName();

        if (in_array($currentRoute, $this->publicRoutes)) {
            $this->needsAuth = false;
        }

        $authResult = $this->authenticate();
        if ($this->needsAuth === true && $authResult === false) {
            $this->outputHttpStatusForbidden("Not authenticated");
        }

        $authorizationResult = $this->authorize();
        if ($authorizationResult === false) {
            $this->outputHttpStatusForbidden("Not authorized");
        }
    }

    /**
     * Parse JSON Body
     */
    public function initRequestParameters()
    {
        if($this->getRequest()->getControllerName() === 'fileupload') {
            return;
        }

        $data           = $this->getRequest()->getRawBody();
        if($data !== false
            && (
                // Backward compatibility for scripts not setting a proper header
                substr($data, 0, 1) === '{'
                || substr(filter_input(INPUT_ENV, 'CONTENT_TYPE'), 0, -5) === '/json'
            )
            && ($this->jsonData = json_decode($data)) === null) {
            $logMessage = sprintf(
                "ApiV2 - Failed to parse json Code: %s, Message: %s",
                json_last_error(), json_last_error_msg()
            );
            $this->logger->log($logMessage, Zend_log::WARN);
        }
    }

    /**
     * Authenticate via active Zend_Session or JWT token
     * @return bool true if user authentication was successful
     * @throws Zend_Log_Exception
     */
    protected function authenticate()
    {
        $daoAuthentication = new Dao_Authentication;
        $daoUser           = new Dao_User();
        $auth              = Zend_Auth::getInstance();

        $tokenString = $this->_request->getHeader('Authorization');
        $tokenString = str_replace('Bearer ', '', $tokenString);

        $authenticated    = false;
        $tokenAuthMessage = '';
        $securityLevel    = '';
        if ($auth->hasIdentity()) {
            $username      = $auth->getIdentity();
            $this->user    = $daoUser->getUserByUsername($username);
            $authenticated = true;
        } elseif (!empty($tokenString) && $daoAuthentication->isTokenValid($tokenString, $tokenAuthMessage, $securityLevel)) {
            $this->user    = $daoAuthentication->getApiSessionUserByToken($tokenString);
            $this->token   = $tokenString;
            $authenticated = true;
        }

        if ($authenticated === false && !empty($tokenAuthMessage)) {
            $logLevel = Zend_Log::NOTICE;
            if ($securityLevel === Dao_Authentication::SECURITY_RISK_HIGH) {
                $logLevel = Zend_log::WARN;
            }

            $logMessage = sprintf(
                "ApiV2 - Failed auth attempt: %s",
                $tokenAuthMessage
            );
            $this->logger->log($logMessage, $logLevel);
        }

        return $authenticated;
    }

    /**
     * Check if user is authorized for current route
     * @return bool true if user is authorized
     * @throws Zend_Controller_Exception
     * @throws Zend_Session_Exception
     */
    protected function authorize()
    {
        $sess                 = Plugin_ControllerGuard::getUserSessionStore();
        $needsPermissionCheck = $sess->needsPermissionCheck;

        // ControllerGuard checks permissions for us.
        // But because there is no user data available at that moment, we need to do it here.
        if ($needsPermissionCheck === true) {
            $result = Plugin_ControllerGuard::authorizeRequest($this->_request, (int) $this->getUserInformation()->getThemeId());
            return $result;
        }

        return true;
    }

    /**
     * Dispatch action and handle remaining exceptions
     *
     * @param string $action Method name of action
     * @throws Zend_Controller_Response_Exception
     */
    public function dispatch($action)
    {
        try {
            parent::dispatch($action);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Log exception and respond with error output
     *
     * @param Exception $e instance of the exception to handle
     * @throws Zend_Controller_Response_Exception
     */
    protected function handleException(Exception $e)
    {
        Bootstrap::logException($e);

        $options      = $this->getInvokeArg('bootstrap')->getOptions();
        $isProduction = $options['isproduction'];

        $httpStatusCode = 500;
        $message        = '';
        $data           = array();
        if ($e instanceof Zend_Controller_Action_Exception) {
            $httpStatusCode = $e->getCode();
        } elseif ($e instanceof Exception_AccessDenied) {
            $httpStatusCode = 401;
        }

        if ($message === '') {
            $message = Zend_Http_Response::responseCodeAsText($httpStatusCode);
        }

        if (!$isProduction && APPLICATION_ENV !== 'production') {
            $data = array(
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTrace(),
            );
        }

        $this->outputError($message, $data);
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function indexAction()
    {
        $this->list();
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function headAction()
    {
        $this->outputHttpStatusNotImplemented();
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function getAction()
    {
        $id = (int)$this->getParam('id', 0);
        $this->read($id);
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function postAction()
    {
        $this->insert();
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function putAction()
    {
        $id = (int)$this->getParam('id', 0);
        $this->update($id);
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function deleteAction()
    {
        $id = (int)$this->getParam('id', 0);
        $this->delete($id);
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function list()
    {
        $this->outputHttpStatusNotImplemented();
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function read(int $id)
    {
        $this->outputHttpStatusNotImplemented();
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function insert()
    {
        $this->outputHttpStatusNotImplemented();
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function update(int $id)
    {
        $this->outputHttpStatusNotImplemented();
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function delete(int $id)
    {
        $this->outputHttpStatusNotImplemented();
    }

    /**
     * Get value of JSON response body by key
     *
     * @param      $paramName
     * @param null $default
     * @return |null
     */
    public function getJsonParam($paramName, $default = null)
    {
        if ($this->jsonData !== null
            && property_exists($this->jsonData, $paramName)) {
            return $this->jsonData->$paramName;
        }

        return $default;
    }

    /**
     * Get current user object
     *
     * @return Dto_UserDto
     */
    public function getUserInformation()
    {
        $userObj = new Dto_UserDto($this->user);
        return $userObj;
    }

    /**
     * Generates a generic API response in the specified format (given by method)
     * Currently json and xml are supported.
     *
     * @param $success          bool if the requested action was performed successfully or not
     * @param $message          string message returned containing useful information for the requester
     * @param $data             array|string|null the data to be included in the response
     * @param $debug            array|null array containing debug information to be added to the response
     *                          this adds an additional key 'debug' to the array on the same level
     *                          as success, message and data
     *
     * @return string returns the response formatted using the specified method
     */
    public static function getApiResponse($success, $message, $data, $debug = null)
    {
        if (empty($data)) {
            $data = null;
        }

        $response            = array();
        $response['success'] = $success;
        $response['message'] = $message;
        $response['data']    = $data;

        if (!is_null($debug)) {
            $response['debug'] = $debug;
        }

        return json_encode($response);
    }

    /**
     * Output a standardized response
     *
     * @param boolean $success identifies if the request was successful
     * @param string  $message a short description to describe the status of the request
     * @param mixed   $data    additional data
     * @param integer $code    HTTP-Status-Code
     * @param mixed   $debug   additional debug information
     * @throws Zend_Controller_Response_Exception
     *
     */
    private function output($success, $message, $data, $code, $debug)
    {
        $notification = self::getApiResponse($success, $message, $data, $debug);

        $this->getResponse()
            ->setHttpResponseCode($code)
            ->setHeader('Content-Type', 'application/json')
            ->setBody($notification);

        $this->_response->sendHeaders();
        $this->_response->outputBody();
        exit;
    }


    /**
     * Output a successful response
     *
     * @param string  $message a short description to describe the status of the request
     * @param mixed   $data    additional data
     * @param integer $code    HTTP-Status-Code
     * @param mixed   $debug   additional debug information
     * @throws Zend_Controller_Response_Exception
     */
    protected function outputContent($message, $data = null, $code = 200, $debug = null)
    {
        $this->output(true, $message, $data, $code, $debug);
    }


    /**
     * Output a response with an error
     *
     * @param string  $message a short description to describe the status of the request
     * @param mixed   $data    additional data
     * @param integer $code    HTTP-Status-Code
     * @param mixed   $debug   additional debug information
     * @throws Zend_Controller_Response_Exception
     */
    protected function outputError($message, $data = null, $code = 500, $debug = null)
    {
        $this->output(false, $message, $data, $code, $debug);
    }

    /**
     * Output form validator errors
     *
     * @param      $data
     * @param null $debug
     * @throws Zend_Controller_Response_Exception
     */
    protected function outputValidationError($data, $debug = null)
    {
        $this->outputError('Validation failed', $data, 422, $debug);
    }

    /**
     * Output HTTP-Status-Code Forbidden (403)
     *
     * @param mixed $data additional data
     * @throws Zend_Controller_Response_Exception
     */
    protected function outputHttpStatusForbidden($data = null)
    {
        $code    = 403;
        $message = Zend_Http_Response::responseCodeAsText($code);

        $this->outputError($message, $data, $code);
    }

    /**
     * Output HTTP-Status-Code Not Found (404)
     *
     * @param mixed $data additional data
     * @throws Zend_Controller_Response_Exception
     */
    protected function outputHttpStatusNotFound($data = null)
    {
        $code    = 404;
        $message = Zend_Http_Response::responseCodeAsText($code);

        $this->outputError($message, $data, $code);
    }

    /**
     * Output HTTP-Status-Code Internal Server Error (500)
     *
     * @param mixed $data additional data
     * @throws Zend_Controller_Response_Exception
     */
    protected function outputHttpStatusInternalServerError($data = null)
    {
        $code    = 500;
        $message = Zend_Http_Response::responseCodeAsText($code);

        $this->outputError($message, $data, $code);
    }

    /**
     * Output HTTP-Status-Code Not Implemented (501)
     *
     * @param mixed $data additional data
     * @throws Zend_Controller_Response_Exception
     */
    protected function outputHttpStatusNotImplemented($data = null)
    {
        $code    = 501;
        $message = Zend_Http_Response::responseCodeAsText($code);

        $this->outputError($message, $data, $code);
    }

}