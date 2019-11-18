<?php

/**
 *
 * listen mailboxes and imports new mails
 *
 *
 */
class Import_Mail extends Service_Queue_Processor
{


    public function __construct()
    {
        parent::__construct('import_mail');
    }


    public function process()
    {
        // get message properties
        $properties = $this->message->getArgs();
        list($messageId, $host) = $properties;


        try {
            $this->handleImport($messageId);
            Service_Queue_Handler::finalize($this->message->getId());
        } catch (Exception $e) {
            $this->logger->log('Mail Import failed!', Zend_Log::CRIT);
            $this->logger->log($e, Zend_Log::ERR);
            // TODO: implement me
            Service_Queue_Handler::failed($this->message->getId());
        }

    }


    private function handleImport($mailImportId)
    {
        $mailDaoImpl  = new Dao_Mail();
        $mailsToWatch = $mailDaoImpl->getMailImportConfig($mailImportId);


        foreach ($mailsToWatch as $config) {

            if ($config[Db_MailImport::PROTOCOL] == 'POP3') {


                $mail = new Zend_Mail_Storage_Pop3(array('host'     => $config[Db_MailImport::HOST],
                                                         'port'     => $config[Db_MailImport::PORT],
                                                         'user'     => $config[Db_MailImport::USER],
                                                         'password' => $config[Db_MailImport::PASSWORD],
                                                         'ssl'      => $config[Db_MailImport::SSL],
                ));

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

            $this->logger->log($mail->countMessages() . " Nachrichten gefunden", Zend_Log::DEBUG);
            $exceptions = array();

            foreach ($mail as $key => $message) {
                try {
                    if ($config[Db_MailImport::IS_EXTENDED]) {
                        $exceptions = Import_Mail_Extended::process($config, $mail, $key, $message, $exceptions);
                    } else {
                        $exceptions = Import_Mail_Standard::process($config, $mail, $key, $message, $exceptions);
                    }

                } catch (Exception $e) {
                    $this->logger->log($e, Zend_Log::EMERG);
                    if ($config[Db_MailImport::PROTOCOL] == 'IMAP' && isset($config[Db_MailImport::MOVE_FOLDER])) {
                        $mail->moveMessage($key, $config[Db_MailImport::MOVE_FOLDER]);
                    } else {
                        $mail->removeMessage($key);
                    }

                    array_push($exceptions, array('exception' => $e, 'subject' => $message->subject));
                }
            }

            $this->logger->log('Mailimport finished with ' . count($exceptions) . ' errors', Zend_Log::ERR);
            if (count($exceptions) > 0) {
                $mail->removeMessage($key);
                $this->logger->log('Mailimport finished with errors!', Zend_Log::ERR);

                /*
                $recieverList = $mailDaoImpl->getImportMailNotification($config[Db_MailImport::ID]);
                
                $reciever = array(); // TODO: where to read from?
                foreach($recieverList as $r) {
                    if($r[Db_Notification::TYPE] == 'pm') {
                        array_push($reciever, array('type' => 'pm', 'address' => $r[Db_Notification::ADDRESS]));
                    }
                }
                $gateways = array(Notification_Gateway_Pm); // TODO: put to config?
                
                foreach($exceptions as $exception) {
                    $this->logger->log($exception, Zend_Log::ERR);
                    
                    $subject = "Mailimport ERROR!";
                    $body = "Mailimport Exception occurred! \r\n
                    \r\n
                    SUBJECT: \r\n
                    ".$exception['subject'];
                    
                    $message = new Notification_Message_Exception();
                    $message->setException($exception['exception']);
                    $message->setBody($body);
                    $message->setSubject($subject);
                    $message->setGateways($gateways);
                    $message->setReciever($reciever);
                    $message->send();
                }
                
                */
            }

        }
    }
}