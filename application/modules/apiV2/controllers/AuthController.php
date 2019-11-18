<?php /** @noinspection SpellCheckingInspection */

require_once 'V2BaseController.php';

class ApiV2_AuthController extends V2BaseController
{
    /**
     * @OA\Post(
     *     path="/auth/token",
     *     tags={"auth"},
     *     summary="Login and receive a token",
     *     description="Login with username + password and reseive jwt token as response",
     *     operationId="token",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/x-www-form-urlencoded",
     *              @OA\Schema(
     *                  type="object",
     *                  required={
     *                      "username",
     *                      "password",
     *                  },
     *                  @OA\Property(
     *                      type="string",
     *                      property="username",
     *                      description="api username",
     *                  ),
     *                  @OA\Property(
     *                      type="string",
     *                      property="password",
     *                      description="api password",
     *                  ),
     *                  @OA\Property(
     *                      type="integer",
     *                      property="lifetime",
     *                      description="lifetime of token in seconds",
     *                  ),
     *              )
     *          ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *          description="Authentication successful",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Authentication successful",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      @OA\Property(
     *                          property="token",
     *                          type="string",
     *                          description="jwt token",
     *                          example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6InFCQjdlVTgxd0NsdG5ZWXB5d2NcL21yYVUrYlByWDJ6RXhDU05mdkFOMmJYNFBpS211VGlnRG9SMXcyMWwifQ.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3RcLyIsImp0aSI6InFCQjdlVTgxd0NsdG5ZWXB5d2NcL21yYVUrYlByWDJ6RXhDU05mdkFOMmJYNFBpS211VGlnRG9SMXcyMWwiLCJpYXQiOjE1NTMwODQ0NzUsImV4cCI6MTU1MzA4ODA3NSwidXNlcl9pZCI6MSwiaXBfYWRkcmVzcyI6IjE3Mi4yMS4wLjEiLCJzb3VyY2UiOiJhcGkifQ.c7qD1zzr-37KwQz12WSPcpoFYfpgf4FMAnG0mCtFeoc",
     *                      ),
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=403,
     *          description="Authentication failed",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="success",
     *                      type="boolean",
     *                      example=false,
     *                  ),
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Authentication failed",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     security={}
     * )
     */
    /**
     * @throws Zend_Controller_Response_Exception
     * @throws Zend_Log_Exception
     */
    public function tokenAction()
    {
        $username = $this->getParam('username', '');
        $password = $this->getParam('password', '');
        $lifeTime = $this->getParam('lifetime', 0);

        $daoAuth = new Dao_Authentication();

        $authAttempt = $daoAuth->auth($username, $password);
        /** @var Zend_Auth_Result $authResult */
        $authResult = $authAttempt['result'];
        $authUser   = $authAttempt['user'];

        if ($authResult->isValid()) {

            if ($authUser[Db_User::IS_TWO_FACTOR_AUTH] == 1) {
                $this->outputError("Authentication failed (2FA not supported)", null, 403);
                return;
            }

            $logMessage = sprintf(
                'apiV2 successful login with username "%s"',
                $authUser[Db_User::USERNAME]
            );
            $this->logger->log($logMessage, Zend_Log::INFO);

            $response = array(
                'token' => $daoAuth->getNewApiAuthToken($authUser, $lifeTime)->__toString()
            );
            $this->outputContent("Authentication successful", $response);
            return;
        }

        $logMessage = sprintf(
            'apiV2 login with username "%s" failed: %s',
            $username,
            implode(', ', $authResult->getMessages())
        );
        $this->logger->log($logMessage, Zend_Log::INFO);

        $this->outputError("Authentication failed", null, 403);
        return;
    }


    /**
     * @OA\Get(
     *     path="/auth/refresh",
     *     tags={"auth"},
     *     summary="Receive a new token using exting token",
     *     description="Use an extisting and valid token to receive a new token",
     *     operationId="refresh",
     *     @OA\Parameter(
     *         name="lifetime",
     *         in="query",
     *         description="lifetime of token in seconds",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *          description="Refresh successful",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Refresh successful",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      @OA\Property(
     *                          property="token",
     *                          type="string",
     *                          description="jwt token",
     *                          example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6InFCQjdlVTgxd0NsdG5ZWXB5d2NcL21yYVUrYlByWDJ6RXhDU05mdkFOMmJYNFBpS211VGlnRG9SMXcyMWwifQ.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3RcLyIsImp0aSI6InFCQjdlVTgxd0NsdG5ZWXB5d2NcL21yYVUrYlByWDJ6RXhDU05mdkFOMmJYNFBpS211VGlnRG9SMXcyMWwiLCJpYXQiOjE1NTMwODQ0NzUsImV4cCI6MTU1MzA4ODA3NSwidXNlcl9pZCI6MSwiaXBfYWRkcmVzcyI6IjE3Mi4yMS4wLjEiLCJzb3VyY2UiOiJhcGkifQ.c7qD1zzr-37KwQz12WSPcpoFYfpgf4FMAnG0mCtFeoc",
     *                      ),
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=403,
     *          description="Invalid token",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="success",
     *                      type="boolean",
     *                      example=false,
     *                  ),
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Forbidden",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     security={
     *         {"apiV2_auth": {}}
     *     }
     * )
     */
    /**
     * @throws Zend_Controller_Response_Exception
     * @throws Zend_Log_Exception
     */
    public function refreshAction()
    {
        $lifeTime = $this->getParam('lifetime', 0);

        $daoAuth = new Dao_Authentication();
        $user    = $this->user;

        $logMessage = sprintf(
            'apiV2 successful token refresh for user "%s".',
            $this->user[Db_User::USERNAME]
        );
        $this->logger->log($logMessage, Zend_Log::INFO);

        $response = array(
            'token' => $daoAuth->getNewApiAuthToken($user, $lifeTime)->__toString()
        );

        $this->outputContent("Refresh successful", $response);
        return;
    }
}