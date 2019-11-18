<?php

/**
 *
 * this processor recreates the filesearch index.
 *
 *
 */
class Process_Filesearch extends Service_Queue_Processor
{

    public function __construct()
    {
        parent::__construct('filesearch');
    }

    public function process()
    {
        set_time_limit(10800);
        $this->logger->log('process Filesearch!', Zend_Log::INFO);

        $fileSearch = new Util_Search_File();
        $fileSearch->recreateIndex();

        $this->logger->log('process Filesearch finished!', Zend_Log::INFO);
    }
}