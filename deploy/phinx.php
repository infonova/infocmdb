<?php


$yamlConfig = dirname(__FILE__).'/phinx.yml';
if(file_exists($yamlConfig)) {
    require_once "../library/composer/autoload.php";
    $configFile = file_get_contents($yamlConfig);
    $configArray = \Symfony\Component\Yaml\Yaml::parse($configFile);

    return $configArray;
}

if(!function_exists('parseIniData')) {

    function parseIniData($iniData)
    {
        $iniArray = array();
        foreach ($iniData as $key => $data) {
            $pieces                 = explode(':', $key);
            $thisSection            = trim($pieces[0]);
            $iniArray[$thisSection] = $data;
        }

        return $iniArray;
    }

}

$defaultDatabase = getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production';

$config = array(
    'paths' => array(
        'migrations'    => getcwd().'/migrations',
        'seeds'         => getcwd().'/seeds',
    ),

    'environments' => array(
        'default_migration_table'   => 'phinxlog',
        'default_database'          => $defaultDatabase,

    ),

);

$databaseConfig = parse_ini_file(getcwd().'/../application/configs/database.ini', true);
$databaseConfig = parseIniData($databaseConfig);


$databaseConfigMapping = array(
    // phinx config key => Zend database.ini value
    'host'      => 'database.params.host',
    'name'      => 'database.params.dbname',
    'user'      => 'database.params.root_username',
    'pass'      => 'database.params.root_password',
    'charset'   => 'database.params.charset'
);

$environments = array(
    'production',
    'development',
    'staging',
    'testing'
);

$environmentDefaultValues = array(
    'adapter'   => 'mysql',
    'host'      => '',
    'name'      => '',
    'user'      => '',
    'pass'      => '',
    'port'      => 3306,
    'charset'   => 'utf8',
);

foreach ($environments as $env) {
    foreach ($environmentDefaultValues as $phinxKey => $defaultValue) {
        $config['environments'][$env][$phinxKey] = $defaultValue;

        if(isset($databaseConfigMapping[$phinxKey])) {
            $zendConfigName = $databaseConfigMapping[$phinxKey];

            if (isset($databaseConfig[$env][$zendConfigName])) {
                $config['environments'][$env][$phinxKey] = $databaseConfig[$env][$zendConfigName];
            } elseif (isset($databaseConfig['production'][$zendConfigName])) {
                $config['environments'][$env][$phinxKey] = $databaseConfig['production'][$zendConfigName];
            }
        }

    }
}

return $config;