<?php

class QueryControllerCest extends AbstractApiV2Test
{
    public function withoutName(ApiTester $I)
    {
        $I->sendPUT('/apiV2/query');
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Not Found',
        ));
    }

    public function invalidName(ApiTester $I)
    {
        $I->sendPUT('/apiV2/query/execute/THIS_QUERY_CAN_NOT_BE_FOUND');
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Not Found',
        ));
    }

    public function inactive(ApiTester $I)
    {
        $I->haveInDatabase('stored_query', array(
            'name'           => 'inactive_query',
            'query'          => 'select 1234 as number',
            'is_active'      => 0,
            'status'         => 1,
            'status_message' => '',
        ));
        $I->sendPUT('/apiV2/query/execute/inactive_query');
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Validation failed',
            'data'    => 'Query is inactive',
        ));
    }

    public function successWithoutParams(ApiTester $I)
    {
        $I->haveInDatabase('stored_query', array(
            'name'           => 'query_without_params',
            'query'          => 'select 1234 as number',
            'status'         => 1,
            'status_message' => '',
        ));
        $I->sendPUT('/apiV2/query/execute/query_without_params');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'Query executed successfully',
            'data'    => array(
                array(
                    'number' => 1234
                ),
            ),
        ));
    }

    public function successWithParams(ApiTester $I)
    {
        $I->haveInDatabase('stored_query', array(
            'name'           => 'query_with_params',
            'query'          => 'select ":my_param:" as result',
            'status'         => 1,
            'status_message' => '',
        ));

        $requestData = array(
            'query' => array(
                'params' => array(
                    'my_param' => "THIS is my parameter"
                ),
            ),
        );

        $I->sendPUT('/apiV2/query/execute/query_with_params', json_encode($requestData));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'Query executed successfully',
            'data'    => array(
                array(
                    'result' => 'THIS is my parameter'
                ),
            ),
        ));
    }

    public function userIdReplacement(ApiTester $I)
    {
        $I->haveInDatabase('stored_query', array(
            'name'           => 'query_with_user_id',
            'query'          => 'select ":user_id:" as result',
            'status'         => 1,
            'status_message' => '',
        ));

        $requestData = array(
            'query' => array(
                'params' => array(
                    'user_id' => "WRONG!!"
                ),
            ),
        );

        $I->sendPUT('/apiV2/query/execute/query_with_user_id', json_encode($requestData));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'Query executed successfully',
            'data'    => array(
                array(
                    'result' => '1'
                ),
            ),
        ));
    }

    public function sqlError(ApiTester $I)
    {
        $I->haveInDatabase('stored_query', array(
            'name'           => 'sql_error',
            'query'          => 'select abc as number',
            'status'         => 1,
            'status_message' => '',
        ));
        $I->sendPUT('/apiV2/query/execute/sql_error');
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Executing query failed',
            'data'    => null,
        ));
    }

}