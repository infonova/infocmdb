<?php

abstract class AbstractAcceptanceTest {

    public function _before(AcceptanceTester $I)
    {
        $I->clearMaintenance();
        $I->loggingIn($I);
    }

    public function _after(AcceptanceTester $I)
    {
        $I->clearMaintenance();
    }

}