<?php

class Service_Queue_Listener_Reporting implements Service_Queue_Listener
{

    public function listen()
    {
        $logger           = Zend_Registry::get('Log');
        $reportingDaoImpl = new Dao_Reporting();
        $list             = $reportingDaoImpl->getReportingForCronjob();

        foreach ($list as $reporting) {
            $cronjob = explode(' ', $reporting[Db_Reporting::EXECUTION_TIME]);

            $data                      = array();
            $data[Db_Cron::TYPE]       = 'reporting';
            $data[Db_Cron::MAPPING_ID] = $reporting[Db_Reporting::ID];
            $data[Db_Cron::VAR_DUMP]   = time();


            if (!$reporting['cronId']) {
                // insert instead of update
                $lastExecutionTime = date('Y-m-d h:i:s', 123);
            } else {
                $lastExecutionTime = $reporting[Db_Cron::LAST_EXECUTION];
            }


            if (Service_Queue_Cron::checkExecutionTime($lastExecutionTime, $cronjob)) {
                $data[Db_Cron::ID] = $reporting['cronId'];

                try {
                    if ($reporting['cronId']) {
                        $reportingDaoImpl->updateReportingImportsForCronjob($data, $reporting['cronId']);
                    } else {
                        $reportingDaoImpl->insertReportingImportsForCronjob($data);
                    }
                    $this->insertQueueMessage($reporting);
                } catch (Exception $e) {
                    $logger->log($e, Zend_Log::ERR);
                    // TODO
                }
            }

        }
    }


    private function insertQueueMessage($reporting)
    {
        $queueDaoImpl = new Dao_Queue();
        $messages     = $queueDaoImpl->searchActiveMessagesForReporting('reporting', $reporting[Db_Reporting::NAME]);

        if ($messages['cnt'] > 0) {
            // do nothing. message already in queue
        } else {
            $args                = array();
            $args['reportingId'] = $reporting[Db_Reporting::ID];
            $args['name']        = $reporting[Db_Reporting::NAME];

            $message = new Service_Queue_Message();
            $message->setQueueId(4); // TODO: replace me!
            $message->setArgs($args);
            Service_Queue_Handler::add($message);
        }
    }

}