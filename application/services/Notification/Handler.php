<?php

class Notification_Handler
{


    public function sendNotification(Notification_Message $message, array $parameter = array())
    {


        // TODO: typ
        // TODO: gateway
        try {
            $className = 'Notification_Type_' . ucfirst($type);
            $type      = new $className();

            if (!$type) {
                // TODO: exception handling
                return false;
            }

            $type->handle($gateways, $parameter);

            return true;
        } catch (Exception $e) {
            $logger = Zend_Registry::get('Log');
            $logger->log($e, Zend_Log::CRIT);
            return;
        }

    }


    public function retrieveNotification()
    {

    }
}