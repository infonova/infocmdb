<?php

abstract class Service_Queue_Processor
{

    protected $message;
    protected $logger;
    protected $queueName = "default";

    public function __construct($queue)
    {
        $this->queueName = $queue;
        $this->logger    = Zend_Registry::get('Log');
    }

    /**
     * overwrite this method!
     */
    public function process()
    {
        return false;
    }

    public function getQueue()
    {
        return $this->queueName;
    }

    public function setMessage(Service_Queue_Message $message)
    {
        $this->message = $message;
    }
}