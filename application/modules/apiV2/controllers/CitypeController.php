<?php

require_once 'V2BaseController.php';

class ApiV2_CitypeController extends V2BaseController
{
    /**
     * @OA\Get(
     *     path="/citype/index",
     *     tags={"citype"},
     *     summary="get Citype List",
     *     description="get citype list",
     *     operationId="index",
     *     @OA\Response(
     *          response=404,
     *          description="Provided ciAttributeId does not exist",
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
     *     security={
     *         {"apiV2_auth": {}}
     *     }
     * )
     * @throws Zend_Controller_Response_Exception*@throws Zend_Session_Exception
     */
    public function list()
    {
        $user            = parent::getUserInformation();
        $ciTypeServiceGet    = new Service_Citype_Get($this->translator, $this->logger, $user->getThemeId());

        try {
            $ciTypeList = $ciTypeServiceGet->getCitypeList();
            $ciTypeData = array();
            foreach($ciTypeList['paginator'] as $ciType) {
                $ciTypeKey = $ciType[Db_CiType::NAME];
                $ciTypeData[$ciTypeKey] = $ciType;
                $ciTypeData[$ciTypeKey]['attributes'] = $ciTypeServiceGet->getAttributes($ciType[Db_CiType::ID]);

            }
            $this->outputContent("success", $ciTypeData, 200);
        } catch (Exception $e) {
            $this->outputError("unknown error");
        }
    }

}
