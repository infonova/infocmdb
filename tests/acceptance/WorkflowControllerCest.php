<?php

class WorkflowControllerCest extends AbstractAcceptanceTest
{
    public function status(AcceptanceTester $I)
    {

        $workflowPath = \Codeception\Configuration::projectDir() . 'public/_uploads/workflow/';

        $name     = 'workflow_status_test';
        $indexUrl = '/workflow/index/search/' . $name;

        $I->wantTo('create new workflow');
        $I->amOnPage('/workflow/create');

        $I->fillField('name', $name);
        $I->fillField('description', 'Test if template works');
        $I->selectOption('#user', 'admin');
        $I->selectOption('#trigger', 'manuell');
        $I->click('input[value=Speichern]');


        $I->wantTo('Create id mismatch of workflow_case and workflow_item');
        $I->haveInDatabase('workflow_case', [
            'workflow_id' => 1,
            'context'     => null,
            'status'      => 'CLOSED',
            'user_id'     => 0,
        ]);

        $I->wantTo('Ensure only one workflow is listed');
        $I->amOnPage($indexUrl);
        $I->see('Anzahl der Ergebnisse: 1');

        $I->wantTo('Check Status is untouched (orange)');
        $I->seeInSource('info_yellow.png');

        $I->wantTo('Execute workflow to change status to success (green)');
        $I->click('Ausführen');
        $I->see('Workflow erfolgreich gestartet!');
        $I->amOnPage($indexUrl);
        $I->seeInSource('accept_16.png');

        $I->wantTo('Force workflow error to change status to error (red)');
        rename($workflowPath . $name . '.pl', $workflowPath . '_' . $name . '.pl');
        $I->click('Ausführen');
        $I->see('Workflow erfolgreich gestartet!');
        $I->wait(2);
        $I->amOnPage($indexUrl);
        $I->wait(2);
        $I->seeInSource('info_red.png');
        rename($workflowPath . '_' . $name . '.pl', $workflowPath . $name . '.pl');
    }

    public function scriptTemplatePerl(AcceptanceTester $I)
    {
        $name = 'workflow_script_template_perl';

        $I->wantTo('check workflow script matches template (perl)');
        $I->amOnPage('/workflow/create');

        $I->fillField('name', $name);
        $I->fillField('description', 'Test if template works');
        $I->selectOption('#user', 'admin');
        $I->selectOption('#trigger', 'manuell');
        $I->click('3Script');
        $I->scrollTo(['css' => 'input[value=Speichern]']);
        $I->click('Script validieren');
        $I->see('syntax OK', '#script_check_output');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad();

        $I->click('Bearbeiten');

        $I->click('3Script');
        $I->waitForAjaxLoad();
        $I->see("use lib '/app/library/perl/libs';");
        $I->see("my \$cmdb = InfoCMDB->new('infocmdb');");
    }

    public function scriptTemplateGo(AcceptanceTester $I)
    {
        $name = 'workflow_script_template_go';

        $I->wantTo('check workflow script matches template (go)');
        $I->amOnPage('/workflow/create');

        $I->fillField('name', $name);
        $I->fillField('description', 'Test if template works');
        $I->selectOption('#user', 'admin');
        $I->selectOption('#trigger', 'manuell');
        $I->click('3Script');
        $I->selectOption('#lang_selector', 'Golang');
        $I->waitForAjaxLoad(5);
        $I->see('package main');
        $I->scrollTo(['css' => 'input[value=Speichern]']);
        $I->click('Script validieren');
        $I->dontSee('error', '#script_check_output');
        $I->dontSee('fail', '#script_check_output');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad();

        $I->click('Bearbeiten');

        $I->click('3Script');
        $I->waitForAjaxLoad();
        $I->see("package");
    }


    public function rebuildAll(AcceptanceTester $I)
    {
        $workflowBinFile = codecept_root_dir() . '/data/workflows/golang/test-go/test-go';

        $I->wantTo('ensure workflow binary is not present');
        if (is_file($workflowBinFile)) {
            unlink($workflowBinFile);
        }

        $I->wantTo('ensure seeded workflow is builded');
        $I->amOnPage('/workflow/index');
        $I->click('Alle neu bauen');
        $I->see('OK');

        $I->wantTo('check workflow can be executed');
        $I->click('test-go');
        $I->click('Ausführen');
        $I->see('Workflow erfolgreich gestartet!');
    }

    public function solveStatus(AcceptanceTester $I)
    {
        $I->amOnPage('/workflow/detail/workflowId/1');
        $I->click('Ausführen');

        $I->seeInSource('Keine Fehler');
        $I->seeInSource('title="Als gelöst markieren"');

        $I->wantTo('Solve workflow');
        $I->click('Als gelöst markieren');
        $I->seeInSource('title="Fehler gelöst"');
        $I->dontSeeInSource('title="Als gelöst markieren"');
    }
}
