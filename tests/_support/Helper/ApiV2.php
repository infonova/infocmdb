<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Util\Fixtures;

class ApiV2 extends \Codeception\Module
{

    public function _before(\Codeception\TestCase $test)
    {
        Phinx::prepareTestEnvironment();
    }

    public function loggingIn($username = 'admin', $password = 'admin', $force=false)
    {
        /** @var \Codeception\Module\REST $rest */
        $rest  = $this->getModule('REST');
        $token = Fixtures::get('auth_token');

        if (empty($token) || $force === true) {
            codecept_debug('logging in...');

            $rest->sendPost('/apiV2/auth/token', array(
                'username' => $username,
                'password' => $password,
            ));
            $rest->seeResponseContainsJson(array(
                'success' => true
            ));

            $rawJson = $rest->grabResponse();
            $json    = json_decode($rawJson);
            $token   = $json->data->token;

            Fixtures::add('auth_token', $token);
        }

        $rest->amBearerAuthenticated($token);
    }

}
