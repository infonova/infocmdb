<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Util\Fixtures;
use Symfony\Component\Yaml\Yaml;

class Phinx extends \Codeception\Module
{

    public static function prepareTestEnvironment() {
        putenv('APPLICATION_ENV=testing');
        putenv('LANG="en_US.UTF-8"');

        $dbReady = Fixtures::get('db_ready');
        if($dbReady === false) {
            self::resetTestEnvironment();
            Fixtures::add('db_ready', true);
        }
    }

    public static function resetTestEnvironment() {
        self::resetDatabase();
        self::migrateDatabase();
        self::seedDatabase('TestEnvironment');

        Fixtures::add('auth_user', '');
        Fixtures::add('auth_cookie', '');
    }

    public static function resetDatabase() {
        $config         = self::getCodeceptionConfig();
        $dsn            = $config['modules']['config']['Db']['dsn'];
        $username       = $config['modules']['config']['Db']['root_username'];
        $password       = $config['modules']['config']['Db']['root_password'];
        $databaseName   = self::getDsnAttribute('dbname', $dsn);
        $dbh            = new \PDO($dsn, $username, $password);

        codecept_debug('Resetting Database: '.$databaseName."...\n");

        $dbh->exec('DROP DATABASE IF EXISTS '.$databaseName.'_history');
        $dbh->exec('DROP DATABASE IF EXISTS '.$databaseName.'_tables');
        $dbh->exec('DROP DATABASE IF EXISTS '.$databaseName);
        $dbh->exec('CREATE DATABASE IF NOT EXISTS '.$databaseName.' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
        $dbh->exec('SET global wait_timeout=900'); // do not close codeception db connection while running tests
    }

    public static function migrateDatabase() {
        self::exec('cd deploy && sh phinx migrate -e testing');
    }

    public static function seedDatabase($seedClass) {
        self::exec('cd deploy && sh phinx seed:run -e testing -s '.escapeshellarg($seedClass));
    }

    public static function exec($command) {
        codecept_debug($command);
        exec($command, $ouput, $return);
        codecept_debug($ouput);

        return $return;
    }

    public static function getCodeceptionConfig() {
        $configFile = file_get_contents('codeception.yml');
        $config = Yaml::parse($configFile);

        return $config;
    }

    public static function getDsnAttribute($name, $dsn)
    {
        if (preg_match('/' . $name . '=([^;]*)/', $dsn, $match)) {
            return $match[1];
        } else {
            return null;
        }
    }

}
