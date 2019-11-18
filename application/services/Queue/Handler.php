<?php

// used to retrieve/insert data into queue

class Service_Queue_Handler
{


    /**
     * add a message to a queue
     */
    public static function add(Service_Queue_Message $message)
    {
        $queueDaoImpl = new Dao_Queue();
        // check if already queued
        $queue = $queueDaoImpl->getQueue($message->getQueueId());

        $args = $message->getArgs(); // TODO: find a better way to do this!!
        if ($message->getQueueId() == Service_Queue_Message::QUEUE_FILE) {
            $historyId = null;

            if ($args[3])
                $historyId = $args[3];
            $curQueue = $queueDaoImpl->searchActiveMessagesForFileimport($queue[Db_Queue::NAME], $args[0], $args[2], $historyId);
        } else if ($message->getQueueId() == Service_Queue_Message::QUEUE_REPORTING) {
            $curQueue = $queueDaoImpl->searchActiveMessagesForReporting($queue[Db_Queue::NAME], $args[0]);
        } else if ($message->getQueueId() == Service_Queue_Message::QUEUE_WORKFLOW) {
            // workflow don't need queue check!
            $curQueue        = array();
            $curQueue['cnt'] = 0;
        } else {
            $curQueue = $queueDaoImpl->searchActiveMessages($queue[Db_Queue::NAME], $args[0]);
        }

        if ($curQueue['cnt'] > 0) {
            // message already queued!!
            //throw new Exception_Queue_AlreadyQueued();//log file full of spam
            return null;
        } else {
            if ($message)
                $id = $queueDaoImpl->insertMessage($message);

            return $id;
        }

    }

    /**
     * retrieves a single queued message by the given parameter
     *
     * select for update and set status to 'in_progress'
     */
    public static function get($queueName)
    {
        if ($queueName == 'cleanup') {
            return new Service_Queue_Message(array());
        }

        $queueDaoImpl = new Dao_Queue();
        $message      = $queueDaoImpl->getIdleMessage($queueName);

        if (!$message || !$message[Db_QueueMessage::ID]) {
            return null;
        } else {
            $config  = new Zend_Config_Ini(APPLICATION_PATH . '/configs/queue.ini', APPLICATION_ENV);
            $timeout = $config->queue->message->timeout;
            if ($timeout)
                $timeout = time() + $timeout;
            $queueDaoImpl->setMessageStatus($message[Db_QueueMessage::ID], Dao_Queue::MESSAGE_IN_PROGRESS, $timeout);

            return new Service_Queue_Message($message);
        }
    }


    /**
     * lookup message queue for specific messages
     */
    public static function lookup($queueName)
    {

    }

    /**
     * set message status to 'completed'
     */
    public static function finalize($messageId)
    {
        $queueDaoImpl = new Dao_Queue();
        $queueDaoImpl->setMessageStatus($messageId, 'completed');
    }


    /**
     * set message status to 'failed'
     */
    public static function failed($messageId)
    {
        $queueDaoImpl = new Dao_Queue();
        $queueDaoImpl->setMessageStatus($messageId, 'failed');
    }


    /**
     * reset message status to 'idle'
     */
    public static function resetStatus($messageId)
    {
        // TODO: do we need this method?
        // Ren�W: file.php lockfile check
        $queueDaoImpl = new Dao_Queue();
        $queueDaoImpl->setMessageStatus($messageId, 'idle', null, 'DATE_ADD(NOW(), INTERVAL 1 MINUTE)');
    }
}