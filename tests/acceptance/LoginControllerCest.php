<?php
require_once realpath(dirname(__FILE__)  . '/../../') . '/application' . '/../library/composer/autoload.php';

class LoginControllerCest
{
    const loginPage = '/login/login';

    const inputUser = '#username';
    const inputPassword = '#password';
    const inputLoginSubmit = '#login';

    const loginPageIdentifier = 'Passwort vergessen?';

    public function __construct()
    {
       \Helper\Phinx::prepareTestEnvironment();
    }

    public function failLogin(AcceptanceTester $I)
    {
        $I->wantTo('fail login');

        $I->amOnPage(LoginControllerCest::loginPage);
        $I->dontSee('Error');
        $I->see(LoginControllerCest::loginPageIdentifier);
        $I->fillField(LoginControllerCest::inputUser, "admin");
        $I->fillField(LoginControllerCest::inputPassword, "asdf");
        $I->click(LoginControllerCest::inputLoginSubmit);
        $I->waitForAjaxLoad();
        $I->see('Benutzername / Passwort ist nicht gültig');
    }

    public function login(AcceptanceTester $I, $user = 'admin', $password = 'admin') {

        $I->wantTo('login successfully');

        $I->amOnPage(LoginControllerCest::loginPage);
        $I->fillField(LoginControllerCest::inputUser, $user);
        $I->fillField(LoginControllerCest::inputPassword, $password);
        $I->click(LoginControllerCest::inputLoginSubmit);

        // setup menu to only show top navigation points
        $I->waitForElement('#fancytree');
        $I->executeJS('$.ui.fancytree.getTree().visit(function(node) {
            node.setExpanded(false);
        });');
        $I->executeJS('$.ui.fancytree.getTree().getRootNode().getFirstChild().setExpanded(true);');

        // test if all navigation points are visible
        $I->see('Administration');
        $I->see('Benutzerverwaltung');
        $I->see('Automatisierung');
        $I->see('Schnittstellen');
        $I->see('Einstellungen');

        // test sidebar navigation
        $I->executeJS('$.ui.fancytree.getNode($("span.fancytree-title:contains(\'Durchsuchen\')")).setExpanded(true);');
        $I->waitForElement('//span[contains(@class, \'fancytree-title\')]/a[contains(string(), \'Sites\')]');
        $I->See('Sites');
        $I->dontSee('Austria');
        $I->executeJS('$.ui.fancytree.getNode($("span.fancytree-title:contains(\'Employee\')")).setExpanded(true);');
        $I->waitForElement('//span[contains(@class, \'fancytree-title\')]/a[contains(string(), \'Austria\')]');
        $I->click('Sites');
        $I->see('Austria');
    }

    public function maintenanceMode(AcceptanceTester $I) {
        $MaintenanceMessage = $I->createMaintenance();

        $I->wantTo('see maintenance page');
        $I->loggingOut($I);
        $I->loggingIn($I);
        $I->see($MaintenanceMessage);

        $I->wantTo('bypass maintenance page');
        $I->amOnPage('/index/maintenance/disableForCurrentSession/1');
        $I->see('Wartungs-Modus!');
        $I->see('Administration');
        $I->clearMaintenance();
    }

    public function logout(AcceptanceTester $I) {

        $I->wantTo('logout');

        $I->loggingIn($I);

        $I->amOnPage('index/index');
        $I->dontSee(LoginControllerCest::loginPageIdentifier);
        $I->click("#openTab");
        $I->wait(1);
        $I->see('Admin-Mode aktivieren');
        $I->click("Ausloggen");
        $I->see(LoginControllerCest::loginPageIdentifier);

        \Codeception\Util\Fixtures::add('auth_user', '');
        \Codeception\Util\Fixtures::add('auth_cookie', '');
    }


    function logoutFromAllAccounts(AcceptanceTester $I)
    {
        $I->wantTo('i want to login as admin to create one additional users session');

        $I->amOnPage('login/login');
        $I->fillField(\LoginControllerCest::inputUser, 'admin');
        $I->fillField(\LoginControllerCest::inputPassword, 'admin');
        $I->click(\LoginControllerCest::inputLoginSubmit);
        $I->waitForPageLoad(10);
        $I->resetCookie('INFOCMDB');

        $uidAdmin          = $I->grabFromDatabase('user', 'id', array('username' => 'admin'));
        $uidReader          = $I->grabFromDatabase('user', 'id', array('username' => 'reader'));
        $sessionCountAdmin = $I->grabNumRecords('user_session', array('user_id' => $uidAdmin));
        $I->assertGreaterThan(0, $sessionCountAdmin, 'admin session wasn\'t stored');

        $I->wantTo('login multiple times with reader');

        for ($i = 0; $i < 3; $i++) {
            $I->resetCookie('INFOCMDB');
            $I->amOnPage('login/login');
            $I->fillField(\LoginControllerCest::inputUser, 'reader');
            $I->fillField(\LoginControllerCest::inputPassword, 'reader');
            $I->click(\LoginControllerCest::inputLoginSubmit);
            $I->waitForPageLoad(10);
        }

        \Codeception\Util\Fixtures::add('auth_user', 'reader');
        \Codeception\Util\Fixtures::add('auth_cookie', $I->grabCookie('INFOCMDB'));
        $I->setCookie('INFOCMDB', \Codeception\Util\Fixtures::get('auth_cookie'));

        $sessionCountReader = $I->grabNumRecords('user_session', array('user_id' => $uidReader));

        $I->assertGreaterThan(1, $sessionCountReader, 'multiple open session should be found');

        $I->amOnPage('user/usersettings/');
        $I->click('Von allen Geräten abmelden');

        $sessionCountReader = $I->grabNumRecords('user_session', array('user_id' => $uidReader));
        $I->assertEquals(0, $sessionCountReader, 'no session should be open');

        $sessionCountAdmin = $I->grabNumRecords('user_session', array('user_id' => $uidAdmin));
        $I->assertGreaterThan(0, $sessionCountAdmin, 'admin sessions have been reset as well');

        \Codeception\Util\Fixtures::add('auth_user', '');
        \Codeception\Util\Fixtures::add('auth_cookie', '');
    }

    function twoFactorAuth(AcceptanceTester $I)
    {
        $I->wantTo('i setup tfa class');
        $tfa = new RobThree\Auth\TwoFactorAuth('infoCMDB');

        $tfaUser = array(
//            'id' => ,
            'username'                 => 'tfa_test_user_' . time(),
            'password'                 => 'tfa_test_pw1',
            'email'                    => 'tfa@localhost',
            'firstname'                => 'tfa',
            'lastname'                 => 'tester',
            'description'              => '',
            'note'                     => '',
            'language'                 => 'de',
            'layout'                   => 'default',
            'theme_id'                 => 3, // reader
            'is_root'                  => '0',
            'is_ci_delete_enabled'     => '0',
            'is_relation_edit_enabled' => '0',
            'is_ldap_auth'             => '0',
            'is_active'                => '1',
            'is_two_factor_auth'       => '0',
            'password_expire_off'      => '1',
//            'password_changed' => ',
//            'secret' => null,
//            'api_secret' => null,
//            'last_access' => null,
//            'settings' => null,
            'user_id'                  => 1, // created by
            'valid_from'               => '2019-08-05T06:24:35Z',
        );

        $I->wantTo('create tfa test user');
        $tfaUserId = $I->haveInDatabase('user', $tfaUser);

        $I->wantTo('login without having tfa enabled');

        $I->amOnPage('login/login');
        $I->fillField(\LoginControllerCest::inputUser, $tfaUser['username']);
        $I->fillField(\LoginControllerCest::inputPassword, $tfaUser['password']);
        $I->click(\LoginControllerCest::inputLoginSubmit);
        $I->waitForAjaxLoad(10);

        $I->wantTo('activate tfa');

        $I->amOnPage('/user/usersettings/');
        $I->click('2-Faktoren Authentifizierung');

        $I->wantTo('have an invalid tfa code');
        $I->fillField('TFAVerifyCode', '1234');
        $I->click('Code verifizieren');
        $I->waitForAjaxLoad(10);
        $I->See('Der Code ist nicht gültig');

        $I->wantTo('have an valid tfa code');
        $tfaKey = $I->grabTextFrom('//li[@id="QRCode"]/../li[last()]/b');
        $tfaCode = $tfa->getCode($tfaKey);

        $I->fillField('TFAVerifyCode', $tfaCode);
        $I->click('Code verifizieren');
        $I->waitForAjaxLoad(10);
        $I->dontSee('Der Code ist nicht gültig');
        $I->See('Sie haben die 2-Faktoren Authentifizierung aktiviert');

        $I->amOnPage('login/logout');

        $I->wantTo('login with tfa active');

        $I->amOnPage('login/login');
        $I->fillField(\LoginControllerCest::inputUser, $tfaUser['username']);
        $I->fillField(\LoginControllerCest::inputPassword, $tfaUser['password']);
        $I->click(\LoginControllerCest::inputLoginSubmit);
        $I->waitForAjaxLoad(10);
        $I->see('Geben Sie bitte den in der Google Authenticator App angezeigten Code ein');

        $I->wantTo('see tfa wrong error');

        $I->fillField('verifyCode', '00000');
        $I->click(\LoginControllerCest::inputLoginSubmit);
        $I->waitForAjaxLoad(10);
        $I->See('ERROR Der Code ist nicht gültig');
        $I->grabPageSource();

        $I->wantTo('login with correct tfa');

        $tfaCode = $tfa->getCode($tfaKey);
        $I->fillField('verifyCode', $tfaCode);
        $I->click(\LoginControllerCest::inputLoginSubmit);
        $I->waitForAjaxLoad(10);
        $I->dontSee('ERROR Der Code ist nicht gültig');
        $I->seeCurrentUrlEquals('/index/index');

        $I->amOnPage('login/logout');

        $I->wantTo('disable tfa via admin');
        $I->amOnPage('login/login');

        $I->fillField(\LoginControllerCest::inputUser,     'admin');
        $I->fillField(\LoginControllerCest::inputPassword, 'admin');
        $I->click(\LoginControllerCest::inputLoginSubmit);
        $I->waitForAjaxLoad(10);

        $I->amOnPage('user/edit/userId/' . $tfaUserId);

        $I->click('2-Faktoren Authentifizierung');
        $I->click('2-Faktoren Authentifizierung deaktivieren');
        $I->cancelPopup();
        $I->wait(2);
        $I->waitForAjaxLoad(10);
        $I->seeInDatabase('user', array('id' => $tfaUserId, 'is_two_factor_auth' => '1'));

        $I->click('2-Faktoren Authentifizierung deaktivieren');
        $I->acceptPopup();
        $I->wait(2);
        $I->waitForAjaxLoad(10);
        $I->see('2-Faktoren Authentifizierung für den Benutzer ist deaktiviert');
        $I->seeInDatabase('user', array('id' => $tfaUserId, 'is_two_factor_auth' => '0'));

        $I->amOnPage('login/logout');

        $I->wantTo('login without tfa enabled for user');

        $I->amOnPage('login/login');

        $I->fillField(\LoginControllerCest::inputUser, $tfaUser['username']);
        $I->fillField(\LoginControllerCest::inputPassword, $tfaUser['password']);
        $I->click(\LoginControllerCest::inputLoginSubmit);
        $I->waitForAjaxLoad(10);
        $I->seeCurrentUrlEquals('/index/index');
    }

    public function _after(AcceptanceTester $I)
    {
        $I->clearMaintenance();
    }


}
