<?php

// Use stubs so we can analyze types from unloaded extensions
if (!extension_loaded('ldap')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/ldap/ldap.php';
}

if (!extension_loaded('mbstring')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/mbstring/mbstring.php';
}
