<?php

// Use stubs so we can analyze types from unloaded extensions
if (!extension_loaded('apc')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/apc/apc.php';
}

if (!extension_loaded('igbinary')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/igbinary/igbinary.php';
}

if (!extension_loaded('memcache')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/memcache/memcache.php';
}

if (!extension_loaded('memcached')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/memcached/memcached.php';
}

if (!extension_loaded('wincache')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/wincache/wincache.php';
}

if (!extension_loaded('xcache')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/xcache/xcache.php';
}

if (!function_exists('output_cache_get')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/zend/zend.php';
}

if (!extension_loaded('zendcache')) {
    require_once __DIR__ . '/vendor/jetbrains/phpstorm-stubs/ZendCache/ZendCache.php';
}
