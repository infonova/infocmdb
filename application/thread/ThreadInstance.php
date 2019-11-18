<?php
//set_time_limit (0);

require "ThreadUtility.php";

class ThreadInstance
{
    var $stdin;
    var $stdout;
    var $db;
    var $log;

    function setup()
    {
        $this->stdin  = fopen("php://stdin", "r");
        $this->stderr = fopen("php://stderr", "w");
        stream_set_blocking($this->stdin, false);

        $options = new Zend_Config_Ini(APPLICATION_PATH . '/configs/database.ini', APPLICATION_ENV);

        $this->db = Zend_Db::factory($options->database->adapter, $options->database->params);
        $this->db->getConnection();

        $writer    = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/data.log');
        $this->log = new Zend_Log($writer);
    }

    function getCommand()
    {
        try {
            return $this->getLine(true);
        } catch (Exception $e) {
            $this->log->log($e, Zend_Log::ERR);
        }
    }

    function response($status, $data)
    {
        response($status, $data);
    }

    function getLine($wait = false)
    {
        $this->log->log('[START]listening for command ', Zend_Log::ERR);
        if ($wait) {
            $buffer = "";
            while (!strlen($buffer)) {
                $buffer .= fgets($this->stdin, 1024);
                usleep(300);

                if (strlen($buffer))
                    $this->log->log('command: ' . $buffer, Zend_Log::ERR);
            }
        } else {
            $buffer = fgets($this->stdin, 1024);
        }
        return trim($buffer);
    }

    function debug($text)
    {
        fwrite($this->stderr, $text);
    }
}