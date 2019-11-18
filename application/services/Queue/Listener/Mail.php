<?php

class Service_Queue_Listener_Mail implements Service_Queue_Listener
{

    public function listen()
    {
        $logger      = Zend_Registry::get('Log');
        $mailDaoImpl = new Dao_Mail();
        $list        = $mailDaoImpl->getMailImportsForCronjob();

        foreach ($list as $mail) {
            $cronjob = explode(' ', $mail[Db_MailImport::EXECUTION_TIME]);

            $data                      = array();
            $data[Db_Cron::TYPE]       = 'mailimport';
            $data[Db_Cron::MAPPING_ID] = $mail[Db_MailImport::ID];
            $data[Db_Cron::VAR_DUMP]   = time();

            if (!$mail['cronId']) {
                // insert instead of update
                $mailDaoImpl->insertMailImportsForCronjob($data);
                try {
                    $this->insertQueueMessage($mail[Db_MailImport::ID], $mail[Db_MailImport::HOST], $logger);
                } catch (Exception $e) {
                    $logger->log($e, Zend_Log::ERR);
                    // TODO
                }
            } else if (Service_Queue_Cron::checkExecutionTime($mail[Db_Cron::LAST_EXECUTION], $cronjob)) {
                $data[Db_Cron::ID] = $mail['cronId'];
                $mailDaoImpl->updateMailImportsForCronjob($data, $mail['cronId']);

                try {
                    $this->insertQueueMessage($mail[Db_MailImport::ID], $mail[Db_MailImport::HOST], $logger);
                } catch (Exception $e) {
                    $logger->log($e, Zend_Log::ERR);
                    // TODO
                }
            }
        }

    }

    private function insertQueueMessage($mailImportId, $host, $logger)
    {
        $queueDaoImpl = new Dao_Queue();
        $messages     = $queueDaoImpl->searchActiveMessages('import_mail', $host);

        if ($messages && $messages['cnt'] > 0) {
            // do nothing. message already in queue
        } else {
            // open connection and check mails
            $mailDaoImpl  = new Dao_Mail();
            $mailsToWatch = $mailDaoImpl->getMailImportConfig($mailImportId);

            // can only be one!
            foreach ($mailsToWatch as $config) {
                if ($config[Db_MailImport::PROTOCOL] == 'POP3') {


                    $mail = new Zend_Mail_Storage_Pop3(array('host'     => $config[Db_MailImport::HOST],
                                                             'port'     => $config[Db_MailImport::PORT],
                                                             'user'     => $config[Db_MailImport::USER],
                                                             'password' => $config[Db_MailImport::PASSWORD],
                                                             'ssl'      => $config[Db_MailImport::SSL]));

                } elseif ($config[Db_MailImport::PROTOCOL] == 'IMAP') {

                    $mail = new Zend_Mail_Storage_Imap(array('host'     => $config[Db_MailImport::HOST],
                                                             'port'     => $config[Db_MailImport::PORT],
                                                             'user'     => $config[Db_MailImport::USER],
                                                             'password' => $config[Db_MailImport::PASSWORD],
                                                             'ssl'      => $config[Db_MailImport::SSL],
                                                             'folder'   => $config[Db_MailImport::INBOX_FOLDER] ?? 'INBOX',
                    ));

                } else {

                    $this->logger->log("Protocol Error", Zend_Log::DEBUG);

                }
                if ($mail->countMessages() > 0) {
                    $logger->log("mail_import found " . $mail->countMessages() . ' new mails', Zend_Log::INFO);
                    $args                 = array();
                    $args['mailImportId'] = $mailImportId;
                    $args['host']         = $host;

                    $message = new Service_Queue_Message();
                    $message->setQueueId(2); // TODO: replace me!
                    $message->setArgs($args);
                    Service_Queue_Handler::add($message);
                    break;
                } else {
                    // TODO: handle empty list
                }
            }
            // return
        }
    }
}