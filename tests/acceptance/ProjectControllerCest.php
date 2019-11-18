<?php

class ProjectControllerCest extends AbstractAcceptanceTest
{
    public function change(AcceptanceTester $I)
    {
        $I->wantTo("change to project Springfield");
        $I->click("#project_dropdown_trigger > a");
        $I->click("Springfield");
        $I->dontSee("Zugriff verweigert");

        $I->wantTo("change to ALL projects");
        $I->click("#project_dropdown_trigger > a");
        $I->click("#display_all_projects");
        $I->dontSee("Zugriff verweigert");
    }
}