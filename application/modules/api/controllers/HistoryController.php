<?php
require_once 'BaseController.php';

class Api_HistoryController extends BaseController
{


    public function indexAction()
    {
        $this->_forward('get');
    }

    /**
     * retrieve history id
     */
    public function getAction()
    {
        $ciId    = $this->_getParam('ciid');
        $message = $this->_getParam('message');
        $method  = $this->_getParam('method'); //xml. json, plain

        try {
            $user       = parent::getUserInformation();
            $historyDao = new Dao_History();

            $userId = $user[Db_User::ID];

            if (!$userId)
                $userId = 0;

            $historyId = $historyDao->createHistory($userId, $message);

            $notification           = array();
            $notification['status'] = 'OK';
            $notification['data']   = $historyId;
            $notification           = parent::getReturnValue($notification);

            if ($method && $method == 'plain') {
                $this->logger->log('history ID: ' . $historyId, Zend_Log::DEBUG);
                echo $historyId;
                exit;
            }
            $this->logger->log($notification, Zend_Log::DEBUG);
            $this->getResponse()
                ->setHttpResponseCode($code)
                ->appendBody($notification);

        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            $notification = array('status' => 'error', 'message' => 'unexpected Error occurred.');
            $notification = parent::getReturnValue($notification);

            $this->getResponse()
                ->setHttpResponseCode(500)
                ->appendBody($notification);
        }
    }
}