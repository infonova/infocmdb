<?php


class AnnouncementControllerCest extends AbstractAcceptanceTest
{
    public function create(AcceptanceTester $I){
        $I->wantTo('create an announcement');
        $I->amOnPage('/announcement/create');
        $I->waitForPageLoad(10);
        
        /*
        * Test validators
        */
        $I->wantTo('test the empty field validators');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad(10);
        $I->seeCurrentUrlEquals('/announcement/create');
        $I->see('Feld darf nicht leer sein');

        /*
        * Continue creation
        */
        $I->wantTo('continue creating announcement');
        $I->fillField('name', 'This would be the internal description');
        $I->seeOptionIsSelected('type', 'Information');
        $I->fillField('title_de', 'Hallo Welt');
        $I->fillTinyMceEditorByName('message_de',  'Lass uns diesen Editor auffüllen');
        $I->fillField('title_en', 'Hello World');
        $I->fillTinyMceEditorByName('message_en',  'Let us fill up this editor too');
        $I->fillField('show_from_date', '1900-06-30 03:20:15');
        $I->fillField('show_to_date', '3000-08-10 05:10:30');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad(10);
        $I->see('Ankündigung erfolgreich angelegt!');

        /*
         * Check created announcement
         */
        $I->wantTo('check created announcement');
        $I->fillField('search', 'auffüllen');
        $I->click('filterButton');
        $I->waitForPageLoad(10);
        $I->see('Hallo Welt');
        $I->see('Hello World');
        $I->see('This would be the internal description');
        $I->see('1900-06-30');
        $I->see('3000-08-10');
        $I->see('Information');
        $I->see('inaktiv');
    }
    
    
    public function duplicate(AcceptanceTester $I){
        $announcementId = $I->grabColumnFromDatabase('announcement', 'id', array('name' => 'announcement2_inthefuture'));
        $I->wantTo('clone announcement');
        $I->amOnPage('/announcement/create/cloneFromId/' . $announcementId[0]);
        $I->seeInField('name', 'copy_of_announcement2_inthefuture');
        $I->seeOptionIsSelected('type', 'Frage');
        $I->seeInField('title_de', 'Headline2_de');
        $I->seeInField('title_en', 'Headline2_en');
        $I->switchToIFrame('message_de_ifr');
        $I->see('Text2_de');
        $I->switchToIFrame();
        $I->switchToIFrame('message_en_ifr');
        $I->see('Text2_en');
        $I->switchToIFrame();
        $I->seeInField('show_from_date', '2900-06-30 03:20:15');
        $I->seeInField('show_to_date', '3000-08-10 05:10:30');
        $I->seeCheckboxIsChecked('#valid');

        $I->wantTo('rename announcement attributes');
        $I->fillField('name', 'announcement_clone');
        $I->fillField('title_de', 'Headline_clone');
        $I->uncheckOption('#valid');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad(10);
        $I->see('Ankündigung erfolgreich angelegt!');
        $I->fillField('search', 'announcement_clone');
        $I->click('filterButton');
        $I->waitForPageLoad(10);

        $I->wantTo('check cloned announcement');
        $I->see('announcement_clone');
        $I->see('Headline_clone');
        $I->see('Headline2_en');
        $I->see('2900-06-30');
        $I->see('3000-08-10');
        $I->see('Frage');
        $I->see('inaktiv');
    }


    public function filter(AcceptanceTester $I){
        
        /*
         * Search by name
         */
        $I->wantTo('search by name');
        $I->amOnPage('/announcement/index');
        $I->fillField('search', 'announcement1_forfiltering');
        $I->click('filterButton');
        $I->waitForPageLoad(10);
        $I->see('aaa_headline1_de');
        $I->see('zzz_headline1_en');
        $I->see('announcement1_forfiltering');
        $I->see('1900-06-30');
        $I->see('3000-08-10');
        $I->see('Information');
        $I->see('aktiv');
        
        /*
         * Change items per page
         */
        $I->wantTo('Change items per page');
        $I->amOnPage('/announcement/index');
        $I->click('.dropdown');
        $I->click('//ul[@id="itemsPerPageList"]/li[text() ="10"]');
        $I->waitForPageLoad(10);

        /*
         * Check items per page
         */
        $I->wantTo('Check items per page');
        $I->countRowsInTable(10);

        /*
         * ASC filtering
         */
        $I->wantTo('ASC filtering');
        $I->amOnPage('/announcement/index/page//orderBy/title_de/direction/ASC');
        $I->see('aaa_headline1_de');

        /*
         * DESC filtering
         */
        $I->wantTo('DESC filtering');
        $I->amOnPage('/announcement/index/page//orderBy/title_en/direction/DESC');
        $I->see('zzz_headline1_en');
    }


    /*
     * Show announcements after login
     */
    public function display(AcceptanceTester $I){

        $I->wantTo('add announcements to db');
        $I->haveInDatabase('announcement', array(
            'name'           => 'announcement4',
            'show_from_date' => '1900-06-30 03:20:15',
            'show_to_date'   => '3000-06-30 03:20:15',
            'type'            => 'question',
            'is_active'      => '1',
            'user_id'        => '1',
        ));

        $I->haveInDatabase('announcement', array(
            'name'           => 'announcement5',
            'show_from_date' => '1900-06-30 03:20:15',
            'show_to_date'   => '3000-06-30 03:20:15',
            'type'            => 'information',
            'is_active'      => '1',
            'user_id'        => '1',
        ));

        $announcementId_1 = $I->grabColumnFromDatabase('announcement', 'id', array('name' => 'announcement4'));
        $announcementId_2 = $I->grabColumnFromDatabase('announcement', 'id', array('name' => 'announcement5'));

        //  English message not necessary
        $I->haveInDatabase('announcement_message', array(
            'announcement_id'   => $announcementId_1[0],
            'language'          => 'de',
            'title'             => 'Headline4_de',
            'message'           => 'Text4_de',
            'user_id'           => '1',
        ));

        $I->haveInDatabase('announcement_message', array(
            'announcement_id'   => $announcementId_2[0],
            'language'          => 'de',
            'title'             => 'Headline5_de',
            'message'           => 'Text5_de',
            'user_id'           => '1',
        ));

        $I->wantTo('check if announcement question shows up');
        $I->loggingOut($I);
        $I->loggingIn($I);
        $I->see('Headline4_de');
        $I->see('Text4_de');
        $I->seeElement('#announcementDecline');
        $I->seeElement('#announcementAccept');

        /*
         * Try to manipulate URL
         */
        $I->wantTo('manipulate the URL');
        $I->amOnPage('/announcement/index');
        $I->seeCurrentUrlEquals('/announcement/display/');
        $I->see('Headline4_de');
        $I->see('Text4_de');

        /*
         * Test checkbox validator
         */
        $I->wantTo('test checkbox validator');
        $I->seeCurrentUrlEquals('/announcement/display/');
        $I->see('Headline4_de');
        $I->see('Text4_de');
        $I->click('#announcementAccept');
        $I->see('Bitte bestätigen Sie zuerst, dass Sie den Text verstanden haben');
        $I->see('Headline4_de');
        $I->reloadPage();
        $I->click('#announcementDecline');
        $I->see('Bitte bestätigen Sie zuerst, dass Sie den Text verstanden haben');

        /*
         * Decline announcement (question)
         */
        $I->wantTo('decline the announcement');
        $I->checkOption('#announcementCheckbox');
        $I->click('#announcementDecline');
        $I->waitForText('Wollen Sie wirklich ablehnen?', 10);
        $I->waitForText('Ja, ablehnen', 10);
        $I->click('Ja, ablehnen');
        $I->waitForPageLoad(10);
        $I->seeCurrentUrlEquals('/login/login');
        $I->seeInDatabase('announcement_user', ['announcement_id' => $announcementId_1[0], 'accept' => '0']);
        $I->loggingOut($I);

        /*
         * Redirect after login
         */
        $I->wantTo('redirect after login');
        $I->amOnPage('/announcement/index');
        $I->loggingIn($I);

        /*
         * Accept first announcement (question)
         */
        $I->wantTo('accept first announcement');
        $I->seeCurrentUrlEquals('/announcement/display/');
        $I->see('Headline4_de');
        $I->see('Text4_de');
        $I->checkOption('#announcementCheckbox');
        $I->click('#announcementAccept');
        $I->waitForPageLoad(10);
        $I->seeInDatabase('announcement_user', ['announcement_id' => $announcementId_1[0], 'accept' => '1']);

        /*
         * Accept second announcement (information)
         */
        $I->wantTo('accept second announcement');
        $I->seeCurrentUrlEquals('/announcement/display/');
        $I->see('Headline5_de');
        $I->see('Text5_de');
        $I->click('#announcementAccept');
        $I->waitForPageLoad(10);
        $I->seeCurrentUrlEquals('/announcement/index');
        $I->seeInDatabase('announcement_user', ['announcement_id' => $announcementId_2[0], 'accept' => '1']);
    }
    /*
     * Show announcements during Maintenance
     */
    public function displayDuringMaintenance(AcceptanceTester $I){

        $I->wantTo('Enable maintenance mode');
        $MaintenanceMessage = $I->createMaintenance();

        $I->wantTo('add announcements to db');
        $announcementId = $I->haveInDatabase('announcement', array(
            'name'           => 'announcement7',
            'show_from_date' => '1900-06-30 03:20:15',
            'show_to_date'   => '3000-06-30 03:20:15',
            'type'            => 'question',
            'is_active'      => '1',
            'user_id'        => '1',
        ));

        $I->haveInDatabase('announcement_message', array(
            'announcement_id'   => $announcementId,
            'language'          => 'de',
            'title'             => 'Headline7_de',
            'message'           => 'Text7_de',
            'user_id'           => '1',
        ));

        $I->wantTo('check if maintenance shows up first');
        $I->loggingOut($I);
        $I->loggingIn($I);
        $I->see($MaintenanceMessage);

        $I->clearMaintenance();
    }


    /*
     * Also tests reconfirmation checkbox
     */
    public function edit(AcceptanceTester $I){
        
        $announcementId = $I->grabColumnFromDatabase('announcement', 'id', array('name' => 'announcement2_inthefuture'));
        $I->haveInDatabase('announcement_user', array('announcement_id' => $announcementId[0], 'user_id' => '1', 'accept' => '1'));
        $I->amOnPage('/announcement/edit/announcementId/' . $announcementId[0]);
        
        $I->wantTo('check announcement');
        $I->seeInField('name', 'announcement2_inthefuture');
        $I->seeOptionIsSelected('type', 'Frage');
        $I->seeInField('title_de', 'Headline2_de');
        $I->seeInField('title_en','Headline2_en');
        $I->switchToIFrame('message_de_ifr');
        $I->see('Text2_de');
        $I->switchToIFrame();
        $I->switchToIFrame('message_en_ifr');
        $I->see('Text2_en');
        $I->switchToIFrame();
        $I->seeInField('show_from_date','2900-06-30 03:20:15');
        $I->seeInField('show_to_date','3000-08-10 05:10:30');
        $I->seeCheckboxIsChecked('valid');
        $I->cantSeeCheckboxIsChecked('re_confirmation');
        
        $I->wantTo('edit announcement');
        $I->selectOption('type','Vereinbarung');
        $I->fillField('title_de', 'Cowboy Hats');
        $I->fillField('show_from_date','1900-06-30 03:20:15');
        $I->checkOption('#re_confirmation');
        $I->click('input[value=Speichern]');
        $I->waitForPageLoad(10);
        $I->see('Ankündigung erfolgreich aktualisiert!');
        $I->dontSeeInDatabase('announcement_user', ['announcement_id' => $announcementId[0], 'accept' => '1']);
        
        /*
         * Check edited announcement
         */
        $I->wantTo('check the edited announcement');
        $I->fillField('search', 'Cowboy');
        $I->click('filterButton');
        $I->waitForPageLoad(10);
        $I->see('Cowboy Hats');
        $I->see('Vereinbarung');
        $I->dontSee('Headline2_de');
        $I->loggingOut($I);

        /*
         * Reconfirm announcement
         */
        $I->wantTo('check if announcement will be displayed');
        $I->loggingIn($I);
        $I->seeCurrentUrlEquals('/announcement/display/');
        $I->see('Cowboy Hats');
        $I->see('Text2_de');
        $I->seeElement('#announcementDecline');
        $I->seeElement('#announcementAccept');
        $I->checkOption('#announcementCheckbox');
        $I->click('#announcementAccept');
        $I->waitForPageLoad(10);
        $I->seeInDatabase('announcement_user', ['announcement_id' => $announcementId[0], 'accept' => '1']);
        $I->dontSeeInCurrentUrl('display');
    }
    
    public function delete(AcceptanceTester $I){
        
        $I->wantTo('delete the announcement');
        $I->amOnPage('/announcement/index');
        $I->fillField('#search', 'announcement2_inthefuture');
        $I->click('filterButton');
        $I->see('announcement2_inthefuture');
        $I->click('announcementIndexLinkDelete');
        $I->seeInPopup('Wollen Sie die Ankündigung wirklich löschen?');
        $I->acceptPopup();
        $I->waitForPageLoad(10);
        $I->see('Ankündigung erfolgreich gelöscht!');

        /*
         * Check deleted announcement
         */
        $I->wantTo('check the deleted announcement');
        $I->fillField('search', 'announcement2_inthefuture');
        $I->click('filterButton');
        $I->waitForPageLoad(10);
        $I->see('Keine Einträge gefunden!');
    }

    /*
     * Declining results in deactivated account
     */
    public function declineAgreement(AcceptanceTester $I){

        $I->wantTo('add announcements to db');
        $I->haveInDatabase('announcement', array(
            'name'           => 'announcement6_decline',
            'show_from_date' => '1900-06-30 03:20:15',
            'show_to_date'   => '3000-06-30 03:20:15',
            'type'            => 'agreement',
            'is_active'      => '1',
            'user_id'        => '1',
        ));

        $announcementId = $I->grabColumnFromDatabase('announcement', 'id', array('name' => 'announcement6_decline'));

        //  English message not necessary
        $I->haveInDatabase('announcement_message', array(
            'announcement_id'   => $announcementId[0],
            'language'          => 'de',
            'title'             => 'Headline6_de',
            'message'           => 'Text6_de',
            'user_id'           => '1',
        ));


        $I->loggingOut($I);
        $I->loggingIn($I);
        $I->wantTo('decline agreement');
        $I->seeCurrentUrlEquals('/announcement/display/');
        $I->see('Headline6_de');
        $I->see('Text6_de');
        $I->checkOption('#announcementCheckbox');
        $I->click('#announcementDecline');
        $I->see('Wollen Sie wirklich ablehnen?');
        $I->click('Ja, ablehnen');
        $I->waitForPageLoad(10);
        $I->seeCurrentUrlEquals('/login/login');
        $I->loggingOut($I);
        $I->seeInDatabase('user', ['id' => '1', 'is_active' => '0']);

        /*
         * Activate account and log in again
         */
        $I->wantTo('activate account and log in again');
        $I->updateInDatabase('user', array('is_active' => '1'), array('id' => '1'));
        $I->loggingIn($I);
        $I->seeCurrentUrlEquals('/announcement/display/');
        $I->see('Headline6_de');
        $I->see('Text6_de');
        $I->checkOption('#announcementCheckbox');
        $I->click('#announcementAccept');
        $I->waitForPageLoad(10);
        $I->seeInDatabase('announcement_user', ['announcement_id' => $announcementId[0], 'accept' => '1']);
        $I->dontSeeInCurrentUrl('display');
    }
}