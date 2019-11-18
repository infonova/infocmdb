<?php

class Process_Reporting extends Service_Queue_Processor
{

    public function __construct()
    {
        parent::__construct('reporting');
    }

    public function process()
    {

        $properties = $this->message->getArgs();
        list($reportingId, $reportingName, $userId) = $properties;
        $this->logger->log($reportingName . ': Process Reporting started!', Zend_Log::INFO);

        try {
            $this->handleReporting($reportingId, $userId);
            Service_Queue_Handler::finalize($this->message->getId());
        } catch (Exception_Reporting_OutputInvalid $e) {
            $this->logger->log($reportingName . ': Process Reporting finished without output!', Zend_Log::WARN);
            $this->logger->log($e, Zend_Log::DEBUG);
            Service_Queue_Handler::finalize($this->message->getId());
        } catch (Exception $e) {
            $this->logger->log($reportingName . ': Process Reporting failed!', Zend_Log::WARN);
            $this->logger->log($e, Zend_Log::ERR);
            // TODO: implement me
            Service_Queue_Handler::failed($this->message->getId());
        }
    }

    // TODO: refactor me!
    private function handleReporting($reportingId, $userId)
    {
        if (!$userId)
            $userId = 0;

        $reportingDaoImpl = new Dao_Reporting();
        $reporting        = $reportingDaoImpl->getReporting($reportingId);


        $input = Process_Reporting_Factory::getInput($reporting[Db_Reporting::INPUT]);
        $input->process($reporting, $userId);

        $output = Process_Reporting_Factory::getOutput($reporting[Db_Reporting::OUTPUT]);
        $output->process($reporting, $input->getAttributes(), $input->getData());

        $transport = Process_Reporting_Factory::getTransport($reporting[Db_Reporting::TRANSPORT]);
        $transport->process($reporting, $output->getFile(), $output->getPath(), $userId);
        // finished??
    }

}