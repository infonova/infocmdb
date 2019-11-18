<?php

abstract class BaseController extends Zend_Rest_Controller
{

    protected $logger     = null;
    protected $translator = null;
    protected $returnKey  = null;
    protected $user       = null;

    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        // API always uses "clean" layout
        $this->_helper->layout->setLayout('clean');

        if (is_null($this->logger)) {
            $this->logger = $this->logger = Zend_Registry::get('Log');
        }

        $this->translator = Zend_Registry::get('Zend_Translate');

        $this->initUser();
        $this->initReturnKey();

        parent::init();
    }

    protected function initUser()
    {
        $daoAuthentication = new Dao_Authentication;
        $daoUser           = new Dao_User();
        $auth              = Zend_Auth::getInstance();

        $apiKey = $this->_getParam('apikey');
        if ($apiKey === null) {
            $apiKey = $this->_request->getHeader('apikey');
        }

        $user = array();
        if ($auth->hasIdentity()) {
            $username = $auth->getIdentity();
            $user     = $daoUser->getUserByUsername($username);
        } elseif ($apiKey !== null) {
            $user = $daoAuthentication->getApiSessionUser($apiKey);
        }

        $this->user = $user;
    }

    protected function initReturnKey()
    {
        $method = $this->_getParam('method');
        if ($method === null) {
            $method = $this->_request->getHeader('method');
        }

        if ($method) {
            $this->setReturnKey($method);
        }
    }

    public function indexAction()
    {

    }

    public function headAction()
    {


    }

    public function listAction()
    {
        $this->getResponse()
            ->setHttpResponseCode(501);
    }

    public function getAction()
    {
        $this->getResponse()
            ->setHttpResponseCode(501);
    }

    public function postAction()
    {
        $this->getResponse()
            ->setHttpResponseCode(501);
    }

    public function putAction()
    {
        $this->getResponse()
            ->setHttpResponseCode(501);
    }

    public function deleteAction()
    {
        $this->getResponse()
            ->setHttpResponseCode(501);
    }


    /**
     * handle Exception_AccessDenied errors thrown in Service Objects
     */
    protected function forbidden()
    {
        $error            = array();
        $error['status']  = 'error';
        $error['message'] = "User (Api-key) is not allowed to execute this method";

        $message = Util_Query::getXML($error);
        $this->getResponse()
            ->setHttpResponseCode(403)
            ->appendBody($message);
    }


    protected function getUserInformation()
    {
        return $this->user;
    }

    protected function setReturnKey($key)
    {
        $this->returnKey = $key;
    }

    protected function getReturnValue($obj)
    {
        if (!$this->returnKey) {
            $this->returnKey = $this->_request->getHeader('format');
        }
        $result = "";

        if (!$this->returnKey) {
            $this->returnKey = 'xml';
        }

        return Util_Query::convertResult($obj, $this->returnKey);
    }


    public static function getXML($obj)
    {
        return Util_Query::getXML($obj);
    }

    public static function getJSON($obj)
    {
        return Util_Query::getJSON($obj);
    }

    public static function getPlain($obj)
    {
        return Util_Query::getPlain($obj);
    }

    /**
     * Generates a generic API response in the specified format (given by method)
     * Currently json and xml are supported.
     *
     * @param $success          bool if the requested action was performed successfully or not
     * @param $message          string message returned containing useful information for the requester
     * @param $data             array|string|null the data to be included in the response
     * @param $method           string the method to encode the data. xml or json are supported, default xml
     * @param $debug            array|null array containing debug information to be added to the response
     *                          this adds an additional key 'debug' to the array on the same level
     *                          as success, message and data
     *
     * @return string returns the response formatted using the specified method
     */
    public static function getApiResponse($success, $message, $data, $method, $debug = null)
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

        switch ($method) {
            case "json":
                return json_encode($response);
                break;
            case "xml":
                $xml = new SimpleXMLElement('<result/>');
                self::arrayToXml($response, $xml);
                return $xml->asXML();
                break;
            case "plain":
                return "Unsupported response type: " . $method;
                break;
            default:
                $xml = new SimpleXMLElement('<result/>');
                self::arrayToXml($response, $xml);
                return $xml->asXML();
        }
    }

    /**
     * Turns an array into a xml file. The given $xml is turned into the desired xml in-place.
     *
     * @param $array
     * @param $xml SimpleXMLElement
     */
    public static function arrayToXml($array, &$xml)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xml->addChild("$key");
                    self::arrayToXml($value, $subnode);
                } else {
                    self::arrayToXml($value, $xml);
                }
            } else {
                $xml->addChild("$key", "$value");
            }
        }
    }

    protected function outputContent($message, $data = null, $code = 200)
    {
        $returnFormat = $this->returnKey;

        $notification = self::getApiResponse(true, $message, $data, $returnFormat);

        $this->getResponse()
            ->setHttpResponseCode($code)
            ->appendBody($notification);
    }

    protected function outputError($message, $data = null, $code = 500)
    {
        $returnFormat = $this->returnKey;

        $notification = self::getApiResponse(false, $message, $data, $returnFormat);

        $this->getResponse()
            ->setHttpResponseCode($code)
            ->appendBody($notification);
    }

    public function __destruct()
    {
        $userHistoryDao = new Dao_UserHistory();
        $auth           = Zend_Auth::getInstance();

        // API-Auth does not have a identity --> logout after request for security reasons
        if (!$auth->hasIdentity() && Zend_Session::sessionExists()) {
            $sessionId = Zend_Session::getId();
            $userHistoryDao->deleteSession($sessionId); // delete db row for session
            Zend_Session::destroy(); // delete from browser session
        }
    }
}