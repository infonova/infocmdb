<?php

/**
 *
 * this class is responsible for the file logger and the logrotation
 *
 */
class Plugin_Log_File extends Zend_Log_Writer_Stream
{
    function __construct($file)
    {
        parent::__construct($file, self::rotator($file));
    }

    static function rotator($file)
    {
        if (!file_exists($file)) {
            fopen($file, 'a');
            chmod($file, 0777);
        }

        $mode = 'a+';
        return $mode;
    }
}