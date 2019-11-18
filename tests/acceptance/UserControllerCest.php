<?php

class UserControllerCest extends AbstractAcceptanceTest
{
    protected $userDefaultArr = array(
        'theme'               => array(
            'type'  => 'select',
            'value' => 'Reader',
        ),
        'layout'              => array(
            'type'  => 'select',
            'value' => 'default',
        ),
        'name'                => array(
            'type'  => 'text',
            'value' => 'test_uc_reader_1',
        ),
        'email'               => array(
            'type'  => 'text',
            'value' => 'test_nopermissions@localhost',
        ),
        'firstname'           => array(
            'type'  => 'text',
            'value' => 'Firstname',
        ),
        'lastname'            => array(
            'type'  => 'text',
            'value' => 'LastName',
        ),
        //
        'password'            => array(
            'type'  => 'text',
            'value' => 'somepassword1',
        ),
        'password_confirm'    => array(
            'type'  => 'text',
            'value' => 'somepassword1',
        ),
        'password_expire_off' => array(
            'type'  => 'checkbox',
            'value' => true,
        ),
        'description'         => array(
            'type'  => 'text',
            'value' => '',
        ),
        'note'                => array(
            'type'  => 'text',
            'value' => '',
        ),
        'language'            => array(
            'type'  => 'select',
            'value' => 'Deutsch',
        ),
        'ldapAuth'            => array(
            'type'  => 'checkbox',
            'value' => true,
        ),
        'relationDelete'      => array(
            'type'  => 'checkbox',
            'value' => false,
        ),
        'ciDelete'            => array(
            'type'  => 'checkbox',
            'value' => false,
        ),
        'isRoot'              => array(
            'type'  => 'checkbox',
            'value' => false,
        ),
        'projectId_1'         => array(
            'type'  => 'checkbox',
            'value' => false,
        ),
        'roleId_1'            => array(
            'type'  => 'checkbox',
            'value' => false,
        ),
    );

    protected $userReaderArr;
    protected $userAdminArr;

    public function __construct()
    {
        $this->userReaderArr = $this->userDefaultArr;
        $this->userAdminArr  = $this->userDefaultArr;

        $this->userAdminArr['name']['value']           = 'test_uc_admin_1';
        $this->userAdminArr['theme']['value']          = 'Admin';
        $this->userAdminArr['relationDelete']['value'] = true;
        $this->userAdminArr['ciDelete']['value']       = true;
        $this->userAdminArr['isRoot']['value']         = true;
    }

    public function create(AcceptanceTester $I)
    {
        $I->wantTo('Create reader user');
        $I->amOnPage('/user/index');
        $I->click('Benutzer anlegen');

        foreach ($this->userReaderArr as $prefKey => $prefVal) {
            $fieldValue = $prefVal['value'];
            switch ($prefVal['type']) {
                case "select":
                    $I->selectOption($prefKey, $fieldValue);
                    break;
                case "checkbox":
                    if ($fieldValue === true) {
                        $I->checkOption($prefKey);
                    } else {
                        $I->uncheckOption($prefKey);
                    }
                    break;
                default:
                    $I->fillField($prefKey, $fieldValue);
            }
        }

        $I->click('input[value=Speichern]');
        $I->waitForAjaxLoad();
        $I->see('Benutzer wurde erfolgreich angelegt!');

        $I->wantTo('Create admin user');
        $I->click('Benutzer anlegen');

        foreach ($this->userAdminArr as $prefKey => $prefVal) {
            $fieldValue = $prefVal['value'];
            switch ($prefVal['type']) {
                case "select":
                    $I->selectOption($prefKey, $fieldValue);
                    break;
                case "checkbox":
                    if ($fieldValue === true) {
                        $I->checkOption($prefKey);
                    } else {
                        $I->uncheckOption($prefKey);
                    }
                    break;
                default:
                    $I->fillField($prefKey, $fieldValue);
            }
        }

        $I->click('input[value=Speichern]');
        $I->waitForAjaxLoad();
        $I->see('Benutzer wurde erfolgreich angelegt!');

    }

    public function userCreateSqlInjection(AcceptanceTester $I)
    {
        $I->wantTo('Create an sql-injection critical reader user');

        $userSqlInjection = $this->userReaderArr;
        $userSqlInjection['name']['value'] = 'Hello\'" OR 1==1; --';
        $I->amOnPage('/user/index');
        $I->click('Benutzer anlegen');

        foreach ($userSqlInjection as $prefKey => $prefVal) {
            $fieldValue = $prefVal['value'];
            switch ($prefVal['type']) {
                case "select":
                    $I->selectOption($prefKey, $fieldValue);
                    break;
                case "checkbox":
                    if ($fieldValue === true) {
                        $I->checkOption($prefKey);
                    } else {
                        $I->uncheckOption($prefKey);
                    }
                    break;
                default:
                    $I->fillField($prefKey, $fieldValue);
            }
        }

        $I->click('input[value=Speichern]');
        $I->waitForAjaxLoad();
        $I->see('Benutzer wurde erfolgreich angelegt!');

        $I->wantTo('verify that the XSS string is plainly inserted as a login');

        $userSqlInjectionId = $I->grabFromDatabase('user', 'id', array(
            'username'        => $userSqlInjection['name']['value'],
        ));
        $I->assertGreaterThan(0, $userSqlInjectionId);
        $I->amOnPage('/user/delete/userId/' . $userSqlInjectionId);
        $I->waitForText('Benutzer wurde erfolgreich gelöscht!');
        $I->dontSeeInDatabase('user', array('id' => $userSqlInjectionId));
    }

    public function duplicateUser(AcceptanceTester $I)
    {
        $I->wantTo('duplicate a user account');
        $I->amOnPage('/user/index');

        $userLogin = $this->userReaderArr['name']['value'];
        $I->click('//a[string()="'.$userLogin.'"]/../../td/div/a[@class="duplicate_list buttonLink"]');
        $I->seeInField('name', 'copy_of_' . $userLogin);
        $I->fillField('password','someOtherPassword1');
        $I->fillField('password_confirm','someOtherPassword1');
        $I->click('input[value=Speichern]');
        $I->waitForAjaxLoad();
        $I->see('Benutzer wurde erfolgreich angelegt!');
    }

    public function delete(AcceptanceTester $I)
    {
        $I->wantTo('Cancel a user delete');
        $userLogin = $this->userReaderArr['name']['value'];
        $I->click('//a[string()="'.$userLogin.'"]/../../td/a[@class="delete_list"]');
        $I->seeInPopup('Wollen Sie den Benutzer wirklich löschen?');
        $I->cancelPopup();
        $I->dontSee('Benutzer wurde erfolgreich gelöscht!');
        $id = $I->grabFromDatabase('user', 'id', array('username' => $userLogin));
        $I->assertGreaterThan(0, $id, "reader user should still exist if dialog is canceled.");

        $I->wantTo('Delete the reader user');
        $I->amOnPage('/user/index');

        $userLogin = $this->userReaderArr['name']['value'];
        $I->click('//a[string()="'.$userLogin.'"]/../../td/a[@class="delete_list"]');
        $I->seeInPopup('Wollen Sie den Benutzer wirklich löschen?');
        $I->acceptPopup();
        $I->waitForText('Benutzer wurde erfolgreich gelöscht!');
        $id = $I->grabFromDatabase('user', 'id', array('username' => $userLogin));
        $I->assertFalse($id, "reader user should not exist in the database anymore.");

        $I->wantTo('Delete the duplicated reader user');
        $userLogin = 'copy_of_' . $userLogin;
        $I->click('//a[string()="'.$userLogin.'"]/../../td/a[@class="delete_list"]');
        $I->seeInPopup('Wollen Sie den Benutzer wirklich löschen?');
        $I->acceptPopup();
        $I->waitForText('Benutzer wurde erfolgreich gelöscht!');
        $id = $I->grabFromDatabase('user', 'id', array('username' => $userLogin));
        $I->assertFalse($id, "reader user should not exist in the database anymore.");

        $userLogin = $this->userAdminArr['name']['value'];
        $I->wantTo('Delete the admin user');
        $I->click('//a[string()="'.$userLogin.'"]/../../td/a[@class="delete_list"]');
        $I->seeInPopup('Wollen Sie den Benutzer wirklich löschen?');
        $I->acceptPopup();
        $I->waitForText('Benutzer wurde erfolgreich gelöscht!');
        $id = $I->grabFromDatabase('user', 'id', array('username' => $userLogin));
        $I->assertFalse($id, "admin user should not exist in the database anymore.");
    }

}