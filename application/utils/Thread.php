<?php
require APPLICATION_PATH . '/thread/ThreadUtility.php';

/**
 *
 * this class simulates ThreadHandling in php by using pipes and new process instances.
 * DOES NOT WORK ON WINDOWS
 *
 *
 *
 */
class Util_Thread
{
    public $pref;
    public $pipes;
    public $pid;
    public $stdout;
    public $timeout = 300;

    public function __construct()
    {
        $this->pref    = 0;
        $this->stdout  = "";
        $this->pipes   = (array)null;
        $this->timeout = 300;
    }

    public static function create($url, $command = null, $spawnChild = true)
    {
        if (!$command)
            $command = 'php -q';

        $t          = new Util_Thread;
        $descriptor = array(
            0 => array("pipe", "r"), // stdin
            1 => array("pipe", "w"), // stdout
            2 => array("pipe", "w") // stderr
        );

        if ($spawnChild)
            $url .= " &";

        $t->pref = proc_open("$command $url", $descriptor, $t->pipes);

        stream_set_blocking($t->pipes[1], 0);
        stream_set_blocking($t->pipes[2], 0);

        usleep($t->timeout);
        return $t;
    }

    public function isActive()
    {
        $this->stdout .= $this->listen();
        $f            = stream_get_meta_data($this->pipes[1]);
        return !$f["eof"];
    }

    public function isResponding()
    {
        $buffer = $this->listen();
        $this->tell("ping");
        usleep($this->timeout);
        $answer = processresponse($this->listen());
        return $answer["status"] == "ok";
    }

    public function close()
    {
        $this->tell("quit");
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        $r          = proc_close($this->pref);
        $this->pref = null;
        return $r;
    }

    public function tell($thought, $params = null)
    {
        fwrite($this->pipes[0], $thought . "\n");
        if (is_array($params)) {
            foreach ($params as $param) {
                fwrite($this->pipes[0], $param . "\n");
            }
        }
        usleep($this->timeout);
    }

    public function tellAndWaitForResponse($thought, $params = null)
    {
        $this->tell($thought, $params);
        $response = "";
        do {
            $response = $this->listen();
        } while ($response == "");
        return processresponse($response);
    }

    public function listen()
    {
        $buffer       = $this->stdout;
        $this->stdout = "";
        while ($r = fgets($this->pipes[1], 1024)) {
            $buffer .= $r;
        }
        return $buffer;
    }

    public function getError()
    {
        $buffer = "";
        while ($r = fgets($this->pipes[2], 1024)) {
            $buffer .= $r;
        }
        return $buffer;
    }
}