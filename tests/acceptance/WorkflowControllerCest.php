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

    public function testWorkflowInstanceJsonContext(AcceptanceTester $I)
    {
        $jsonTestData = <<< JSON
        {
            "Environment": {
            "APPLICATION_ENV": "production",
    "APPLICATION_PATH": "\/mnt\/webroot\/sdcmdb\/application",
    "APPLICATION_URL": "http:\/\/sdcmdbdev.infonova.at\/",
    "APPLICATION_DATA": "\/mnt\/webroot\/sdcmdb\/data",
    "APPLICATION_PUBLIC": "\/mnt\/webroot\/sdcmdb\/public\/",
    "GOCACHE": "\/mnt\/webroot\/sdcmdb\/data\/cache\/golang",
    "WORKFLOW_CONFIG_PATH": "\/mnt\/webroot\/sdcmdb\/application\/configs\/workflows"
  },
  "ciid": 184113,
  "triggerType": "ci_update",
  "data": {
            "old": {
                "relations": {
                },
      "projects": {
                    "23": {
                        "id": "23",
          "name": "emea",
          "description": "BE-EMEA",
          "note": "Bearingpoint EMEA",
          "order_number": "0",
          "is_active": "1",
          "user_id": "0",
          "valid_from": "0000-00-00 00:00:00",
          "ci_project_valid_from": "2011-07-01 10:16:58",
          "ci_project_history_id": "818162"
        }
      },
      "ciTypeId": "275",
      "ciTypeName": "user_austria_graz",
      "attributes": {
                    "301": {
                        "2608582": {
                            "id": "301",
            "name": "ma_sap_no",
            "description": "http://username:password@webservice.call.com",
            "note": "SAT file column A 'SAP-No'",
            "hint": "SAT file column A \"SAP-No\"",
            "attribute_type_id": "1",
            "attribute_group_id": "1",
            "order_number": "50",
            "column": "1",
            "is_unique": "1",
            "is_numeric": "0",
            "is_bold": "0",
            "is_event": "0",
            "is_unique_check": "0",
            "is_autocomplete": "0",
            "is_multiselect": "0",
            "is_project_restricted": "0",
            "regex": "",
            "workflow_id": null,
            "script_name": null,
            "tag": null,
            "input_maxlength": "15",
            "textarea_cols": "0",
            "textarea_rows": "0",
            "is_active": "1",
            "user_id": "0",
            "valid_from": "2012-01-02 08:33:47",
            "historicize": "1",
            "display_style": null,
            "attributeTypeName": "input",
            "attribute_group": "General",
            "parent_attribute_group": "0",
            "value_text": "4041531",
            "value_date": null,
            "value_ci": null,
            "ciAttributeId": "2608582",
            "initial": "0",
            "valueNote": null,
            "history_id": "928883",
            "value_default": null
          }
        }
      }
    }
  },
  "user_id": "126"
}
JSON;
        $I->wantTo('See valid json context');

        $instanceId = $I->haveInDatabase('workflow_case', [
            'workflow_id' => 1,
            'context' => $jsonTestData,
            'status' => 'CLOSED',
            'user_id' => 0,
        ]);

        $I->amOnPage('/workflow/instance/instanceId/' . $instanceId);

        $contextContainer = $I->findElement(WebDriverBy::id("context_container"));
        $contextContainerHtml = $contextContainer->getAttribute('innerHTML');
        $I->assertNotEmpty($contextContainerHtml, "context should be displayed");

        $I->wantTo('See no credentials in json context');
        $I->assertNotContains("http://username:password@webservice.call.com", $contextContainerHtml,
            "credentials should be hidden");
        $I->assertContains("http://<credentials>@webservice.call.com", $contextContainerHtml,
            "credentials should be hidden");
    }

}
