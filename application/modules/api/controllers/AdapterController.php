<?php
require_once 'BaseController.php';

class Api_AdapterController extends BaseController
{

    public function indexAction()
    {
        $this->_forward('get');
    }

    public function headAction()
    {
    }

    /**
     * retrieve single ci
     */
    public function getAction()
    {

        if ($this->_hasParam('query')) {
            return $this->executeQuery();
        } elseif ($this->_hasParam('workflow')) {
            return $this->executeWorkflow();
        } elseif ($this->_hasParam("executable_attribute_name")) {
            return $this->executeExecutable();
        } else {
            echo 'Invalid Operation';
            exit;
        }
    }

    public function putAction()
    {

        if ($this->_hasParam('query')) {
            return $this->executeQuery();
        } elseif ($this->_hasParam('workflow')) {
            return $this->executeWorkflow();
        } elseif ($this->_hasParam("executable_attribute_name")) {
            return $this->executeExecutable();
        } else {
            echo 'Invalid Operation';
            exit;
        }
    }

    private function executeQuery()
    {


        header('Content-Type: text/javascript; charset=utf8');
        $apikey     = $this->_getParam('apikey');
        $scriptName = $this->_getParam('query');
        $method     = $this->_getParam('method'); //xml. json, plain
        $groupCi    = $this->_getParam('group');

        try {
            $queryDaoImpl = new Dao_Query();
            $query        = $queryDaoImpl->getQueryByName($scriptName);

            $queryId = $query[Db_StoredQuery::ID];

            if (!$query) {
                throw new Exception_Query_InvalidStoredScript();
            } else if (!$query[Db_StoredQuery::IS_ACTIVE]) {
                throw new Exception_Query_StoredScriptInactive();
            }

            $query = $query[Db_StoredQuery::QUERY];

            $user   = $this->getUserInformation();
            $userId = '0';
            if (isset($user[Db_User::ID])) {
                $userId = $user[Db_User::ID];
            }

            $parameter  = array();
            $paramCount = substr_count($query, 'argv');
            for ($param = 1; $param <= $paramCount; $param++) {
                $parameterName             = 'argv' . $param;
                $parameter[$parameterName] = $this->_getParam($parameterName);
            }
            $parameter['user_id'] = $userId;

            $query = trim($query);

            $result = array();
            $code   = 400;

            $this->logger->log("executing Webservice: " . $scriptName, Zend_Log::INFO);
            $this->logger->log("Query: " . $query, Zend_Log::DEBUG);
            $queryDao = new Dao_Query();

            if (strpos($query, 'sql#') === 0) {
                // to enable old config
                $query = substr($query, strlen('sql#'));
            }

            //check default query
            $isDefaultQuery = $queryDao->isQueryDefaultQuery($scriptName);
            $this->logger->log("IsDefaultQuery: " . $isDefaultQuery, Zend_Log::INFO);

            if ($isDefaultQuery == 1){
              $this->logger->log("Executing new logic! ", Zend_Log::INFO);
              $result = $queryDao->executeDefaultQuery($scriptName,$query, $parameter);
            }else {
               $this->logger->log("Executing old logic! ", Zend_Log::INFO);
               $result = $queryDao->executeQuery($query, $parameter);
            }
            // update status
            $queryDao->updateQueryStatus($queryId, 1);

            // TODO:
            if ($groupCi) {
                // TODO: iterate resultset -> subres
                // assume: ciid and citypeid
                $newRes = array();

                $currResGroup = array();
                foreach ($result as $res) {
                    if (empty($currResGroup)) {
                        $currResGroup['id']         = $res['id'];
                        $currResGroup['ci_type_id'] = $res['ci_type_id'];
                        $currResGroup['attributes'] = array();
                    } else if ($res['id'] != $currResGroup['id']) {
                        array_push($newRes, $currResGroup);
                        $currResGroup               = array();
                        $currResGroup['id']         = $res['id'];
                        $currResGroup['ci_type_id'] = $res['ci_type_id'];
                        $currResGroup['attributes'] = array();
                    }

                    unset($res['id']);
                    unset($res['ci_type_id']);
                    array_push($currResGroup['attributes'], $res);
                }
                array_push($newRes, $currResGroup);
                $result = $newRes;
            }


            $notification           = array();
            $notification['status'] = 'OK';
            $notification['data']   = $result;
            $notification           = parent::getReturnValue($notification);

            if ($method && $method == 'plain') {
                echo $notification;
                exit;
            }

            $code = 200;
            $this->logger->log($notification, Zend_Log::DEBUG);
            $this->getResponse()
                ->setHttpResponseCode($code)
                ->appendBody($notification);
        } catch (Exception $e) {
            $this->logger->log(sprintf('Webservice failed: %s', $scriptName), Zend_Log::CRIT);
            $this->logger->log(sprintf('Query: %s', $query), Zend_Log::CRIT);
            $this->logger->log($e, Zend_Log::CRIT);
            $notification = array('status' => 'error', 'message' => 'unexpected Error occurred.');
            $notification = parent::getReturnValue($notification);

            $this->getResponse()
                ->setHttpResponseCode(500)
                ->appendBody($notification);
        }
    }

    private function executeWorkflow()
    {

        $apiKey       = $this->_getParam('apikey');
        $workflowName = $this->_getParam('workflow');
        $debugEnabled = $this->_getParam('debug');
        $method       = $this->_getParam('method');
        $startAt      = $this->_getParam('startAt');
        $triggerType  = $this->_getParam('triggerType');

        if ($triggerType !== 'api' && $triggerType !== 'webhook') {
            $triggerType = 'api';
        }

        $utilWorkflow = new Util_Workflow($this->logger);
        $daoWorkflow  = new Dao_Workflow();

        $workflow   = $daoWorkflow->getWorkflowByName($workflowName);
        $workflowId = $workflow[Db_Workflow::ID];

        if (empty($workflowId)) {
            $workflowInfo = parent::getApiResponse(false, "Workflow not found", null, $method);
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->appendBody($workflowInfo);

            return;
        }

        $user   = $this->getUserInformation();
        $userId = '0';
        if (isset($user[Db_User::ID])) {
            $userId = $user[Db_User::ID];
        }

        // generate new api key for users with active session
        if (empty($apiKey)) {
            $apiKey = $utilWorkflow->getApikey($userId);
        }

        // catch all params for debugging and replay of request
        $allParams = array(
            'apikey'   => $apiKey,
            'workflow' => $workflowName,
            'debug'    => $debugEnabled,
            'method'   => $method,
            'startAt'  => $startAt,
        );

        // actual workflow script parameters
        $workflowParams = array('triggerType' => $triggerType);

        foreach ($this->getAllParams() as $key => $value) {
            if ($key !== "Environment") { // do not allow to change system environment
                // add all params for debugging purposes
                $allParams[$key]      = $value;

                // add actual workflow script parameters, but only for new workflows with json parameters
                if($workflow[Db_Workflow::RESPONSE_FORMAT] == 'json') {
                    $workflowParams[$key] = $value;
                }

                // backwards compatibility for legacy and plain parameter workflows
                if (stripos(trim($key), "argv") === 0) {
                    $workflowParams[':' . $key . ':'] = $value;
                }
            }
        }
        ksort($workflowParams);

        if (isset($debugEnabled)) {
            $debugEnabled = intval($debugEnabled) === 1;
        } else {
            $debugEnabled = false;
        }

        if (isset($startAt)) {
            $startAt    = new DateTime($startAt);
            $forceAsync = true;
        } else {
            $startAt    = new DateTime();
            $forceAsync = false;
        }
        $util         = new Util_Workflow($this->logger);
        $started      = new DateTime();
        $workflowInfo = $util->startWorkflow($workflowId, $userId, $workflowParams, false, $forceAsync, $startAt);
        if (isset($workflowInfo['instance_finished'])) {
            $finished = new DateTime($workflowInfo['instance_finished']);
            unset($workflowInfo['instance_finished']);
        } else {
            $finished = new DateTime();
        }
        if (isset($workflowInfo['instance_created'])) {
            $started = new DateTime($workflowInfo['instance_created']);
            unset($workflowInfo['instance_created']);
        }

        $duration = $finished->getTimestamp() - $started->getTimestamp();

        $started  = $started->format("Y-m-d H:i:s");
        $finished = $finished->format("Y-m-d H:i:s");

        $workflowInfo['started']  = $started;
        $workflowInfo['finished'] = $finished;
        $workflowInfo['duration'] = $duration;

        if ($debugEnabled) {
            $debug        = array();
            $debug['url'] = APPLICATION_URL . "api/adapter";
            foreach ($allParams as $param_name => $param_value) {
                $debug['url'] .= "/" . $param_name . "/" . $param_value;
            }
            $debug['parameters']                   = $allParams;
            $workflowInfo['debug']                 = array();
            $workflowInfo['debug']['workflow_log'] = $workflowInfo['log'];
        } else {
            $debug = null;
        }

        unset($workflowInfo['log']);

        $success = !($workflowInfo['status'] == Util_Workflow::STATUS_FAILED);

        if ($success) {
            $message = "Workflow successful";
        } else {
            $message = "Workflow failed";
        }


        $workflowInfo = parent::getApiResponse($success, $message, $workflowInfo, $method, $debug);
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->appendBody($workflowInfo);
    }


    /**
     * Start attribute executable script via API
     *
     * The following options can be passed per http header or post
     *      executable_attribute_name   -> name of attribute
     *      ciid                        -> CI-ID
     *      apikey                      -> optional - existing api key
     */
    private function executeExecutable()
    {
        $apiKey        = $this->_getParam('apikey');
        $attributeName = $this->_getParam('executable_attribute_name');
        $ciId          = $this->_getParam('ciid');

        $daoAttribute   = new Dao_Attribute();
        $daoAuth        = new Dao_Authentication();
        $utilExecutable = new Util_Executable($this->logger);

        $user        = null;
        $sessionUser = $this->getUserInformation();
        if ($sessionUser !== false) {
            $user = new Dto_UserDto($sessionUser);
        } elseif (!empty($apiKey)) {
            $userRow = $daoAuth->getApiSessionUser($apiKey);
            $user    = new Dto_UserDto($userRow);
        }

        $attribute = $daoAttribute->getAttributeByNameAll($attributeName);

        if ($attribute === false) {
            $this->outputError('Attribute could not be resolved');
            return;
        }

        Util_AttributeType_Type_Executeable::insertMissingAttributes($ciId);
        $result = $utilExecutable->startExecutable($ciId, $attribute[Db_Attribute::ID], null, $user, 'api');

        if (isset($result['notification']['success'])) {
            $this->outputContent('Executable successful', array('output' => $result['last_line']));
        } elseif (isset($result['notification']['error'])) {
            $this->outputError($result['notification']['error']);
        } else {
            $this->outputError('no response from workflow');
        }
    }
}