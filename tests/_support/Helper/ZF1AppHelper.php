<?php
namespace Helper;

use Codeception\Module\ZF1;
use Codeception\Lib\Connector\ZF1 as ZF1Connector;
use Codeception\Configuration;
use Codeception\TestInterface;
use Zend_Config_Ini;
use Zend_Cache;

class ZF1AppHelper extends ZF1
{
    public function _initialize()
    {
        defined('DS') || define('DS', DIRECTORY_SEPARATOR);

        defined('APPLICATION_ENV') || define('APPLICATION_ENV', $this->config['env']);
        defined('APPLICATION_PATH') || define(
            'APPLICATION_PATH',
            Configuration::projectDir() . $this->config['app_path']
        );
        defined('LIBRARY_PATH') || define('LIBRARY_PATH', Configuration::projectDir() . $this->config['lib_path']);

        defined('APPLICATION_DATA')
        || define('APPLICATION_DATA', Configuration::projectDir() . 'data');

        defined('APPLICATION_PUBLIC')
        || define('APPLICATION_PUBLIC', Configuration::projectDir()  . 'public');

        if (php_sapi_name() != "cli") {
            $pageURL = 'http';

            if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
                $pageURL .= "s";
            }
            $pageURL .= "://";
            if ($_SERVER["SERVER_PORT"] != "80") {
                $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"];
            } else {
                $pageURL .= $_SERVER["SERVER_NAME"];
            }
        } else {
            $pageURL = '';
        }

        // Define application URL
        defined('APPLICATION_URL')
        || define('APPLICATION_URL', $pageURL . '/');

        defined('APPLICATION_FOLDER')
        || define('APPLICATION_FOLDER', Configuration::projectDir());


        // Ensure library/ is on include_path
        set_include_path(
            implode(
                PATH_SEPARATOR,
                [
                    LIBRARY_PATH,
                    LIBRARY_PATH .  DS . 'PHPExcel' . DS . 'Classes',
                    realpath(LIBRARY_PATH . '/composer/diablomedia'),
                    get_include_path(),
                ]
            )
        );

        require_once LIBRARY_PATH . '/composer/autoload.php'; // loading library with composer autoloader

        \Zend_Loader_Autoloader::getInstance();
        
    }


    public function _before(TestInterface $test)
    {
        // Clear Output before each test
        ob_get_clean();
        ob_start();

        $this->client = new ZF1Connector();


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
        if (!($finalConfig = $configCache->load('configuration'))) {
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
            $configCache->save($finalConfig, 'configuration');
        }

        // special handling for console
        if (!empty($argv[1])) {
            $finalConfig['isConsole'] = true;
            $_SERVER['REQUEST_URI'] = $argv[1];
        }


        \Zend_Session::$_unitTestEnabled = true;
        try {
            $this->bootstrap = new \Zend_Application(
                $this->config['env'],
                $finalConfig
            );
        } catch (\Exception $e) {
            throw new ModuleException(__CLASS__, $e->getMessage());
        }
        $this->bootstrap->bootstrap();
        $this->client->setBootstrap($this->bootstrap);

        $this->amOnPage('/login/login');

        $db = $this->bootstrap->getBootstrap()->getResource('db');
        if ($db instanceof \Zend_Db_Adapter_Abstract) {
            $this->db = $db;
            $this->db->getProfiler()->setEnabled(true);
            $this->db->getProfiler()->clear();
        }
    }
}