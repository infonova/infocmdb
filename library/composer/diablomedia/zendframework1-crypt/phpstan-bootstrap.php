<?php

if (!extension_loaded('gmp')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/gmp/gmp.php';
}

if (!extension_loaded('mcrypt')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/mcrypt/mcrypt.php';
}

if (!extension_loaded('hash')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/hash/hash.php';
}
// The hash extension may emulate the mhash extension if PHP is configured with --with-mhash
// otherwise the mhash* functions/constants won't exist
if (!function_exists('mhash')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/mhash/mhash.php';
}

if (!extension_loaded('openssl')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/openssl/openssl.php';
} else {
    // Only defined when php/openssl compiled with MD2 support
    if (!defined('OPENSSL_ALGO_MD2')) {
        define('OPENSSL_ALGO_MD2', 4);
    }
}
