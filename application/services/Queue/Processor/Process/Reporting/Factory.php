<?php

class Process_Reporting_Factory
{

    public static function getInput($input)
    {
        $className = 'Process_Reporting_Input_' . ucfirst($input);
        return self::getHandling($className);
    }

    public static function getOutput($output)
    {
        $className = 'Process_Reporting_Output_' . ucfirst($output);
        return self::getHandling($className);
    }

    public static function getTransport($transport)
    {
        $className = 'Process_Reporting_Transport_' . ucfirst($transport);
        return self::getHandling($className);
    }


    private static function getHandling($className)
    {
        try {
            $class = new $className();

            if (!$class) {
                return false;
            }

            return $class;
        } catch (Exception $e) {
            $logger = Zend_Registry::get('Log');
            $logger->log($e, Zend_Log::CRIT);
            return false;
        }
    }
}