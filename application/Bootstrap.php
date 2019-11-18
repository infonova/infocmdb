<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    private $front;

    protected function _initAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => '',
            'basePath'  => dirname(__FILE__),
        ));

        $autoloader->addResourceType('exception', 'exceptions/', 'Exception');

        $autoloader->addResourceType('util', 'utils/', 'Util');
        $autoloader->addResourceType('enum', 'utils/enum/', 'Enum');
        $autoloader->addResourceType('import', 'services/Queue/Processor/Import/', 'Import');
        $autoloader->addResourceType('export', 'services/Queue/Processor/Export/', 'Export');
        $autoloader->addResourceType('process', 'services/Queue/Processor/Process/', 'Process');

        $autoloader->addResourceType('service', 'services/', 'Service');

        $autoloader->addResourceType('notification', 'services/Notification', 'Notification');

        $autoloader->addResourceType('search', 'utils/search/', 'Util_Search');
        $autoloader->addResourceType('searchdocuments', 'utils/search/documents/', 'Util_Search_Document');

        $autoloader->addResourceType('form', 'forms/', 'Form');

        $autoloader->addResourceType('dao', 'models/', 'Dao');
        $autoloader->addResourceType('do', 'models/mapping/', 'Db');
        $autoloader->addResourceType('dto', 'models/dto/', 'Dto');

        $autoloader->addResourceType('plugin', 'plugins/', 'Plugin');
        $autoloader->addResourceType('attributeType', 'utils/attributeType/', 'Util_AttributeType');

        return $autoloader;
    }


    protected function _initSession()
    {
        // do nothing
    }

    protected function _initView()
    {
        $options = $this->getOptions();
        if (!isset($options['resources']['view'])) return;
        $config = $options['resources']['view'];
        if (isset($config)) {
            $view = new Zend_View($config);
        } else {
            $view = new Zend_View;
        }
        $view->setUseStreamWrapper(true);
        if (isset($config['doctype'])) {
            $view->doctype($config['doctype']);
        }
        if (isset($config['language'])) {
            $view->headMeta()->appendName('language', $config['language']);
        }
        if (isset($config['charset'])) {
            $view->headMeta()->appendName('charset', $config['charset']);
        }

        $individualizationConfig = new Util_Config('individualization.ini', APPLICATION_ENV);
        $cmdbName                = $individualizationConfig->getValue('title', 'infoCMDB');
        $titleSeperator          = $individualizationConfig->getValue('pageTitle.seperator', ' / ');

        $view->headTitle()->setSeparator($titleSeperator);
        $view->headTitle()->setPostfix(' - ' . $cmdbName);

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper(
            'ViewRenderer'
        );
        date_default_timezone_set('Europe/Vienna'); // TODO: replace me with something more generic
        $viewRenderer->setView($view);
        return $view;
    }


    protected function _initRequest()
    {
        $this->bootstrap('FrontController');
        $this->front = $this->getResource('FrontController');
        $request     = $this->front->getRequest();
        if (null === $this->front->getRequest()) {
            $request = new Zend_Controller_Request_Http();
            $this->front->setRequest($request);
        }
        return $request;
    }


    protected function _initResponse()
    {
        $options = $this->getOptions();
        if (!isset($options['response']['defaultContentType'])) {
            return;
        }
        $response = new Zend_Controller_Response_Http;
        $response->setHeader('Content-Type',
            $options['response']['defaultContentType'], true);
        $this->bootstrap('FrontController');
        $this->getResource('FrontController')->setResponse($response);
    }

    protected function _initForceSSL()
    {
        $options = $this->getOptions();

        if (
            php_sapi_name() !== 'cli'
            && (isset($_SERVER["REQUEST_URI"]) && !preg_match('/\/(api|scheduler)\//', $_SERVER["REQUEST_URI"]))
            && (isset($options['forceHTTPS']) && $options['forceHTTPS'] == 1)
            && (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '443')
        ) {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    /*
     * Removes possible XSS attacks by stripping js code from request
     * Add Controller to $excluded_controllers to exclude controller
     */
    protected function _initXSSCleanAllParams()
    {
        $request  = Zend_Controller_Front::getInstance()->getRequest();
        $response = new Zend_Controller_Response_Http();
        Zend_Controller_Front::getInstance()->getRouter()->route($request);
        $current_controller = $request->getControllerName();
        $current_action     = $request->getActionName();
        //exlude the following controllers
        //don't forget to clean params manually --> ServiceAbstract->xssClean or Bootstrap::xssClean
        $excluded_controllers = array(
            'attribute',
            'query',
            'workflow',
            'mail',
        );

        // Logging every Request temporary
        // Format: data/logs/requests/20141105_12.log
        // massive log-output --> delete old files
        $logControllers = array(
            //'ci',
            //'attribute'
        );
        if (in_array($current_controller, $logControllers)) {
            $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . "/../data/logs/requests/" . date("Ymd_H") . ".log");
            $logger = new Zend_Log($writer);
            if (
                $current_action != 'custom.css' && $current_action != 'tiny_mce' &&
                $current_action != 'autocompletemultiselect' && $current_action != 'autocompleteattributeid' &&
                !($current_controller == 'index' && $current_action == 'index')

            ) {
                if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $client_ip = $_SERVER['REMOTE_ADDR'];
                } else {
                    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }

                $logger->log(
                    $client_ip . ";" . $current_controller . "/" . $current_action .
                    "\n    Params: " . json_encode($request->getParams())
                    , LOG_ERR);
            }
        }
        //END Logging every Request temporary

        if (!in_array($current_controller, $excluded_controllers)) {
            //POST-Parameter: remove all JS
            $postParams     = $request->getPost();
            $safePostParams = array();
            foreach ($postParams as $key => $value) {
                $value                = Bootstrap::xssClean($value);
                $safePostParams[$key] = $value;
            }
            $request->setPost($safePostParams);

            //GET-Parameter: can't update GET-Parameter, forwarding with POST-Params not possible --> show error message
            $getParams = $request->getParams();
            $getParams = array_diff($getParams, $postParams);//get only GET-Params not possible --> all Params without POST
            foreach ($getParams as $key => $value) {
                $newValue = Bootstrap::xssClean($value);
                if ($value != $newValue) {
                    die("ERROR: Detected possible XSS-attack in URL. Please remove all Javascript-Code from Parameter '" . $key . "'!<br />
					In the case you didn't changed the URL and just clicked somewhere in the application, please contact the support!<br />
					<br />
					<a href='#' onclick='history.back()'>Back</a>");
                }
            }
        }
    }

    protected function _initPlugins()
    {
        $options = $this->getOptions();

        $front = Zend_Controller_Front::getInstance();
        if (!$front->hasPlugin('Plugin_Log')) {
            $front->registerPlugin(new Plugin_Log(), 20);
            $front->registerPlugin(new Plugin_Translation(), 30);

            if (!$options['database']['params']['host']) {
                $front->registerPlugin(new Plugin_Installer(), 35);
            } else {

                $front->registerPlugin(new Plugin_Db(), 40);
                $front->registerPlugin(new Plugin_Session(), 41);

                if (isset($options['isConsole'])) {
                    $front->registerPlugin(new Plugin_Console(), 45);
                }

                // register module specific plugins
                $front->registerPlugin(new Plugin_Cmdb_Authentication(), 50);

                $front->registerPlugin(new Plugin_Rest_Authentication(), 60);
                $front->registerPlugin(new Plugin_Rest_Route(), 70);
            }
            $front->registerPlugin(new Plugin_ControllerGuard(), 80);
        }
    }

    /*
     * _initModuleLayoutPath will set the LayoutPath to:
     *  APPLICATION_PATH/modules/<current_module>/layouts
     *  if the directory exists.
     *
     *  This allows to split layout files by module structure.
     */
    protected function _initModuleLayoutPath()
    {
        $front            = Zend_Controller_Front::getInstance();

        $currentModule    = $front->getRequest()->getModuleName();
        $moduleLayoutPath = APPLICATION_PATH . "/modules/" . $currentModule . "/layouts";

        if (is_dir($moduleLayoutPath) === false) {
            // if path doesn't exists, default will be used
            $defaultModule    = $front->getDefaultModule();
            $moduleLayoutPath = APPLICATION_PATH . "/modules/" . $defaultModule . "/layouts";
        }

        $options                                      = $this->getOptions();
        $options['resources']['layout']['layoutPath'] = $moduleLayoutPath;
        $this->setOptions($options);
    }

    /*
     * Function to prevent XSS-attacks
     * Removes JS-Code of given string
     * @param string $data string to clean
     * @return string string without js
     */
    public static function xssClean($data)
    {
        return $data;
        if (!is_array($data)) {
            $dataArray = array($data);
            $isArray   = 0;
        } else {
            $dataArray = $data;
            $isArray   = 1;
        }

        foreach ($dataArray as $key => $data) {
            // Fix &entity\n;
            $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
            $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
            $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
            $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

            // Remove any attribute starting with "on" or xmlns
            $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

            // Remove javascript: and vbscript: protocols
            $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
            $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
            $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

            // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
            $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
            $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
            $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

            // Remove namespaced elements (we do not need them)
            $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

            do {
                // Remove really unwanted tags
                $old_data = $data;
                $data     = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|video|source|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
            } while ($old_data !== $data);

            $dataArray[$key] = $data;
        }

        if ($isArray == 0) {
            return $dataArray[0];
        } else {
            return $dataArray;
        }
    }

    /**
     * Logs an exception
     *
     * @param Exception $exception
     * @throws Zend_Exception
     */
    public static function logException($exception)
    {
        $logger = Zend_Registry::get('Log');
        $logLevel = Zend_Log::ERR;

        if (
            $exception instanceof Exception_File ||
            $exception instanceof Exception_AccessDenied ||
            $exception instanceof Exception_InvalidParameter
        ) {
            $logLevel = Zend_Log::WARN;
        }

        $requestUri  = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();

        $logMessage = sprintf(
            "Exception thrown while requesting %s: %s",
            $requestUri,
            $exception
        );
        $logger->log($logMessage, $logLevel);
    }
}

if (!function_exists('apache_request_headers')) {
    function apache_request_headers()
    {
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == "HTTP_") {
                $key       = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                $out[$key] = $value;
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }
}