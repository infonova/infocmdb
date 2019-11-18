<?php

require_once 'V2BaseController.php';

class ApiV2_QueryController extends V2BaseController
{

    /**
     * @OA\Put(
     *     path="/query",
     *     tags={"query"},
     *     summary="Execute query",
     *     description="Execute a predefined query managed in admin backend",
     *     operationId="execute",
     *     @OA\Parameter(
     *         name="execute",
     *         in="query",
     *         description="name of query",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *          required=false,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  required={},
     *                  @OA\Property(
     *                      type="object",
     *                      property="query",
     *                      description="query data",
     *                      @OA\Property(
     *                          type="object",
     *                          property="params",
     *                          description="query parameters (key=name of parameter, value=value of parameter)",
     *                          example="{ ""ci_id"": 12 }",
     *                      ),
     *                  ),
     *              )
     *          ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *          description="CI saved successfully",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Query executed successfully",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example="[ { ""ci_id"": 1123 }, { ""ci_id"": 1124 } ]",
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Query failed",
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
     *                      example="Executing query failed",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Query with name does not exist",
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
     *                      example="Not Found",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=422,
     *          description="Validation failed",
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
     *                      example="Query is inactive",
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
    public function putAction()
    {
        $queryName = $this->getParam('execute', '');
        $queryData = $this->getJsonParam('query', array());
        $userId    = $this->getUserInformation()->getId();

        $queryDao = new Dao_Query();
        $queryRow = $queryDao->getQueryByName($queryName);

        if ($queryRow === false) {
            $this->outputHttpStatusNotFound();
            return;
        }

        if ($queryRow[Db_StoredQuery::IS_ACTIVE] == 0) {
            $this->outputValidationError('Query is inactive');
            return;
        }

        $query = $queryRow[Db_StoredQuery::QUERY];
        $query = trim($query);

        if ($query === '') {
            $this->outputValidationError('Query is empty');
            return;
        }

        $this->logger->logf("apiV2 -  executing Webservice: %s", Zend_Log::INFO, $queryName);
        $this->logger->logf("apiV2 - Query: %s", Zend_Log::DEBUG, $query);

        try {
            $params            = (array)$queryData->params;
            $params['user_id'] = $userId;

            $result = $queryDao->executeQuery($query, $params, $generatedQuery);
        } catch (Exception $e) {
            $this->logger->logf('Webservice failed: %s', Zend_Log::CRIT, $queryName);
            $this->logger->logf('Query: %s', Zend_Log::CRIT, $generatedQuery);
            $this->logger->log($e, Zend_Log::CRIT);
            $queryDao->updateQueryStatus($queryRow[Db_StoredQuery::ID], 0);
            $this->outputError('Executing query failed', null, 400);
            return;
        }

        $queryDao->updateQueryStatus($queryRow[Db_StoredQuery::ID], 1);
        $this->outputContent('Query executed successfully', $result);
    }
}