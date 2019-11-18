<?php

require_once 'V2BaseController.php';

class ApiV2_FileuploadController extends V2BaseController
{
    /**
     * @OA\Post(
     *     path="/fileupload",
     *     tags={"fileupload"},
     *     summary="Upload files to the server",
     *     description="Upload a file to the server and receive an upload-Id for further handling. The request body contains the file content.",
     *     operationId="upload",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="mixed",
     *          ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *          description="Upload success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/Util_Response"),
     *                  },
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      example="Upload completed successfully",
     *                  ),
     *                  @OA\Property(
     *                      property="data",
     *                      type="object",
     *                      example="apiV2_fileupload_0e1f80ad4a1f23db5fd102bb220156edafce4464cf1e6b27d5c0b1fa3e8dc1d5",
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Request contains no file",
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
     *                      example="No file in request",
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
    public function insert()
    {
        $body = $this->getRequest()->getRawBody();

        $cryptUtil = new Util_Crypt();
        $fileHash  = "apiV2_fileupload_" . $cryptUtil->create_uniqid();

        if ($body == '') {
            $this->outputError('No file in request', null, 400);
        }

        try {
            $tmpFilePath = Util_FileUpload::getTmpUploadPath() . '/' . $fileHash;
        } catch (Zend_Config_Exception $e) {
            Bootstrap::logException($e);
            $this->outputError("Invalid fileupload configuration", null, 500);
            return;
        }

        if ($fp = fopen($tmpFilePath, "xb")) {
            fwrite($fp, $body);
        } else {
            $this->outputError("Failed to save - upload path not writeable");
            return;
        }

        $this->outputContent('Upload completed successfully', $fileHash);
    }


}