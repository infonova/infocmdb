<?php

// Use stubs so we can analyze types from unloaded extensions
if (!extension_loaded('bz2')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/bz2/bz2.php';
}

if (!extension_loaded('ctype')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/ctype/ctype.php';
}

if (!extension_loaded('iconv')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/iconv/iconv.php';
}

if (!extension_loaded('mbstring')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/mbstring/mbstring.php';
}

if (!extension_loaded('mcrypt')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/mcrypt/mcrypt.php';
}

if (!extension_loaded('openssl')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/openssl/openssl.php';
} else {
    // Only defined when php/openssl compiled with MD2 support
    if (!defined('OPENSSL_ALGO_MD2')) {
        define('OPENSSL_ALGO_MD2', 4);
    }
}

if (!extension_loaded('rar')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/rar/rar.php';
}

if (!extension_loaded('zip')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/zip/zip.php';
}

if (!extension_loaded('zlib')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/zlib/zlib.php';
}
