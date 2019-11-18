<?php
/**
 *  This file is used to redirect ALL incoming requests
 *  to the specific controller classes. It is part of the
 *  Zend framework customization and should NOT be changed.
 */

/*
 * Legacy Instance support to avoid unnecessary load
 * browsers by default try to access favicon which has
 * been redirected to the index script for handling.
 */

if(strpos($_SERVER['REQUEST_URI'], 'favicon.ico') !== false) {
    die();
}

// Define the operating system directory separator \ /
define('DS', DIRECTORY_SEPARATOR);

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . DS . '..' . DS . 'application'));


// Define path to application directory
defined('APPLICATION_DATA')
    || define('APPLICATION_DATA', realpath(dirname(__FILE__) . DS . '..' . DS . 'data'));

// Define path to public directory
defined('APPLICATION_PUBLIC')
    || define('APPLICATION_PUBLIC', realpath(dirname(__FILE__)) . DS);

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Define application URL
defined('APPLICATION_URL')
	|| define('APPLICATION_URL', curPageURL() . '/');

defined('APPLICATION_FOLDER')
    || define('APPLICATION_FOLDER', str_replace('-','',basename(realpath(dirname(__FILE__) . DS . '..' . DS))));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(APPLICATION_PATH . DS . '..' . DS . 'library'),
	realpath(APPLICATION_PATH . DS . '..' . DS . 'library' . DS . 'PHPExcel' . DS . 'Classes'),
    realpath(APPLICATION_PATH . '/../library/composer/diablomedia'),
    get_include_path(),
)));

require_once APPLICATION_PATH . '/../library/composer/autoload.php'; // loading library with composer autoloader

// Check for cached configuration, or create one before passage to Zend_App
// This should speed up bootstrapping by some small margin
$frontendOptions = array(
    'name' => 'File',
    'params' => array(
        'lifetime' => 900,
        'automatic_cleaning_factor' => 0,
        'automatic_serialization' => true,
        'master_files' => array(
            APPLICATION_PATH . DS . 'configs' . DS . 'application.ini',
            APPLICATION_PATH . DS . 'configs' . DS . 'database.ini'
        )
    )
);
$backendOptions = array(
    'name' => 'File',
    'params' => array(
        'cache_dir' => APPLICATION_PATH . DS . '..' . DS . 'data' . DS . 'cache' . DS . 'config',
    )
);
$configCache = Zend_Cache::factory(
    $frontendOptions['name'],
    $backendOptions['name'],
    $frontendOptions['params'],
    $backendOptions['params']
);
$finalConfig = null;
if (!($finalConfig = $configCache->load('configuration_'.APPLICATION_ENV))) {
    $configFiles = array(
        APPLICATION_PATH . DS . 'configs' . DS . 'application.ini',
        APPLICATION_PATH . DS . 'configs' . DS . 'database.ini'
    );
    $masterConfig = null;
    foreach($configFiles as $file) {
        $config = new Zend_Config_Ini($file, APPLICATION_ENV, array('allowModifications'=>true));
        if (is_null($masterConfig)) {
            $masterConfig = $config;
        } else {
            $masterConfig->merge($config);
        }
    }
    $finalConfig = $masterConfig->toArray();
    $configCache->save($finalConfig, 'configuration_'.APPLICATION_ENV);
}

// special handling for console
if (!empty($argv[1])) {
	$finalConfig['isConsole'] = true;
	$_SERVER['REQUEST_URI'] = $argv[1];
}


// handle env variables defined in config
if(isset($finalConfig['env'])) {
    if(isset($finalConfig['env']['var'])) {
        $envVariables = (array) $finalConfig['env']['var'];
        foreach($envVariables as $name => $value) {
            setEnvironmentVariable($name, $value);
        }
    }

    if(isset($finalConfig['env']['var_prefix'])) {
        $envVariables = (array) $finalConfig['env']['var_prefix'];
        foreach($envVariables as $name => $value) {
            setEnvironmentVariable($name, $value, 'prefix');
        }
    }

    if(isset($finalConfig['env']['var_suffix'])) {
        $envVariables = (array) $finalConfig['env']['var_suffix'];
        foreach($envVariables as $name => $value) {
            setEnvironmentVariable($name, $value, 'suffix');
        }
    }
}


// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    $finalConfig
);

$application->bootstrap()
            ->run();


function curPageURL() {
    if (php_sapi_name() != "cli") {
        $pageURL = 'http';
        $host = $_SERVER["HTTP_HOST"];
        if(isset($_ENV['DOCKER_WEB_HOSTNAME']) && $_ENV['DOCKER_WEB_HOSTNAME'] !== 'localhost') {
            $host = $_ENV['DOCKER_WEB_HOSTNAME'];
        }

        // If the CMDB is run behind a reverse proxy providing ssl encryption we need to check HTTP_X_FORWARDED_PROTO
        if (
            (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
            || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https")
        ) {
            $pageURL .= "s";
        }

        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $host . ":" . $_SERVER["SERVER_PORT"];
        } else {
            $pageURL .= $host;
        }

        return $pageURL;
    } else {
        return "";
    }
}

function setEnvironmentVariable ($name, $value, $action = 'set') {

    $valueEnv = getenv($name);
    $valueApache = '';

    if(function_exists('apache_getenv')) {
        $valueApache = apache_getenv($name);
    }

    switch($action) {
        case 'prefix':
            $valueEnv = $value . $valueEnv;
            $valueApache = $value . $valueApache;
            break;
        case 'suffix':
            $valueEnv = $valueEnv . $value;
            $valueApache = $valueApache . $value;
            break;
        case 'set':
        default:
            $valueEnv = $valueApache = $value;
    }

    if(function_exists('apache_setenv')) {
        apache_setenv($name, $valueApache);
    }

    putenv($name . '=' . $valueEnv);
    $_ENV[$name] = $valueEnv;
}
