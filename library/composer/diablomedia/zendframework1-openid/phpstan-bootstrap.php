<?php

// Use stubs so we can analyze types from unloaded extensions
if (!extension_loaded('bcmath')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/bcmath/bcmath.php';
}

if (!extension_loaded('gmp')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/gmp/gmp.php';
}

if (!extension_loaded('hash')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/hash/hash.php';
}
// The hash extension may emulate the mhash extension if PHP is configured with --with-mhash
// otherwise the mhash* functions/constants won't exist
if (!function_exists('mhash')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/mhash/mhash.php';
}

if (!extension_loaded('mbstring')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/mbstring/mbstring.php';
}

if (!extension_loaded('openssl')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/openssl/openssl.php';
}
