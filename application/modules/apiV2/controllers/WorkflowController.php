<?php

require_once 'V2BaseController.php';

class ApiV2_WorkflowController extends V2BaseController
{

    /**
     * @OA\Post(
     *     path="/workflow",
     *     tags={"workflow"},
     *     summary="Execute workflow",
     *     description="Execute a workflow",
     *     operationId="execute",
     *     @OA\Parameter(
     *         name="execute",
     *         in="query",
     *         description="name of workflow",
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
     *                      property="workflow",
     *                      description="workflow name",
     *                      @OA\Property(
     *                          type="object",
     *                          property="params",
     *                          description="workflow params",
     *                              example={ "ci_id": 12 }
     *                      ),
     *                      @OA\Property(
     *                          type="string",
     *                          property="startAt",
     *                          description="start at ISO8601",
 *                              example="2004-02-12T15:19:21+00:00"
     *                      ),
     *                      @OA\Property(
     *                          type="bool",
     *                          property="forceAsync",
     *                          description="start the workflow always async. _false_ can't override async workflows to be run sync",
 *                              example=false
     *                      ),
     *                  ),
     *              ),
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
     *                      example="Workflow executed successfully",
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
     *          description="Workflow failed",
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
     *     }
     * )
     */
    /**
     * @throws Zend_Controller_Response_Exception
     * @throws Zend_Log_Exception
     */
    public function postAction()
    {
        $workflowName = $this->getParam('execute', '');
        $workflowData = $this->getJsonParam('workflow', array());
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

        $this->logger->logf("apiV2 -  executing Workflow: %s", Zend_Log::INFO, $workflowName);

        try {
            $params            = (array)$workflowData->params;
            $startAt           = (string)$workflowData->startAt;
            $forceAsync        = (bool)$workflowData->forceAsync;
            $params['user_id'] = $userId;

            $workflowService = new Util_Workflow($this->logger);
            $result = $workflowService->startWorkflow($workflowRow[Db_Workflow::ID], $userId, $params, false, $forceAsync, $startAt);
            unset($result['log']);

            //TODO Return also the instance id
        } catch (Exception $e) {
            $this->logger->logf('Workflow failed: %s', Zend_Log::CRIT, $workflowName);
            $this->logger->log($e, Zend_Log::CRIT);
            $this->outputError('Executing workflow failed', null, 400);
            return;
        }

        $this->outputContent('Workflow executed successfully', $result);
    }
}
