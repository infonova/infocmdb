<?php

class Dao_Authentication extends Dao_Abstract
{

    const SECURITY_RISK_LOW  = 0;
    const SECURITY_RISK_HIGH = 3;

    /**
     * Check Credentials of user
     *
     * @param string $username
     * @param string $password
     * @return array Array with key 'result' (Zend_Auth_Result) and 'user' (array with user-db-row)
     * @throws Zend_Auth_Adapter_Exception
     */
    public function auth($username, $password)
    {
        $userDaoImpl     = new Dao_User();
        $userInformation = $userDaoImpl->getUserByUsername($username);
        $result          = array(
            'result' => new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE,
                $username,
                array('Auth could not be completed.')
            ),
            'user'   => $userInformation
        );

        if ($userInformation === false) {
            $result['result'] = new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                $username,
                array('A record with the supplied identity could not be found.')
            );

            return $result;
        }

        if (!$userDaoImpl->isLoginAllowed($userInformation[Db_User::ID], $userInformation)) {
            $result['result'] = new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                $username,
                array('The supplied identity is not allowed to login.')
            );

            return $result;
        }

        if ($userInformation[Db_User::IS_LDAP_AUTH] === '1') {
            $authAdapter = $this->authLdap($username, $password);
        } else {
            $authAdapter = $this->authDatabase($userInformation, $password);
        }

        $result['result'] = $authAdapter->authenticate();

        return $result;
    }


    /**
     * Check Credentials of user (check against db)
     *
     * @param array  $userInformation user-db-row
     * @param string $password        password to check
     * @return Zend_Auth_Adapter_DbTable
     * @throws Zend_Config_Exception
     */
    public function authDatabase($userInformation, $password)
    {
        $config   = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        $username = $userInformation[Db_User::USERNAME];

        if ($config->auth->password->encryption == 1) {
            $password_hash            = $userInformation[Db_User::PASSWORD];
            $crypt                    = new Util_Crypt();
            $stored_password_ishashed = preg_match('/\d+:\d+:(.+?):(.+?)/', $password_hash);
            $given_password_ishashed  = preg_match('/\d+:\d+:(.+?):(.+?)/', $password);

            if ($given_password_ishashed !== 0) {
                $password = '';
            } else {
                if (
                    $stored_password_ishashed === 0
                    && (string)$password === (string)$password_hash) {
                    /**
                     * Encrypt unencrypted passwords
                     * updateUserPassword does this by default now
                     */
                    $userDaoImpl = new Dao_User();
                    $password    = $crypt->create_hash($password); // hash here so we have the password for Zend Auth
                    $userDaoImpl->updateUserPassword($userInformation[Db_User::ID], $password, 0);
                } elseif (
                    $stored_password_ishashed !== 0
                    && $crypt->validate_password($password, $password_hash)) {
                    $password = $password_hash;
                }
            }
        }

        // Die Instanz mit Konstruktor Parametern konfiurieren...
        $authAdapter = new Zend_Auth_Adapter_DbTable(
            $this->db,
            Db_User::TABLE_NAME,
            Db_User::USERNAME,
            Db_User::PASSWORD,
            Db_User::IS_ACTIVE . ' ="1"'
        );

        // set the credentials
        $authAdapter->setIdentity($username);
        $authAdapter->setCredential($password);

        return $authAdapter;
    }

    /**
     * Check Credentials of user (check against ldap)
     *
     * @param string $username
     * @param string $password
     * @return Zend_Auth_Adapter_Ldap
     * @throws Zend_Config_Exception
     */
    public function authLdap($username, $password)
    {
        $config  = new Zend_Config_Ini('../application/configs/ldap.ini', APPLICATION_ENV);
        $options = $config->ldap->toArray();

        // use Zend_Auth_Adapter_Ldap
        $authAdapter = new Zend_Auth_Adapter_Ldap(
            $options,
            $username,
            $password
        //	'MD5(CONCAT('$salt',?)) AND '.Db_User::IS_ACTIVE.' = "1"' // TODO: activate later
        );

        return $authAdapter;
    }

    public function setLastLogin($username)
    {
        $users = new Db_User();
        $data  = array('last_access' => date('Y-m-d H:i:s'));
        $where = $users->getAdapter()->quoteInto('username = ?', $username);

        if (!$users->update($data, $where)) {
            // TODO: throw new Zend_Exception('Error on update last_access: '.);
        }
    }

    public function setApiSession($userId, $timeout, $retry = 0)
    {
        $session = new Db_ApiSession();

        $valid     = time() + $timeout;
        $utilCrypt = new Util_Crypt();

        //generate api-key
        $apikeyLength = 30;
        $apikey       = substr(md5($utilCrypt->create_salt(100)), 0, $apikeyLength);


        //save to database
        $data = array(Db_ApiSession::APIKEY   => $apikey,
                      Db_ApiSession::USER_ID  => $userId,
                      Db_ApiSession::VALID_TO => $valid);
        try {
            $session->insert($data);
        } catch (Zend_Db_Exception $e) {
            if (strstr($e->getMessage(), '1062 Duplicate') && $retry < 1) {
                return $this->setApiSession($userId, $timeout, 1);
            } else {
                throw new Exception ($e->getMessage(), $e->getCode(), $e);
            }
        }
        return $apikey;
    }

    public function getApiSession($apiKey)
    {
        $select = $this->db->select()
            ->from(Db_ApiSession::TABLE_NAME)
            ->where(Db_ApiSession::APIKEY . ' =?', $apiKey);

        return $this->db->fetchRow($select);
    }

    /**
     * Get api_session row by jwt token
     *
     * @param string $tokenString jti of jwt token
     * @return bool|array returns false if no row was found otherwise array with api_session-db-row
     */
    public function getApiSessionByToken($tokenString)
    {
        $tokenParser = new \Lcobucci\JWT\Parser();
        $token       = $tokenParser->parse($tokenString);
        $tokenJti    = $token->getClaim('jti');
        $user        = $this->getApiSession($tokenJti);

        return $user;
    }

    public function getApiSessionUser($apiKey)
    {
        $select = $this->db->select()
            ->from(Db_ApiSession::TABLE_NAME, array())
            ->join(Db_User::TABLE_NAME, Db_User::TABLE_NAME . '.' . Db_User::ID . ' = ' . Db_ApiSession::TABLE_NAME . '.' . Db_ApiSession::USER_ID)
            ->where(Db_ApiSession::TABLE_NAME . '.' . Db_ApiSession::APIKEY . ' =?', $apiKey);
        return $this->db->fetchRow($select);
    }

    /**
     * Get db-user-row by jwt token
     *
     * @param string $tokenString jti of jwt token
     * @return bool|array returns false if no row was found otherwise array with user-db-row
     */
    public function getApiSessionUserByToken($tokenString)
    {
        $tokenParser = new \Lcobucci\JWT\Parser();
        $token       = $tokenParser->parse($tokenString);
        $tokenJti    = $token->getClaim('jti');
        $user        = $this->getApiSessionUser($tokenJti);

        return $user;
    }

    public function findPrivilegesByResourceId($resourceId)
    {
        $select = $this->db->select()
            ->from(Db_ThemePrivilege::TABLE_NAME)
            ->where(Db_ThemePrivilege::RESOURCE_ID . ' = ?', $resourceId);

        return $this->db->fetchAll($select);
    }


    public function insertPasswordReset($data)
    {
        $table = new Db_PasswordReset();
        return $table->insert($data);
    }

    public function getActivePasswordResetByHash($hash)
    {
        $select = $this->db->select()
            ->from(Db_PasswordReset::TABLE_NAME)
            ->where(Db_PasswordReset::HASH . ' = ?', $hash)
            ->where(Db_PasswordReset::VALID_TO . ' >= now()');

        return $this->db->fetchRow($select);
    }

    /** get count of all requests for user = $userId where valid_to >= now()
     *
     * @param int $userId
     *
     * @return row
     */
    public function getCountRecentPasswordResetsByUserId($userId)
    {
        $select = $this->db->select()
            ->from(Db_PasswordReset::TABLE_NAME, array("count(" . Db_PasswordReset::ID . ") as cnt"))
            ->where(Db_PasswordReset::USER_ID . ' = ?', $userId)
            ->where(Db_PasswordReset::VALID_TO . ' >= now()');
        return $this->db->fetchRow($select);
    }

    public function deletePasswordResetByUser($userId)
    {
        $table = new Db_PasswordReset();
        $where = $table->getAdapter()->quoteInto(Db_PasswordReset::USER_ID . ' = ?', $userId);
        return $table->delete($where);
    }

    /** set is_valid to false, important too keep the db entry for enforcing limitations set in login.ini
     *
     * @param int $resetId
     *
     * @return type
     */
    public function setPassswordResetInvalid($resetId)
    {
        $table = new Db_PasswordReset();
        $data  = array(Db_PasswordReset::IS_VALID => false);
        $where = $table->getAdapter()->quoteInto(Db_PasswordReset::ID . " = ?", $resetId);

        return $table->update($data, $where);
    }


    /** get count of all requests where clientKey = $clientKey and valid_to is >= now()
     *
     * @param string $clientKey
     *
     * @return row
     */
    public function getActiveRequestCountByClientKey($clientKey)
    {
        $select = $this->db->select()
            ->from(Db_PasswordReset::TABLE_NAME, array("count(" . Db_PasswordReset::ID . ") as cnt"))
            ->where(Db_PasswordReset::CLIENT_KEY . "=?", $clientKey)
            ->where(Db_PasswordReset::VALID_TO . ' >= now()')
            ->group(Db_PasswordReset::CLIENT_KEY);

        return $this->db->fetchRow($select);
    }

    /** get count of all requests that happend within an hour
     *
     * @return row
     */
    public function getCountRequestsWithinHour()
    {
        $select = $this->db->select()
            ->from(Db_PasswordReset::TABLE_NAME, array("count(" . Db_PasswordReset::ID . ") as cnt"))
            ->where("DATE_ADD(" . Db_PasswordReset::CREATED . ", INTERVAL 1 HOUR) >= now()")    // add 1 hour to the created timestamp
        ;

        return $this->db->fetchRow($select);
    }

    /**
     * Generates a new user based jwt token
     *
     * @param array   $user             db-user-row
     * @param integer $secondsLifeTime  lifetime of token in seconds (0 will set default lifetime)
     * @param array   $additionalClaims associative array with additional claims
     * @return \Lcobucci\JWT\Token
     */
    protected function getNewToken($user, $secondsLifeTime = 0, $additionalClaims = array())
    {
        $crypt       = new Util_Crypt();
        $daoUser     = new Dao_User();
        $algorithm   = new \Lcobucci\JWT\Signer\Hmac\Sha256();
        $loginConfig = new Util_Config('login.ini', APPLICATION_ENV);

        $maxLifeTime   = $loginConfig->getValue('login.apiV2.tokenMaxLifeTime', 86400);
        $defaultClaims = array(
            'user_id'    => (integer)$user[Db_User::ID],
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        );
        $claims        = array_merge($defaultClaims, $additionalClaims);

        if (empty($secondsLifeTime)) {
            $secondsLifeTime = $loginConfig->getValue('login.apiV2.tokenDefaultLifeTime', 3600);
        }

        if ($secondsLifeTime > $maxLifeTime) {
            $secondsLifeTime = $maxLifeTime;
        }

        $secret = $daoUser->provideApiSecret($user[Db_User::ID], $user);

        $tokenBuilder = $crypt->getJwtBuilder($secondsLifeTime, $claims);
        $tokenBuilder->sign($algorithm, $secret);
        $token = $tokenBuilder->getToken();

        $data = array(
            Db_ApiSession::APIKEY      => $token->getClaim('jti'),
            Db_ApiSession::USER_ID     => $user[Db_User::ID],
            Db_ApiSession::VALID_TO    => $token->getClaim('exp'),
            Db_ApiSession::API_VERSION => 2,
        );

        $session = new Db_ApiSession();
        $session->insert($data);

        return $token;
    }

    /**
     * Generates a new jwt token with source "workflow"
     *
     * @param array   $user               db-user-row
     * @param integer $workflowId         id of workflow
     * @param integer $workflowInstanceId id of workflow execution
     * @return \Lcobucci\JWT\Token
     */
    public function getNewWorkflowToken($user, $workflowId, $workflowInstanceId = null)
    {
        $addClaims = array(
            'source'               => 'workflow',
            'workflow_id'          => $workflowId,
            'workflow_instance_id' => $workflowInstanceId,
        );

        return $this->getNewToken($user, 0, $addClaims);
    }

    /**
     * Generates a new jwt token with source "api"
     *
     * @param array   $user            db-user-row
     * @param integer $secondsLifeTime lifetime of token in seconds (0 will set default lifetime)
     * @return \Lcobucci\JWT\Token
     */
    public function getNewApiAuthToken($user, $secondsLifeTime = 0)
    {
        $addClaims = array(
            'source' => 'api',
        );

        return $this->getNewToken($user, $secondsLifeTime, $addClaims);
    }

    /**
     * Check if token is valid
     *
     * @param string  $tokenString   jwt token
     * @param string  $reason        if provided the reason will be described in a message
     * @param integer $securityLevel if provided a security risk will be described (higher is more risky)
     * @return bool true if token is valid
     */
    public function isTokenValid($tokenString, &$reason = '', &$securityLevel = -1)
    {
        $tokenParser = new \Lcobucci\JWT\Parser();
        $validation  = new \Lcobucci\JWT\ValidationData();
        $validation->setIssuer(APPLICATION_URL);

        // 1 - Can the token be parsed
        try {
            $token = $tokenParser->parse($tokenString);
        } catch (InvalidArgumentException $e) {
            $reason        = 'Token could not be parsed - ' . $tokenString;
            $securityLevel = self::SECURITY_RISK_HIGH;
            return false;
        }

        $claims     = $token->getClaims();
        $claimsData = json_encode($claims);
        // 2 - Is token valid in general (Issuer, Expiry Date, ...)
        if ($token->validate($validation) === false) {
            $reason        = 'Token is invalid - ' . $claimsData;
            $securityLevel = self::SECURITY_RISK_LOW;
            return false;
        }

        // 3 - Does IP address in token match with requester IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        if($ipAddress !== $token->getClaim('ip_address')) {
            $reason        = 'Token IP address does not match client IP address (' . $ipAddress . ') - ' . $claimsData;
            $securityLevel = self::SECURITY_RISK_HIGH;
            return false;
        }

        // 4 - Is token still in database
        $apiSession = $this->getApiSessionByToken($tokenString);
        if (!is_array($apiSession)) {
            $reason        = 'Token not found in database - ' . $claimsData;
            $securityLevel = self::SECURITY_RISK_LOW;
            return false;
        }

        // 5 - Is user blocked
        $daoUser = new Dao_User();
        if ($daoUser->isLoginAllowed($apiSession[Db_ApiSession::USER_ID]) === false) {
            $reason        = 'User is not allowed to login - ' . $claimsData;
            $securityLevel = self::SECURITY_RISK_LOW;
            return false;
        }

        // 6 - Does api_secret match with signed token
        $algorithm = new \Lcobucci\JWT\Signer\Hmac\Sha256();
        $secret    = $daoUser->provideApiSecret($apiSession[Db_ApiSession::USER_ID]);
        if ($token->verify($algorithm, $secret) === false) {
            $reason        = 'Token signature can not be verified - ' . $claimsData;
            $securityLevel = self::SECURITY_RISK_HIGH;
            return false;
        }

        $reason = 'Token passed all checks - ' . $claimsData;
        return true;
    }

}