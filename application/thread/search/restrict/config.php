<?php
require_once 'Zend/Db.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Db/Table/Abstract.php';
require_once 'Zend/Log.php';
require_once 'Zend/Log/Writer/Stream.php';

$vars            = getopt("a:b:c:");
$applicationPath = $vars['a'];
$search          = $vars['b'];
$sessionId       = $vars['c'];
$timeout         = time() + 30;

defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', $applicationPath);