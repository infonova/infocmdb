<?php


class AttributeControllerCest extends AbstractAcceptanceTest
{

    public function index(AcceptanceTester $I)
    {
        $I->wantTo('open attribute list');
        $I->amOnPage('/attribute/index');
        $I->see('Anzahl der Ergebnisse');
    }

    public function create(AcceptanceTester $I) {
        $I->wantTo('create input attribute');
        $I->amOnPage('/attribute/index');
        $I->click('Attribut anlegen');
        $I->seeCurrentURLEquals('/attribute/create');
        $I->fillField('name', 'car_model');
        $I->fillField('note', 'Model of the Car');
        $I->fillField('description', 'Model');
        $I->fillField('sorting', '10');
        $I->click('3Individuell');
        $I->fillField('text[defaultvalue]', 'VW Up');
        $I->click('5Berechtigungen hinzufügen');
        $I->selectOption('input[name=roleId_1]', '2');
        $I->click('input[value=Speichern]');
        $I->see('Attribut erfolgreich angelegt!');
        $I->fillField('search', 'car_model');
        $I->click('filterButton');
        $I->see('Model');
    }

    public function edit(AcceptanceTester $I) {
        $I->wantTo('edit input attribute');
        $I->amOnPage('/attribute/index');
        $I->fillField('search', 'car_model');
        $I->click('filterButton');

        $href = $I->grabAttributeFrom('//a[contains(., \'car_model\')]', 'href');
        $id = substr($href, strripos($href, '/')+1);

        $I->click('//a[contains(@href, \'edit/attributeId/'.$id.'\')]');
        $I->see('Berechtigungen hinzufügen');
        $I->fillField('description', 'Modell');
        $I->fillField('note', 'Model of the Cars');
        $I->fillField('sorting', '105');
        $I->click('5Berechtigungen hinzufügen');
        $I->selectOption('input[name=roleId_1]', '1');
        $I->click('input[value=Speichern]');
        $I->see('Attribut erfolgreich bearbeitet!');
        $I->fillField('search', 'car_model');
        $I->click('filterButton');
        $I->see('Modell');
        $I->see('105');
    }

    public function delete(AcceptanceTester $I) {
        $I->wantTo('delete attribute');
        $I->amOnPage('/attribute/index');
        $I->fillField('search', 'car_model');
        $I->click('filterButton');

        $href = $I->grabAttributeFrom('//a[contains(., \'car_model\')]', 'href');
        $id = substr($href, strripos($href, '/')+1);

        $I->click('//a[contains(@href,\'delete_attribute('.$id.')\')]');
        $I->seeInPopup('Wollen Sie das Attribute wirklich löschen?');
        $I->acceptPopup();
        $I->see('Attribut erfolgreich gelöscht!');
        $I->fillField('search', 'car_model');
        $I->click('filterButton');
        $I->dontSee('car_model');
    }

    public function emptyHint (AcceptanceTester $I){
        $I->wantTo('create Attribute and check if Hint is empty');
        $I->amOnPage('/attribute/create');
        $I->fillField('name', 'empty hint');
        $I->fillField('description','is Hint emtpy?');
        $I->click('input[value=Speichern]');
        $I->see('Attribut erfolgreich angelegt!');
        $I->fillField('search', 'empty hint');
        $I->click('filterButton');
        $I->see('empty hint');
        $I->seeInDatabase('attribute', array(
            'name'  => 'empty hint',
            'hint'  => '',
        ));

        //Edit the created attribute and check if Hint stays empty
        $I->wantTo('Update Attribute and check if Hint stays empty');
        $I->click('empty hint');
        $I->click('input[value=Speichern]');
        $I->see('Attribut erfolgreich bearbeitet!');
        $I->seeInDatabase('attribute', array(
            'name'  => 'empty hint',
            'hint'  => '',
        ));
    }

    public function nonEmptyHint(AcceptanceTester $I){
        $I->wantTo('create Atttribute and check if Hint is NOT empty');
        $I->amOnPage('/attribute/create');
        $I->fillField('name', 'non empty hint');
        $I->fillField('description','is Hint not emtpy?');
        $I->click('2Optional');
        $I->executeJS('tinyMCE.activeEditor.setContent("<div>are you showing up?</div>");');
        $I->click('input[value=Speichern]');
        $I->see('Attribut erfolgreich angelegt!');
        $I->fillField('search', 'non empty hint');
        $I->click('filterButton');
        $I->see('non empty hint');
        $I->seeInDatabase('attribute', array(
            'name'  => 'non empty hint',
            'hint'  => '<div>are you showing up?</div>',
        ));
    }

    public function attributeWorkflow(AcceptanceTester $I) {
        $attributeName = 'attribute_script_workflow';

        $I->wantTo('check selected attribute workflow is stored correctly');
        $I->amOnPage('/attribute/create');

        $I->fillField('name', $attributeName);
        $I->fillField('description', 'Test attribute workflow');
        $I->selectOption('#attributeType', 'Script');
        $I->waitForAjaxLoad();
        $I->click('3Individuell');
        $I->click('#workflow_id-showbutton');
        $I->waitForAjaxLoad();
        $I->click('//ul[@id="ui-id-6"]/li[text() ="test"]');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad();

        $I->fillField('search', $attributeName);
        $I->click('filterButton');
        $I->click($attributeName);

        $I->click('3Individuell');
        $I->waitForAjaxLoad();
        $I->seeInField("#workflow_id-input", "test");
    }

    public function autoCompleteInput (AcceptanceTester $I){
        $I->amGoingTo('test various combobox-select form fields');

        /*
         * create Attribute
         */
        $I->wantTo('create attribute and check it in db');
        $I->amOnPage('/attribute/create');
        $I->fillField('name', 'sql_testattribut');
        $I->fillField('description','sqlAttribut');
        $I->selectOption('attributeType', 'Dropdown (SQL filled)');
        $I->selectOption('displayType', 'General');
        $I->fillField('sorting', '1');
        $I->click('3Individuell');
        $I->waitForElement('.ace_text-input', 10);
        $I->fillField('.ace_text-input',
            'SELECT 1 AS id, "Test_Option 1" AS value FROM dual UNION SELECT 2 AS id, "Test_Option 2" AS value FROM dual UNION SELECT 3 AS id, "Test_Option 3" AS value FROM dual');
        $I->fillField('query[textfieldWidth]', '');
        $I->scrollToTop();
        $I->click('4Ci Typen hinzufügen');
        $I->selectOption('input[title=sql_attribute_citype]', '2');
        $I->click('5Berechtigungen hinzufügen');
        $I->selectOption('input[name=roleId_1]', '2');
        $I->selectOption('input[name=roleId_2]', '2');
        $I->selectOption('input[name=roleId_3]', '1');
        $I->click('input[value=Speichern]');
        $I->see('Attribut erfolgreich angelegt!');

        $I->wantTo('check created attribute in database');
        $I->fillField('search', 'sql_testattribut');
        $I->click('filterButton');
        $I->see('sql_testattribut');
        $I->seeInDatabase('attribute', array(
            'name'  => 'sql_testattribut',
            'description'  => 'sqlAttribut',
        ));

        /*
         * CI-Type
         */
        $I->wantTo('link the created attribute to a ci type');
        $I->amOnPage('/citype/index');
        $I->fillField('search', 'sql_attribute_citype');
        $I->click('filterButton');
        $I->see('sql_attribute_citype');
        $I->click('sql_attribute_citype');
        $I->fillField('#defaultAttribute-input', 'sql_testattrib');
        $I->waitForAjaxLoad(10);

        $I->wantTo('select sql_testattribut in ajax dropdown list');
        // xpath: look through all ul (with given id) if it has a li with the following text
        $I->click('//ul[@id="ui-id-6"]/li[text() ="sql_testattribut"]');

        $I->wantTo('click on dropdown button');
        $I->click('#defaultSortAttribute-showbutton');
        $I->waitForAjaxLoad(10);

        $I->wantTo('select emp_ad_username in ajax dropdown list');
        $I->click('//ul[@id="ui-id-7"]/li[text() ="emp_ad_username"]');
        $I->wait(1);

        $I->wantTo('test clear button');
        $I->click('#defaultSortAttribute-clearbutton');
        $I->seeInField('#defaultSortAttribute-input', '');

        $I->wantTo('continue and then save');
        $I->click('3Attribute hinzufügen');
        $I->fillField('#addAttribute_0-input', 'sql_testattrib');
        $I->waitForAjaxLoad(10);

        $I->wantTo('select sql_testattribut in ajax dropdown list');
        $I->click('//ul[@id="ui-id-8"]/li[text() ="sql_testattribut"]');
        $I->click('4Listenansicht');
        $I->fillField('#create_1-input', 'sql_testattrib');
        $I->waitForAjaxLoad(10);

        $I->wantTo('select sql_testattribut in ajax dropdown list');
        $I->click('//ul[@id="ui-id-248"]/li[text() ="sql_testattribut"]');
        $I->fillField('#create_2-input', 'sql_testattrib');
        $I->waitForAjaxLoad(10);

        $I->wantTo('select sql_testattribut in ajax dropdown list');
        $I->click('//ul[@id="ui-id-249"]/li[text() ="sql_testattribut"]');
        $I->click('#fragment-4 input[value=Speichern]');
        $I->see('CI Typ erfolgreich bearbeitet!');

        $I->wantTo('check if it worked via editing');
        $I->fillField('search', 'sql_attribute_citype');
        $I->click('filterButton');
        $I->see('sql_attribute_citype');
        $I->click('sql_attribute_citype');
        $I->waitForPageLoad(10);
        $I->seeInField('#defaultAttribute-input', 'sql_testattribut');
        $I->seeInField('#defaultSortAttribute-input', '');
        $I->fillField('#defaultSortAttribute-input', 'sql_testattrib');
        $I->waitForAjaxLoad(10);

        $I->wantTo('select sql_testattribut in ajax dropdown list');
        $I->click('//ul[@id="ui-id-7"]/li[text() ="sql_testattribut"]');
        $I->click('#fragment-1 input[value=Speichern]');
        $I->waitForPageLoad(10);
        $I->see('CI Typ erfolgreich bearbeitet!');

        /*
         * CI
         */
        $I->wantTo('create a ci with the attribute');
        $I->amOnPage('/ci/create');
        $I->selectOption('parentCiType', 'sql_attribute_citype');
        $I->selectOption('project', 'General');
        $I->waitForElement('#sql_testattribut1', 10);
        $I->click('#sql_testattribut1');
        $I->see('Test_Option 1');
        $I->see('Test_Option 2');
        $I->see('Test_Option 3');
        $I->selectOption('#sql_testattribut1', 'Test_Option 2');
        $I->click('input[value=Speichern]');
        $I->see('CI wurde erfolgreich erstellt!');

        $I->wantTo('edit the ci');
        $I->see('Test_Option 2');
        $I->click('Bearbeiten');
        $I->click('#sql_testattribut1');
        $I->see('Test_Option 1');
        $I->see('Test_Option 2');
        $I->see('Test_Option 3');
        $I->selectOption('#sql_testattribut1', 'Test_Option 1');

        $I->wantTo('add an attribute by using the plus-icon');
        $I->click('#Generaladd');
        $I->waitForAjaxLoad(10);
        $I->fillField('#autoAttribute-input', 'sql_testattrib');
        $I->waitForAjaxLoad(10);

        $I->wantTo('select sql_testattribut in ajax dropdown list');
        $I->click('//ul[@id="ui-id-2"]/li[text() ="sql_testattribut"]');
        $I->click('input[value=Hinzufügen]');
        $I->waitForAjaxLoad(10);
        $I->waitForPageLoad(10);

        $I->wantTo('validate and fill additional attribute in form');
        $I->seeElement('#sql_testattribut1');
        $I->seeElement('#sql_testattribut2');
        $I->selectOption('#sql_testattribut2', 'Test_Option 3');
        $I->click('input[value=Speichern]');
        $I->see('CI wurde erfolgreich geändert!');
        $I->see('Test_Option 1');
        $I->see('Test_Option 3');
    }

    public function createDropDownStatic(AcceptanceTester $I) {
        $attributeName = 'dd_static_test_attribute_create';

        $I->wantTo('create dropdown static (with no values) attribute and check it in db');
        $I->amOnPage('/attribute/create');
        $I->fillField('name', $attributeName);
        $I->fillField('description','DD Static');
        $I->selectOption('attributeType', 'Dropdown (static)');
        $I->selectOption('displayType', 'General');
        $I->fillField('sorting', '1');
        $I->click('3Individuell');
        $I->waitForElement('#option', 10);

        $I->wantTo('add a new option');
        $I->fillField('option', 'Option 10');
        $I->fillField('ordernumber', 10);
        $I->click('Option hinzufügen');
        $optionSelector = "//input[@value='Option 10']";
        $I->waitForElement($optionSelector);
        $I->seeElement($optionSelector);

        $I->wantTo("add another option");
        $I->fillField('option', 'Option 20');
        $I->fillField('ordernumber', 20);
        $I->click('Option hinzufügen');
        $optionSelector = "//input[@value='Option 20']";
        $I->waitForElement($optionSelector);
        $I->seeElement($optionSelector);

        $I->wantTo('modify Option 20');
        $I->fillField("//input[@value='Option 20']", 'Option 20 (modified)');

        $I->wantTo('delete an option');
        $I->click( "//input[@name='options[remove]']");

        $I->click('input[value=Speichern]');

        $I->wantTo('open edit form of created attribute and confirm data');
        $I->fillField('search', $attributeName);
        $I->click('filterButton');
        $I->see($attributeName);
        $I->click($attributeName);
        $I->waitForPageLoad();
        $I->click('3Individuell');
        $optionSelector = "//input[@value='Option 20 (modified)']";
        $I->waitForElement($optionSelector);
        $I->seeElement($optionSelector);

    }

    public function editDropDownStatic(AcceptanceTester $I) {
        $attributeName = 'dd_static_test_attribute_edit';

        $I->wantTo('create dropdown static (with no values) attribute and check it in db');
        $I->amOnPage('/attribute/create');
        $I->fillField('name', $attributeName);
        $I->fillField('description','DD Static');
        $I->selectOption('attributeType', 'Dropdown (static)');
        $I->selectOption('displayType', 'General');
        $I->fillField('sorting', '1');
        $I->click('input[value=Speichern]');

        $I->wantTo('open edit form of created attribute');
        $I->fillField('search', $attributeName);
        $I->click('filterButton');
        $I->see($attributeName);
        $I->click($attributeName);
        $I->waitForPageLoad();
        $I->click('3Individuell');
        $I->waitForElement('#optionName', 10);

        $I->wantTo('add a new option');
        $I->fillField('optionName', 'Option 10');
        $I->fillField('ordernumber', 10);
        $I->click('Option hinzufügen');
        $I->waitForElement('#optionName', 10);
        $I->see('Option erfolgreich hinzugefügt!');
        $I->seeElement("//input[@value='Option 10']");

        $I->wantTo("add another option");
        $I->fillField('optionName', 'Option 20');
        $I->fillField('ordernumber', 20);
        $I->click('Option hinzufügen');
        $I->waitForElement('#optionName', 10);

        $I->wantTo('modify Option 10');
        $I->fillField("//input[@value='Option 10']", 'Option 10 (modified)');
        $I->click("//input[@value='Option 10']/../following-sibling::td/input");
        $I->waitForElement('#optionName', 10);
        $I->see('Option erfolgreich geändert!');
        $I->seeElement("//input[@value='Option 10 (modified)']");

        $I->wantTo('delete an option');
        $I->click("//form[2]/table/tbody/tr/td[4]/a/span/img");
        $I->see('Option wurde erfolgreich gelöscht!');
        $I->dontSee('Option 20');
        $I->dontseeElement("//input[@value='Option 20']");
    }
}
