<?php
namespace Helper;

use \Codeception\Util\Fixtures;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Functional extends \Codeception\Module
{
    protected $test;

    public function _before(\Codeception\TestCase $test)
    {
        Phinx::prepareTestEnvironment();
    }

    public function exec($cmd)
    {
        $this->debug("[Exec] " . $cmd);
        $result = shell_exec($cmd);
        $this->debug("    " . $result);

        return $result;
    }

    public function startListener($name) {
        $ret = $this->exec("php " . APPLICATION_PATH . "/../public/index.php /scheduler/listen/listener/" . escapeshellcmd($name) . " 2>&1");
        return $ret;
    }

    public function startProcessor($type, $name) {
        $ret = $this->exec("php " . APPLICATION_PATH . "/../public/index.php /scheduler/process/type/" . escapeshellarg($type) . "/processor/" . escapeshellcmd($name) . " 2>&1");
        return $ret;
    }

    public function grabQueryResult($query, $parameters=array(), $fetchMode=\PDO::FETCH_ASSOC) {
        if(!$this->hasModule('Db')) {
            return false;
        }

        $sth = $this->getModule('Db')->driver->executeQuery($query, $parameters);
        return $sth->fetchAll($fetchMode);
    }

    /*
     * $data Example:
     *  array(
     *      array(
     *          'header_1' => array(
     *              'csv' => 'row-1 value-1'
     *          ),
     *          'header_2' => array(
     *              'csv' => 'row-1 value-2'
     *          ),
     *      ),
     *      array(
     *          'header_1' => array(
     *              'csv' => 'row-2 value-1'
     *          ),
     *          'header_2' => array(
     *              'csv' => 'row-2 value-2'
     *          ),
     *      ),
     *  ),
     */
    public function createCsv($filePath, $data) {
        if(empty($data)) {
            return false;
        }

        $headers = array_keys($data[0]);
        $delimiter = ';';

        $fp = fopen($filePath, 'w');
        fputcsv($fp, $headers, $delimiter);

        foreach($data as $dataElem) {
            $row = array();
            foreach($headers as $header) {
                $row[] = $dataElem[$header]['csv'];
            }

            fputcsv($fp, $row, $delimiter);
        }

        fclose($fp);
    }
}
