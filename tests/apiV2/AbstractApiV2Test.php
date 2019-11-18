<?php

abstract class AbstractApiV2Test {

    public function _before(ApiTester $I)
    {
        $I->loggingIn();
    }

}