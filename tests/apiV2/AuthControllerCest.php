<?php

class AuthControllerCest extends AbstractApiV2Test
{
    public function refreshToken(ApiTester $I)
    {
        $I->sendGET('/apiV2/auth/refresh');
        $I->seeResponseContainsJson(array(
            'success' => true
        ));
    }
}