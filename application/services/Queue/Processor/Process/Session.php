<?php

/**
 *
 * cleanup for session entries.
 * should be triggered frequently if in_memory user session storage is used
 *
 *
 */
class Process_Session extends Service_Queue_Processor
{

    public function __construct()
    {
        parent::__construct('session');
    }

    public function process()
    {
        $queueDaoImpl = new Dao_Queue();
    }
}