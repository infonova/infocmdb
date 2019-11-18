<?php
require_once 'BaseController.php';

class Api_LoginController extends BaseController
{


    public function getAction()
    {
        $username = $this->_getParam('username');
        $password = $this->_getParam('password');
        $method   = $this->_getParam('method'); //xml. json, plain
        $timeout  = $this->_getParam('timeout'); // expiry in seconds

        if (!$username || !$password) {
            $this->helpLogin();
        }

        $userDaoImpl   = new Dao_User();
        $authInterface = new Dao_Authentication();

        try {

            $options = $this->getInvokeArg('bootstrap')->getOptions();

            if (!$timeout) {
                $timeout = $options['auth']['login']['timeout'];
            }

            // get user row
            $userInformation = $userDaoImpl->getUserByUsername($username);

            // users with 2FA not allowed to login
            if ($userInformation[Db_User::IS_TWO_FACTOR_AUTH] == 1) {
                throw new Exception_AccessDenied("2FA not supported");
            }

            // prepare auth adapter
            $auth = $authInterface->authDatabase($userInformation, $password);

            // check credentials
            $token = $auth->authenticate();
            if ($token->isValid() === true) {

                // generate api key
                $apikey = $authInterface->setApiSession($userInformation[Db_User::ID], $timeout);

                $apiKey           = array();
                $apiKey['status'] = 'OK';
                $apiKey['apikey'] = $apikey;
                $message          = parent::getReturnValue($apiKey);

                if ($method && $method == 'plain') {
                    echo $apikey;
                    exit;
                }

                $this->getResponse()
                    ->setHttpResponseCode(202)
                    ->appendBody($message);

            } else {
                $apiKey            = array();
                $apiKey['status']  = 'error';
                $apiKey['message'] = 'Login failed. Credentials invalid!';
                $message           = parent::getReturnValue($apiKey);

                if ($method && $method == 'plain') {
                    echo 'Login failed. Credentials invalid!';
                    exit;
                }

                $this->getResponse()
                    ->setHttpResponseCode(403)
                    ->appendBody($message);
            }


        } catch (Exception_AccessDenied $e) {
            $this->logger->log('API-Login with user "' . $username . '" failed: ' . $e->getMessage(), Zend_Log::INFO);
            parent::forbidden();
        } catch (Zend_Auth_Adapter_Exception $e) {
            $this->logger->log('Invalid Login for username "' . $username . '" with error messsage ' . $e, Zend_Log::WARN);

            $apiKey            = array();
            $apiKey['status']  = 'error';
            $apiKey['message'] = 'Login failed. Invalid Username "' . $username . '" or password.';
            $message           = parent::getReturnValue($apiKey);

            $this->getResponse()
                ->setHttpResponseCode(403)
                ->appendBody($message);

        } catch (Exception $e) {
            $this->logger->log('Unexpected exception in login process for username "' . $username . '" with password "' . $password . '" and error message ' . $e, Zend_Log::WARN);
            $apiKey            = array();
            $apiKey['status']  = 'error';
            $apiKey['message'] = 'Login failed.';
            $message           = parent::getReturnValue($apiKey);

            $this->getResponse()
                ->setHttpResponseCode(403)
                ->appendBody($message);
        }
    }


    private function helpLogin()
    {
        $apiKey            = array();
        $apiKey['status']  = 'error';
        $apiKey['message'] = 'Login failed. No Username or password was provided. Please use the following example to provide valid login information.';
        $apiKey['example'] = 'application.com/api/login/username/testuser/password/testpw/';
        $message           = parent::getReturnValue($apiKey);

        $this->getResponse()
            ->setHttpResponseCode(403)
            ->appendBody($message)
            ->sendResponse();
        exit;
    }

}