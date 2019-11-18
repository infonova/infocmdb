<?php

class Util_Log extends Zend_Log
{


    /**
     * Log a message at a priority
     *
     * @param  string  $message  Message to log
     * @param  integer $priority Priority of message
     * @param  mixed   $extras   Extra information to log in event
     * @throws Zend_Log_Exception
     */
    public function log($message, $priority = null, $extras = null)
    {
        if (is_null($priority)) {
            $priority = Zend_Log::INFO;
        }

        parent::log($message, $priority, $extras);
    }


    /**
     * Log a formatted message at a priority
     *
     * @param  string  $message  Message to log
     * @param  integer $priority Priority of message
     * @param  mixed   ...$args  parameters for format string
     * @throws Zend_Log_Exception
     */
    public function logf($format, $priority = null, ... $args)
    {
        $message = vsprintf($format, $args);

        self::log($message, $priority);
    }


}