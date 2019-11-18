<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
    protected $isLoggedInViaGui = false;

    public function _before(\Codeception\TestCase $test)
    {
        Phinx::prepareTestEnvironment();
    }

    public function _after(\Codeception\TestCase $test)
    {
        // logout after test
        if($this->isLoggedInViaGui === true) {
            $this->getRestModule()->sendGET('/login/logout');
        }
    }

    /**
     * @return \Codeception\Module\REST
     */
    public function getRestModule() {
        return $this->getModule('REST');
    }

    public function loggingInViaGui($username='admin', $password='admin') {
        $rest = $this->getRestModule();

        $rest->sendPost('/login/login', array(
            'username' => $username,
            'password' => $password,
        ));
        $rest->seeResponseContainsJson(array(
            'success' => true
        ));

        $this->isLoggedInViaGui = true;
    }

    public function insertApiKey($apiKey=null, $userId=1, $validTo=null) {
        if($apiKey === null) {
            $apiKey = date('YmdHis') . '_' . $userId;
        }

        $this->getModule('Db')
            ->haveInDatabase('api_session', array(
                'apikey' => $apiKey,
                'user_id' => 3, // author
                'valid_from'  => date('Y-m-d H:i:s'),
                'valid_to'  => time() + (60 * 60 * 2), // 2 hours
            ));

        return $apiKey;
    }

}
