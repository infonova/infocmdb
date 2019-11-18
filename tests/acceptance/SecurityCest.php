<?php

class SecurityCest extends AbstractAcceptanceTest
{

    /**
     * @group security
     * @skip
     *      SKIPPING this Test as it doesn't have a solution at the moment without breaking other HTML based
     *      attribute output.
     */
    public function attributeInputXSSInjection(AcceptanceTester $I)
    {
        $I->wantTo('Ensure javascript is properly sanitized when editing ci attributes');

        $XssSource      = '<script>console.error("xss-injection-test-found"); alert(\'xss-injection-test-found\');</script>';
        $ciTypeEmpAtVie = $I->grabColumnFromDatabase('ci_type', 'id', array('name' => 'emp_austria_vienna'))[0];
        $userCiId       = $I->grabColumnFromDatabase('ci', 'id', array('ci_type_id' => $ciTypeEmpAtVie))[0];

        $attrFirstName                 = $I->grabColumnFromDatabase('attribute', 'id', array('name' => 'emp_firstname'))[0];
        $userCiAttributeFirstNameId    = $I->grabColumnFromDatabase('ci_attribute', 'id', array('ci_id' => $userCiId, 'attribute_id' => $attrFirstName))[0];
        $attrEmpStaffNumber            = $I->grabColumnFromDatabase('attribute', 'id', array('name' => 'emp_staff_number'))[0];
        $userCiAttributeEmpStaffNumber = $I->grabColumnFromDatabase('ci_attribute', 'value_text', array('ci_id' => $userCiId, 'attribute_id' => $attrEmpStaffNumber))[0];

        $I->updateInDatabase(
            'ci_attribute',
            array('value_text' => 'Test XSS' . $XssSource),
            array('id' => $userCiAttributeFirstNameId)
        );

        $I->amOnPage('/ci/index/typeid/' . $ciTypeEmpAtVie); // employee/austria/vienna

        $I->fillField('search', $userCiAttributeEmpStaffNumber);
        $I->click('filterButton');
        $I->waitForPageLoad(10);
        $I->cantSeeInPageSource($XssSource);
    }

    /**
     * @group security
     */
    public function searchStringXSS(AcceptanceTester $I)
    {
        $I->wantTo('Ensure searchstrings are properly escaped');
        $I->amOnPage('/search/index?searchstring=test"><script%20type="text/javascript">console.error(\'xss-injection-test-found\'); alert(\'xss-injection-test-found\');</script>&searchShortButton=Suchen');
        $I->amOnPage('/search/index?searchstring=test"; console.error(\'xss-injection-test-found\'); alert(\'xss-injection-test-found\'); //&searchShortButton=Suchen');
    }

    /**
     * @group security
     */
    public function changeToNonPermittedProject(AcceptanceTester $I)
    {
        $I->loggingIn($I, "single_project_author", "single_project_author");

        $I->amOnPage('/project/change/projectid/3');
        $I->see("Zugriff verweigert");

        $I->loggingOut($I);
    }

}