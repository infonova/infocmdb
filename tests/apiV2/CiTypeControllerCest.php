<?php

class CiTypeControllerCest extends AbstractApiV2Test
{
    public function getCiIndex(ApiTester $I)
    {
        $I->wantTo('fetch the full citype index');
        $I->sendGET('/apiV2/citype/index');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'success',
        ));
        $I->seeResponseJsonMatchesJsonPath('$.data[demo]');
        $I->seeResponseJsonMatchesJsonPath('$.data[demo].name');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.data');

    }

}
