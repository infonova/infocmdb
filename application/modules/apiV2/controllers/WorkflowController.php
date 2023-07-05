<?php

require_once 'V2BaseController.php';

class ApiV2_WorkflowController extends V2BaseController
{

    /**
     * @OA\Post(
     *     path="/workflow/{name}",
     *     tags={"workflow"},
     *     summary="Execute workflow",
     *     description="Execute a workflow",
     *     operationId="execute",
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         description="name of workflow to execute",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  required={},
     *                  @OA\Property(
     *                      type="object",
     *                      property="params",
     *                      description="workflow parameters",
     *                      example={ "ci_id": 12 }
     *                  ),
     *                  @OA\Property(
     *                      type="string",
     *                      property="startAt",
     *                      description="start date/time for async workflows in ISO 8601 format",
     *                      example="2004-02-12T15:19:21+00:00"
     *                  ),
     *                  @OA\Property(
     *                      type="bool",
     *                      property="forceAsync",
     *                      description="start the workflow always async. _false_ can't override async workflows to be run sync",
     *                      example=false
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *          description="Workflow executed successfully (for synchronous workflows)",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Workflow executed successfully",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      description="Dynamic workflow response data",
     *                      example={"instance_id": "9727","status": "CLOSED","instance_created": "2020-01-20 13:48:18","instance_finished": "2020-01-20 13:48:18"}
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *         response=202,
     *          description="Workflow scheduled successfully (for asynchronous workflows)",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Workflow started or scheduled successfully",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      description="Dynamic workflow response data",
     *                      example={"status": "EXECUTION SCHEDULED"}
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Invalid request",
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
     *                      example="Executing workflow failed",
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
     *          description="Workflow with name does not exist",
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
     *                      example="Parameter ""startAt"" is not a valid ISO8601 string",
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
     *                      example="Workflow is inactive",
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
     *     },
     *     @OA\Response(
     *          response=500,
     *          description="Workflow failed or failed to start",
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
     *                      example="Executing workflow failed",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example=null,
     *                  ),
     *              ),
     *          ),
     *     ),
     * )
     */
    /**
     * @throws Zend_Controller_Response_Exception
     * @throws Zend_Log_Exception
     */
    public function putAction()
    {
        $workflowName = $this->getParam('id', '');
        $workflowData = $this->jsonData;
        $userId    = $this->getUserInformation()->getId();

        $workflowDao = new Dao_Workflow();
        $workflowRow = $workflowDao->getWorkflowByName($workflowName);

        if ($workflowRow === false) {
            $this->outputHttpStatusNotFound();
            return;
        }

        if ($workflowRow[Db_Workflow::IS_ACTIVE] == 0) {
            $this->outputValidationError('Workflow is inactive');
            return;
        }

        $params            = (array)$workflowData->params;
        $startAt           = (string)$workflowData->startAt;
        $forceAsync        = (bool)$workflowData->forceAsync;
        $params['user_id'] = $userId;

        if ($startAt != "") {
            $startAt = date_create_from_format(DateTime::ATOM, $startAt);

            if ($startAt === false) {
                $this->outputBadRequestError('Parameter "startAt" is not a valid ISO8601 string');
                return;
            }
        }

        $this->logger->logf("apiV2 -  executing Workflow: %s", Zend_Log::INFO, $workflowName);

        try {
            $workflowService = new Util_Workflow($this->logger);
            $result = $workflowService->startWorkflow($workflowRow[Db_Workflow::ID], $userId, $params, false, $forceAsync, $startAt);
            unset($result['log']);
        } catch (Exception $e) {
            $this->logger->logf('Workflow failed: %s', Zend_Log::CRIT, $workflowName);
            $this->logger->log($e, Zend_Log::CRIT);
            $this->outputError('Executing workflow failed', null, 500);
            return;
        }

        if ($workflowRow[Db_Workflow::IS_ASYNC] == 1 || $forceAsync) {
            // The instanceId for async workflows is not created yet, remove null value from response
            unset($result['instance_id']);

            $this->outputContent('Workflow scheduled successfully', $result, 202);
        } else {
            $this->outputContent('Workflow executed successfully', $result, 200);
        }
    }
}
