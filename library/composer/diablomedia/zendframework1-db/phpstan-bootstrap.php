<?php

// Use stubs so we can analyze types from unloaded extensions
if (!extension_loaded('ibm_db2')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/ibm_db2/ibm_db2.php';
}

if (!extension_loaded('mysql')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/mysql/mysql.php';
}

if (!extension_loaded('oci8')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/oci8/oci8.php';
}

if (!extension_loaded('sqlite')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/SQLite/SQLite.php';
}

if (!extension_loaded('sqlsrv')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/sqlsrv/sqlsrv.php';
}
