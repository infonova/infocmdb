<?php

/**
 * @group mail
 */
class NotificationControllerCest extends AbstractApiTest
{

    public function _before(ApiTester $I, $scenario)
    {

    }

    public function sendWithInvalidApiKey(ApiTester $I) {
        $apiKey = 'INVALID_1234';

        $I->sendPOST('/api/notification/notify/default', array(
            'apikey'     => $apiKey,
            'method'    => 'json',
        ));

        $I->seeResponseCodeIs(403);
    }

    public function sendWithPreExistingApiKey(ApiTester $I) {
        $apiKey = 'query_' . date('YmdHis');
        $I->insertApiKey($apiKey);

        $body = 'Send mail via API (Api-Key): ' . date('Y-m-d H:i:s');
        $recipient = \Helper\MailCatcher::getUniqueEmailAddress();

        $I->sendPOST('/api/notification/notify/default', array(
            'apikey'        => $apiKey,
            'body'          => $body,
            'Recipients'    => $recipient,
            'method'        => 'json',
        ));

        $I->seeResponseContainsJson(array(
            'status'    => 'OK',
            'data'      => array(
                'type'      => 'mail',
                'address'   => $recipient,
            ),
        ));
        $I->seeResponseCodeIs(200);
        $I->seeInLastEmailTo($recipient, $body);
    }

    public function sendWithAuthenticatedUser(ApiTester $I) {
        $I->loggingInViaGui();

        $body = 'Send mail via API (Session): ' . date('Y-m-d H:i:s');
        $recipient = \Helper\MailCatcher::getUniqueEmailAddress();

        $I->sendPOST('/api/notification/notify/default', array(
            'body'          => $body,
            'Recipients'    => $recipient,
            'method'        => 'json',
        ));

        $I->seeResponseContainsJson(array(
            'status'    => 'OK',
            'data'      => array(
                'type'      => 'mail',
                'address'   => $recipient,
            ),
        ));
        $I->seeResponseCodeIs(200);
        $I->seeInLastEmailTo($recipient, $body);
    }
}
