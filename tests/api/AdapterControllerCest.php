<?php


class AdapterControllerCest extends AbstractApiTest
{
    public function _before(ApiTester $I)
    {

    }

    public function queryWithInvalidApiKey(ApiTester $I) {
        $apiKey = 'INVALID_1234';
        $userId = 0;
        $note = sprintf('created by AdapterControllerCest (%s)', $apiKey);

        $I->sendPOST('/api/adapter/query/int_createHistory', array(
            'apikey'     => $apiKey,
            'method'    => 'json',
            'argv1'     => $userId,
            'argv2'     => $note,
        ));

        $I->dontSeeInDatabase('history', array(
            'user_id' => $userId,
            'note' => $note
        ));

        $I->dontSeeResponseContains('data');
        $I->seeResponseCodeIs(403);
    }

    public function queryWithPreExistingApiKey(ApiTester $I) {
        $apiKey = 'query_' . date('YmdHis');
        $I->insertApiKey($apiKey);

        $userId = 0;
        $note = sprintf('created by AdapterControllerCest (%s)', $apiKey);

        $I->sendPOST('/api/adapter/query/int_createHistory', array(
            'apikey'     => $apiKey,
            'method'    => 'json',
            'argv1'     => $userId,
            'argv2'     => $note,
        ));

        $I->seeInDatabase('history', array(
            'user_id' => $userId,
            'note' => $note
        ));

        $I->seeResponseContainsJson(array(
            'status' => 'OK'
        ));
        $I->seeResponseContains('data');
        $I->seeResponseCodeIs(200);
    }

    public function queryWithAuthenticatedUser(ApiTester $I) {
        $I->loggingInViaGui();

        $userId = 0;
        $note = 'created by AdapterControllerCest (authenticated user)';

        $I->sendPOST('/api/adapter/query/int_createHistory', array(
            'method'    => 'json',
            'argv1'     => $userId,
            'argv2'     => $note,
        ));

        $I->seeInDatabase('history', array(
            'user_id' => $userId,
            'note' => $note
        ));

        $I->seeResponseContainsJson(array(
            'status' => 'OK'
        ));
        $I->seeResponseContains('data');
        $I->seeResponseCodeIs(200);

        $I->wantTo('Ensure the user is still logged in');
        $I->sendGET('/index/index');
        $I->seeResponseContains('nav_project_list_dropdown');
        $I->dontSeeResponseContains('login_form');
    }

    public function executableWithInvalidApiKey(ApiTester $I) {
        $apiKey = 'INVALID_1234';

        $I->sendPOST('/api/adapter/executable_attribute_name/general_regular_executable', array(
            'apikey'     => $apiKey,
            'method'    => 'json',
            'ciid'      => 1,
        ));

        $I->dontSeeResponseContains('true');
        $I->seeResponseCodeIs(403);
    }

    public function executableWithPreExistingApiKey(ApiTester $I) {
        $apiKey = 'executable_' . date('YmdHis');
        $I->insertApiKey($apiKey);

        $I->sendPOST('/api/adapter/executable_attribute_name/general_regular_executable', array(
            'apikey'     => $apiKey,
            'method'    => 'json',
            'ciid'      => 1,
        ));

        $I->seeResponseContainsJson(array(
            'success' => true
        ));
        $I->seeResponseCodeIs(200);
    }

    public function executableWithAuthenticatedUser(ApiTester $I) {
        $I->loggingInViaGui();

        $I->sendPOST('/api/adapter/executable_attribute_name/general_regular_executable', array(
            'method'    => 'json',
            'ciid'      => 1,
        ));

        $I->seeResponseContainsJson(array(
            'success' => true
        ));
        $I->seeResponseCodeIs(200);
    }

    public function workflowWithInvalidApiKey(ApiTester $I) {
        $apiKey = 'INVALID_1234';

        $I->sendPOST('/api/adapter/workflow/test', array(
            'apikey'     => $apiKey,
            'method'     => 'json',
            'argv1'      => 'INVALID',
        ));

        $I->dontSeeResponseContains('true');
        $I->seeResponseCodeIs(403);
    }

    public function workflowWithPreExistingApiKey(ApiTester $I) {
        $apiKey = 'workflow_' . date('YmdHis');
        $I->insertApiKey($apiKey);

        $I->sendPOST('/api/adapter/workflow/test', array(
            'apikey'     => $apiKey,
            'method'     => 'json',
            'argv1'      => 'pre_existing_key',
        ));

        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'Workflow successful',
            'data'    => array(
                'status' => 'CLOSED'
            ),
        ));
        $I->seeResponseCodeIs(200);
    }

    public function workflowWithAuthenticatedUser(ApiTester $I) {
        $I->loggingInViaGui();

        $I->sendPOST('/api/adapter/workflow/test', array(
            'method'    => 'json',
            'argv1'     => 'session',
        ));

        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'Workflow successful',
            'data'    => array(
                'status' => 'CLOSED'
            ),
        ));
        $I->seeResponseCodeIs(200);
    }
}
