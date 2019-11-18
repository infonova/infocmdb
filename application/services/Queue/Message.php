<?php

class Service_Queue_Message
{

    const QUEUE_FILE          = 1;
    const QUEUE_MAIL          = 2;
    const QUEUE_CUSTOMIZATION = 3;
    const QUEUE_REPORTING     = 4;
    const QUEUE_UPDATE        = 5;
    const QUEUE_WORKFLOW      = 6;

    const METHOD_ATTRIBUTE = 'attribute';
    const METHOD_INSERT    = 'insert';
    const METHOD_UPDATE    = 'update';
    const METHOD_IMPORT    = 'import';
    const METHOD_RELATION  = 'relation';

    private $id;
    private $queue_id;
    private $args           = "";
    private $timeout;
    private $execution_time = 0;
    private $userId;
    private $priority       = 1000;
    private $status         = Dao_Queue::MESSAGE_IDLE;

    public function __construct($message = null)
    {
        $this->execution_time = date('Y-m-d H:i:s');

        if ($message) {
            $this->id             = $message[Db_QueueMessage::ID];
            $this->queue_id       = $message[Db_QueueMessage::QUEUE_ID];
            $this->args           = $message[Db_QueueMessage::ARGS];
            $this->timeout        = $message[Db_QueueMessage::TIMEOUT];
            $this->status         = $message[Db_QueueMessage::STATUS];
            $this->execution_time = $message[Db_QueueMessage::EXECUTION_TIME];
            $this->priority       = $message[Db_QueueMessage::PRIORITY];
            $this->userId         = $message[Db_QueueMessage::USER_ID];
        }
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setQueueId($id)
    {
        $this->queue_id = $id;
    }

    public function getQueueId()
    {
        return $this->queue_id;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getExecutionTime()
    {
        return $this->execution_time;
    }

    public function setExecutionTime($time)
    {
        $this->execution_time = $time;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setArgs(array $args)
    {
        foreach ($args as $validate) {
            if (is_array($validate)) {
                throw new Exception_Queue_InvalidMessageArgs();
            }
        }


        $dom  = new DomDocument();
        $root = $dom->createElement("arguments");
        foreach ($args as $argtext) {
            $arg = $dom->createElement("argument");
            $arg->appendChild($dom->createTextNode($argtext));
            $root->appendChild($arg);
        }
        $dom->appendChild($root);
        $this->args = $dom->saveXML();
    }

    public function getArgs()
    {
        $args = array();

        if ($this->args) {
            $dom = new DomDocument();
            $dom->loadXML($this->args);
            foreach ($dom->getElementsByTagName("argument") as $node) {
                $args [] = $node->nodeValue;
            }
        }

        return $args;
    }

    public function getXmlArgs()
    {
        return $this->args;
    }

    public function toArray()
    {
        $message                                  = array();
        $message[Db_QueueMessage::ID]             = $this->id;
        $message[Db_QueueMessage::QUEUE_ID]       = $this->queue_id;
        $message[Db_QueueMessage::ARGS]           = $this->args;
        $message[Db_QueueMessage::TIMEOUT]        = $this->timeout;
        $message[Db_QueueMessage::STATUS]         = $this->status;
        $message[Db_QueueMessage::EXECUTION_TIME] = $this->execution_time;
        $message[Db_QueueMessage::PRIORITY]       = $this->priority;
        $message[Db_QueueMessage::USER_ID]        = $this->userId;
        return $message;
    }


    public function debug()
    {
        print_r($this->args);
        exit;
    }
}