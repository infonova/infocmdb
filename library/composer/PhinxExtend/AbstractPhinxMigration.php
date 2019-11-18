<?php

namespace PhinxExtend;

use Phinx\Config\Config;

abstract class AbstractPhinxMigration extends  \Phinx\Migration\AbstractMigration
{
    public $config;
    public $dbName;

    public function init() {
        if(is_file('phinx.php')) {
            $configFilePath = 'phinx.php';
            $config         = Config::fromPhp($configFilePath);
        } else {
            $configFilePath = 'phinx.yml';
            $config         = Config::fromYaml($configFilePath);
        }

        $options = $this->getInput()->getOptions();
        $databaseEnv = 'production';
        if(isset($options['environment'])) {
            $databaseEnv = $options['environment'];
        } elseif(getenv('APPLICATION_ENV')) {
            $databaseEnv = getenv('APPLICATION_ENV');
        }

        $this->config = $config->getEnvironment($databaseEnv);
        $this->dbName = $this->config['name'];
    }

    public function getCurrentMigrateVersion() {
        $result = $this->fetchRow("SELECT IFNULL(MAX(version), 0) AS version FROM `phinxlog` WHERE `start_time` IS NOT NULL AND `end_time` IS NOT NULL ");
        return $result['version'];
    }

    public function ask($question, array $options=array(), $default='')
    {
        $output = $question;
        if(!empty($options)) {
            $output .= " [".implode('/', $options)."]";
        }

        $output .= ": ";

        if(!empty($default)) {
            $output .= "[".$default."] ";
        }

        echo $output;

        $fr=fopen("php://stdin","r");   // open our file pointer to read from stdin
        $input = fgets($fr,128);        // read a maximum of 128 characters
        $input = rtrim($input);         // trim any trailing spaces.
        fclose ($fr);                   // close the file handle


        if($input == '') {
            return $default;
        }

        if(!empty($options) && !in_array($input, $options)) {
            return $this->ask($question, $default, $options);
        }

        return $input;


    }

    public function hasColumn($database, $table, $column) {
        if($this->fetchRow("SHOW COLUMNS FROM `".$database."`.`".$table."` LIKE '".$column."'")) {
            return true;
        }

        return false;
    }

}