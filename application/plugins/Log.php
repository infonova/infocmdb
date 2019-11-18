<?php

/**
 * Plugin_Log
 *
 * Logger setup. Properties retrieved from the application.ini file
 *
 * Logger is stored in the ZendRegistry and can be accessed with the Log" keyword
 *
 * @Exception is thrown if read/write persmissions are missing for the data/logs directory
 *
 */
class Plugin_Log extends Zend_Controller_Plugin_Abstract
{


    /**
     * preDispatch
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $writerLog = new Plugin_Log_File(APPLICATION_PATH . '/../data/logs/application.log');
        $writerOutput = new Zend_Log_Writer_Stream('php://stdout');
        $log    = new Util_Log();
      
        $log->addWriter($writerLog);
        $log->addWriter($writerOutput);

        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();
        $priority  = Zend_Log::INFO;
        if ($options['logging']['filter']) {
            $priority = (int)$options['logging']['filter'];
            if (!$priority) {
                $priority = Zend_Log::INFO;
            }
        }

        $filter = new Zend_Log_Filter_Priority($priority);
        $log->addFilter($filter);

        Zend_Registry::set('Log', $log);
    }
}