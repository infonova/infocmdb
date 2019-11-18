<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class SchedulerController extends AbstractAppAction
{

    public function init()
    {
        parent::init();
        parent::setTranslatorLocal();
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);
        // action body
        AbstractAppAction::logout();
        exit;
    }

    /**
     * this method should be used to start a specific listener by the given listener name
     *
     * the method is triggered via console. (scheduler)
     */
    public function listenAction()
    {
        $listenerName = $this->_getParam('listener');

        try {
            $listener = Service_Queue_Factory::getListener($listenerName);
            $listener->listen();
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }

        AbstractAppAction::logout();
        exit;
    }

    /**
     * this method should be used to start a queue-processor by the given processor name
     *
     * processors select a not-yet-processed queue items
     *
     * the method is triggered via console. (scheduler)
     */
    public function processAction()
    {
        $processorName = $this->_getParam('processor');
        $processorType = $this->_getParam('type');
        try {
            $processor = Service_Queue_Factory::getProcessor($processorType, $processorName);
            if ($processor) {
                $processor->process();
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }
        AbstractAppAction::logout();
        exit;
    }


    /**
     * this method should be used to start a queue-processor by the given processor name without message check
     *
     * processors select a not-yet-processed queue items
     *
     * the method is triggered via console. (scheduler)
     */
    public function sprocessAction()
    {
        $processorName = $this->_getParam('processor');
        $processorType = $this->_getParam('type');
        try {
            $processor = Service_Queue_Factory::getScheduledProcessor($processorType, $processorName);
            if ($processor) {
                $processor->process();
            }
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }
        AbstractAppAction::logout();
        exit;
    }

    /**
     *
     * destroy all infoCMDB processes. handle with care!
     */
    public function killAction()
    {
        system('killall -e infoCMDB');
        AbstractAppAction::logout();
        exit;
    }

}