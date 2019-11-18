<?php


class CiTypeControllerCest extends AbstractAcceptanceTest
{

    public function index(AcceptanceTester $I)
    {
        $I->wantTo('open citype list');
        $I->amOnPage('/citype/index');
        $I->see('Anzahl der Ergebnisse');
    }

    public function create(AcceptanceTester $I)
    {

        $I->wantTo('create citype');
        $I->amOnPage('/citype/create');
        $I->see('Übergeordneter CI Typ');
        $I->selectOption('#defaultProject', 'General');
        $I->fillField('#name', 'car');
        $I->fillField('#description', 'Auto');
        $I->fillField('#note', 'Autos');
        $I->fillField('#orderNumber', '10');
        $I->checkOption('#allowCiAttach');
        $I->checkOption('#allowAttributeAttach');
        $I->click('input[value=Speichern]');
        $I->see('CI Typ erfolgreich angelegt');
        $I->fillField('search', 'car');
        $I->click('filterButton');
        $I->see('Auto');
        $I->see('Autos');
        $I->see('10');

    }

    public function edit(AcceptanceTester $I) {
        $I->wantTo('edit citype');
        $I->amOnPage('/citype/index');
        $I->fillField('search', 'car');
        $I->click('filterButton');

        $href = $I->grabAttributeFrom('//a[contains(., \'car\')]', 'href');
        $id = substr($href, strripos($href, '/')+1);

        $I->click('//a[contains(@href, \'edit/citypeId/'.$id.'\')]');
        $I->see('CI Typ bearbeiten');
        $I->fillField('#description', 'Autobus');
        $I->click('input[value=Speichern]');
        $I->see('Autobus');
    }

    public function delete(AcceptanceTester $I) {

        $I->wantTo('delete citype');
        $I->amOnPage('/citype/index');

        $href = $I->grabAttributeFrom('//a[contains(., \'car\')]', 'href');
        $id = substr($href, strripos($href, '/')+1);

        $I->click('//a[contains(@onclick,\'delete_citype('.$id.')\')]');
        $I->seeInPopup('Wollen Sie den CI Typen wirklich löschen?');
        $I->acceptPopup();
        $I->waitForText('CI Typ wurde erfolgreich gelöscht!');

        $I->fillField('search', 'car');
        $I->click('filterButton');
        $I->dontSee('Autobus');

    }
}
