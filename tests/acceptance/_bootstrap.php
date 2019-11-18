<?php
// Here you can initialize variables that will be available to your tests
require_once 'LoginControllerCest.php';
require_once 'AbstractAcceptanceTest.php';

\Codeception\Util\Fixtures::add('auth_user', false);
\Codeception\Util\Fixtures::add('auth_cookie', false);