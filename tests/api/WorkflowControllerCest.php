<?php

/**
 * @group workflow
 */
class WorkflowControllerCest extends AbstractApiTest
{

    public function _before(ApiTester $I, $scenario)
    {

    }

    // Test if Workflows with a lot of outputs and stderror output
    // cause the workflow to get stuck.
    public function callLongRunningWorkflowWithErrors(ApiTester $I) {
        $apiKey = 'query_' . date('YmdHis');
        $I->insertApiKey($apiKey);

        $I->sendPOST('/api/adapter/apikey/'.$apiKey.'/workflow/test_long_output_workflow/method/json', array());
        $I->seeResponseCodeIs(200);
    }
}
