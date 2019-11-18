<?php

/**
 *
 * This class is used to retrieve listener and scheduler for the processing queue
 *
 *
 *
 */
class Service_Queue_Factory
{

    public static function getListener($listenerName)
    {
        try {
            $className = 'Service_Queue_Listener_' . ucfirst($listenerName);
            $listener  = new $className();

            if (!$listener) {
                // TODO: exception handling
                return false;
            }

            return $listener;
        } catch (Exception $e) {
            $logger = Zend_Registry::get('Log');
            $logger->log($e, Zend_Log::CRIT);
            return false;
        }
    }


    public static function getProcessor($processorType, $processorName)
    {
        try {
            $className = ucfirst($processorType) . '_' . ucfirst($processorName);
            $processor = new $className();

            if (!$processor) {
                // TODO: exception handling
                return false;
            }

            $message = Service_Queue_Handler::get($processor->getQueue());
            if (!$message) {
                return false;
            } else {
                $processor->setMessage($message);
            }


            return $processor;
        } catch (Exception $e) {
            $logger = Zend_Registry::get('Log');
            $logger->log($e, Zend_Log::CRIT);
            return false;
        }
    }


    public static function getScheduledProcessor($processorType, $processorName)
    {
        try {
            $className = ucfirst($processorType) . '_' . ucfirst($processorName);
            $processor = new $className();

            if (!$processor) {
                // TODO: exception handling
                return false;
            }


            return $processor;


        } catch (Exception $e) {
            $logger = Zend_Registry::get('Log');
            $logger->log($e, Zend_Log::CRIT);
            return false;
        }
    }
}