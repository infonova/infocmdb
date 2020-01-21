<?php

class CiControllerCest extends AbstractAcceptanceTest
{

    public function create(AcceptanceTester $I)
    {
        $I->wantTo('create demo CI');
        $I->amOnPage('/ci/index/typeid/1');
        $I->click('Demo anlegen');
        $I->seeNumberOfElements('table.list_detail > tbody > tr', 26);

        $I->dontSeeElement('tr[data-attributeName="no_hint_icon"] td:nth-child(2) img');
        $I->seeElement('tr[data-attributeName="general_regular_input"] td:nth-child(2) img');

        $I->selectOption('project', '2');

        $ciAttributes = array(
            'general_unique_input' => array(
                'values' => array(
                    'u_1'
                ),
            ),
            'general_regular_input' => array(
                'values' => array(
                    'House'
                ),
            ),
            'general_numeric_input' => array(
                'values' => array(
                    314
                ),
            ),
            'general_textarea' => array(
                'values' => array(
                    'The quick brown fox jumps over the lazy dog (Textarea)'
                ),
            ),
            'general_textedit' => array(
                'values' => array(
                    'The quick brown fox jumps over the lazy dog (My Content in Editor)'
                ),
            ),
            'general_dropdown_static' => array(
                'values' => array(
                    'Option 1'
                ),
            ),
            'general_checkbox' => array(
                'values' => array(
                    array('Check 1')
                ),
            ),
            'general_radio' => array(
                'values' => array(
                    'Radio 2'
                ),
            ),
            'general_date' => array(
                'values' => array(
                    '2012-12-13'
                ),
            ),
            'general_datetime' => array(
                'values' => array(
                    '2021-01-21 12:31'
                ),
            ),
            'general_currency' => array(
                'values' => array(
                    '123,34'
                ),
            ),
            'general_password' => array(
                'values' => array(
                    'secret'
                ),
            ),
            'general_hyperlink' => array(
                'values' => array(
                    'https://example.com'
                ),
            ),
            // TODO: Attachment
            'general_dropdown_sql_filled_select' => array(
                'values' => array(
                    'Option 1'
                ),
            ),
            'general_dropdown_sql_filled_multiselect' => array(
                'values' => array(
                    array('Option 2' => array('amount' => 1))
                ),
            ),
            'general_dropdown_sql_filled_multiselect_counter' => array(
                'values' => array(
                    array(
                        'Option 3' => array('amount' => 2),
                        'Option 2' => array('amount' => 1)
                    ),
                ),
            ),

        );

        foreach($ciAttributes as $attributeName => $options) {
            foreach($options['values'] as $sequenceNumber => $value) {
                $I->fillCiAttributeValue($attributeName, $value, $sequenceNumber);
            }
        }

        $I->click('#create');
        $I->waitForAjaxLoad();
        $I->seeInCurrentUrl('ci/detail');

        foreach($ciAttributes as $attributeName => $options) {
            foreach($options['values'] as $sequenceNumber => $value) {
                $I->seeAttributeValueInCiDetail($attributeName, $value);
            }
        }

        // sql ampel attribute based on general_dropdown_static
        // to ensure ampel attributes aren't corrupted by xss filtering and are rendered to html properly
        $general_dropdown_static_value = $ciAttributes["general_dropdown_static"]["values"][0];
        $I->seeInPageSource('<div data-id="'.$general_dropdown_static_value.'" align="center" class="light" style="background:#00FF00">'.$general_dropdown_static_value.'</div>');
    }

    public function editWithActiveLock(AcceptanceTester $I) {
        $ciid = 15;

        $lockedSince = new DateTime();
        $sub = new DateInterval('PT1M');
        $lockedSince->sub($sub);

        $validUntil = new DateTime();
        $add = new DateInterval('PT1H');
        $validUntil->add($add);

        $I->haveInDatabase('lock', array(
            'lock_type'     => 'ci_lock',
            'resource_id'   => $ciid,
            'held_by'       => 3,
            'locked_since'  => $lockedSince->format('Y-m-d H:i:s'),
            'valid_until'   => $validUntil->format('Y-m-d H:i:s'),
        ));

        $I->wantTo('Open ci edit form with active lock of other user');
        $I->amOnPage('/ci/detail/ciid/' . $ciid);
        $I->click('Bearbeiten');
        $I->canSeeCurrentUrlEquals('/ci/detail/ciid/' . $ciid);
        $I->canSee('CI gesperrt (author, '.$lockedSince->format('Y-m-d H:i:s').')');

        $I->wantTo('Force Ci edit');
        $I->click('Bearbeiten erzwingen');
        $I->fillCiAttributeValue('site_name', 'AcceptanceTest Name');
        $I->click('input[value=Speichern]');
        $I->see('AcceptanceTest Name');

    }

    public function singleEditWithActiveLock(AcceptanceTester $I) {
        $ciid = 16;

        $lockedSince = new DateTime();
        $sub = new DateInterval('PT1M');
        $lockedSince->sub($sub);

        $validUntil = new DateTime();
        $add = new DateInterval('PT1H');
        $validUntil->add($add);

        $I->haveInDatabase('lock', array(
            'lock_type'     => 'ci_lock',
            'resource_id'   => $ciid,
            'held_by'       => 3,
            'locked_since'  => $lockedSince->format('Y-m-d H:i:s'),
            'valid_until'   => $validUntil->format('Y-m-d H:i:s'),
        ));

        $I->wantTo('Open single edit form with active lock of other user');
        $I->amOnPage('/ci/detail/ciid/' . $ciid);
        $I->click('a[title=Bearbeiten]');
        $I->waitForAjaxLoad();
        $I->canSee('CI gesperrt (author, '.$lockedSince->format('Y-m-d H:i:s').')');

        $I->wantTo('Force Ci edit');
        $I->click('Bearbeiten erzwingen');
        $I->fillCiAttributeValue('site_country', 'AcceptanceTest Country');
        $I->click('input[value=Speichern]');
        $I->see('AcceptanceTest Country');
    }

    public function attributeUniqueValidation(AcceptanceTester $I) {
        $validationMessage = 'Der eingegebene Wert wird bereits von einem anderen CI verwendet';

        $I->wantTo('Ensure unique value validation works for ci/create');
        $I->amOnPage('/ci/create/citype/1');
        $I->fillCiAttributeValue('general_unique_input', array(
            'values' => array(
                'demo_2'
            ),
        ));
        $I->waitForAjaxLoad();
        $I->canSee($validationMessage);
        $I->fillCiAttributeValue('general_unique_input', array(
            'values' => array(
                'demo_test_unique'
            ),
        ));
        $I->waitForAjaxLoad();
        $I->cantSee($validationMessage);

        $I->wait(2);
        $I->wantTo('Ensure unique value validation works for ci/edit');
        $I->amOnPage('/ci/index/typeid/1');
        $I->click('#ciListTable a.edit_list:nth-child(1)');
        $I->waitForAjaxLoad();
        $I->fillCiAttributeValue('general_unique_input', array(
            'values' => array(
                'demo_1'
            ),
        ));
        $I->waitForAjaxLoad();
        $I->canSee($validationMessage);
        $I->fillCiAttributeValue('general_unique_input', array(
            'values' => array(
                'demo_test_unique'
            ),
        ));
        $I->waitForAjaxLoad();
        $I->cantSee($validationMessage);
        $I->wait(2);

    }

    public function changeIconOfExistingCi(AcceptanceTester $I) {
        $ciid = 15;
        $newIcon = 'test_img.png';
        $imageSelector = '.pillar_icon img';

        $I->amOnPage('/ci/edit/ciid/' . $ciid);

        $I->wantTo('Check if links for handling icons are present');
        $imgPath = $I->grabAttributeFrom($imageSelector, 'src');
        $I->see('Icon hochladen');
        $I->dontSee('Icon löschen');

        $I->wantTo('Select a new icon and check if icon changes');
        $I->attachFile('#ciicon', $newIcon);
        $I->see('Icon löschen');
        $tmpImgPath = $I->grabAttributeFrom($imageSelector, 'src');
        $I->assertNotEquals($imgPath, $tmpImgPath);

        $I->wantTo('Check if icon is still present after saving form');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad();
        $newImgPath = $I->grabAttributeFrom($imageSelector, 'src');
        $I->assertNotEquals($imgPath, $newImgPath);
        $I->assertContains($newIcon, $newImgPath);

        $I->wantTo('Remove icon');
        $I->amOnPage('/ci/edit/ciid/' . $ciid);
        $I->click('Icon löschen');
        $I->waitForElementNotVisible('#ciicon_delete');

        $I->wantTo('Check icon changed back to default icon');
        $imgPathAfterDeletion = $I->grabAttributeFrom($imageSelector, 'src');
        $I->assertNotEquals($newImgPath, $imgPathAfterDeletion);
        $I->assertContains($imgPath, $imgPathAfterDeletion);

        $I->wantTo('Check if default icon is also present after saving form');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad();
        $imgPathAfterDeletion = $I->grabAttributeFrom($imageSelector, 'src');
        $I->assertContains($imgPath, $imgPathAfterDeletion);
    }

    public function setIconOfNewCi(AcceptanceTester $I) {
        $newIcon = 'test_img.png';
        $imageSelector = '.pillar_icon img';

        $I->amOnPage('ci/create/citype/1');
        $I->selectOption('project', '1');
        $I->fillCiAttributeValue('general_unique_input', 'setIconOfNewCi_#1');

        $I->wantTo('Check if links for handling icons are present');
        $imgPath = $I->grabAttributeFrom($imageSelector, 'src');
        $I->see('Icon hochladen');
        $I->dontSee('Icon löschen');

        $I->wantTo('Select a new icon and check if icon changes');
        $I->attachFile('#ciicon', $newIcon);
        $I->see('Icon löschen');
        $tmpImgPath = $I->grabAttributeFrom($imageSelector, 'src');
        $I->assertNotEquals($imgPath, $tmpImgPath);

        $I->wantTo('Check if icon is still present after saving form');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad();
        $newImgPath = $I->grabAttributeFrom($imageSelector, 'src');
        $I->assertNotEquals($imgPath, $newImgPath);
        $I->assertContains($newIcon, $newImgPath);

        $I->wantTo('Check if deleting an already selected icon works');
        $I->amOnPage('ci/create/citype/1');
        $I->selectOption('project', '1');
        $I->fillCiAttributeValue('general_unique_input', 'setIconOfNewCi_#2');
        $I->attachFile('#ciicon', $newIcon);
        $I->click('Icon löschen');
        $I->waitForElementNotVisible('#ciicon_delete');

        $I->wantTo('Check icon changed back to default icon');
        $imgPathAfterDeletion = $I->grabAttributeFrom($imageSelector, 'src');
        $I->assertContains($imgPath, $imgPathAfterDeletion);

        $I->wantTo('Check if default icon is also present after saving form');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad();
        $imgPathAfterDeletion = $I->grabAttributeFrom($imageSelector, 'src');
        $I->assertContains($imgPath, $imgPathAfterDeletion);
    }

    public function export(AcceptanceTester $I)
    {
        $I->amOnPage('/ci/index/typeid/1');
        $I->click('Exportieren (aktuelle Liste)');
        $I->wait(1);
        $I->dontSee('error');
        $I->wait(1);
        $I->switchToFirstWindow();
    }

    public function duplicate(AcceptanceTester $I)
    {
        $ciid = 18;

        $I->amOnPage('/ci/edit/ciid/' . $ciid);

        $I->wantTo('Grab data');
        $projectName = $I->grabTextFrom('.pillar-project');
        $ciType = $I->grabTextFrom('.pillar-citype');

        $I->wantTo('Check if grabbed data exists in duplicate view');
        $I->click('CI duplizieren');
        $I->seeOptionIsSelected('#project', $projectName);
        $I->see($ciType);
        $I->see('new');

    }

    public function listPerformance(AcceptanceTester $I)
    {
        $runs         = 5;
        $expectedTime = 8; // seconds

        $start = microtime(true);
        for ($i = 1; $i <= $runs; $i++) {
            $I->amOnPage('/ci/index/typeid/3');
        }
        $end = microtime(true);

        $fullTime = $end - $start;
        $avgTime  = $fullTime / $runs;

        $I->wantTo('Assert average load time of ci/list is less than '
            . $expectedTime . ' seconds (' . $runs . ' runs)'
        );
        $I->comment('Average load time is: ' . $avgTime . ' seconds');
        $I->assertLessThan($expectedTime, $avgTime);

    }

    public function triggerExecutable(AcceptanceTester $I)
    {
        $ciid      = 1;
        $testStart = date('Y-m-d H:i:s');

        $I->amOnPage('/ci/detail/ciid/' . $ciid);

        $I->wantTo('Trigger event executable');
        $I->click('Event Executable');
        $I->seeInPopup('Sind sie sicher, dass sie die Aktion ausführen möchten?');
        $I->acceptPopup();
        $I->see('Script erfolgreich ausgeführt');
        $I->seeCurrentUrlEquals('/ci/detail/ciid/' . $ciid);

        $I->wantTo("check if related workflow has a new execution entry");
        $I->seeInDatabase('workflow_case', [
            'created >='   => $testStart,
            'context like' => '%executable%'
        ]);
    }

    public function ciIndexSearch(AcceptanceTester $I)
    {
        // Employee > Austria > Vienna
        $I->amOnPage('/ci/index/typeid/10/');

        $I->wantTo('Search in ci/index');
        $I->fillField('#search', 'Karina');
        $I->click('Filtern');
        $I->see('(Anzahl der Ergebnisse: 1)', '.numberResult');
        $I->click('Filter löschen');

        $I->wantTo('Search in ci/index with Umlauts');
        $I->fillField('#search', 'Jürgen');
        $I->click('Filtern');
        $I->see('(Anzahl der Ergebnisse: 2)', '.numberResult');
        $I->click('Filter löschen');
    }

    public function ciIndexAttributeFilter(AcceptanceTester $I)
    {
        // Employee > Austria > Vienna
        $I->amOnPage('/ci/index/typeid/10/');

        $I->wantTo('Filter for attributes in ci/index');
        $I->click('Attributfilter ein');
        $I->fillField('#emp_firstname', 'Karina');
        $I->click('Filtern');
        $I->see('(Anzahl der Ergebnisse: 1)', '.numberResult');
        $I->click('Filter löschen');

        $I->wantTo('Filter for attributes in ci/index with Umlauts');
        $I->click('Attributfilter ein');
        $I->fillField('#emp_firstname', 'Jürgen');
        $I->click('Filtern');
        $I->see('(Anzahl der Ergebnisse: 1)', '.numberResult');
        $I->click('Filter löschen');

    }

}
