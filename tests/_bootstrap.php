<?php
// This is global bootstrap for autoloading
require_once  __DIR__.'/_support/Extension/TeamCity.php';
require_once  __DIR__.'/_support/Util/Email.php';

\Codeception\Util\Fixtures::add('db_ready', false);