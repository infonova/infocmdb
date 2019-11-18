<?php

class Import_Mail_Standard
{

    public static function process($config, $mail, $key, $message, $exceptions)
    {
        $logger    = Zend_Registry::get('Log');
        $ciDaoImpl = new Dao_Ci();

        $subject = "";

        $subjectTemp = explode($config[Db_MailImport::CI_FIELD], mb_decode_mimeheader($message->subject));
        if ($subjectTemp[0])
            $subject .= $subjectTemp[0] . " ";

        $sub    = trim($subjectTemp[1]);
        $length = strlen($sub);

        $ciId = "";
        for ($i = 0; $i < $length; $i++) {
            if (is_numeric($sub[$i])) {
                $ciId .= $sub[$i];
            } else {
                $subject .= substr($sub, $i);
                break;
            }
        }

        $hist             = new Util_Historization();
        $ciProjectDaoImpl = new Dao_CiProject();
        $historyId        = $hist->createHistory('0', Util_Historization::MESSAGE_IMPORT_MAIL);

        // check again for empty ci
        if (!$ciId) {

            if (!empty($config[Db_MailImport::CI_TYPE_ID])) {
                $ciId = $ciDaoImpl->createCi($config[Db_MailImport::CI_TYPE_ID], null, $historyId);

                if (!empty($ciId)) {
                    if (!empty($config[Db_MailImport::PROJECT_ID])) {
                        $ciProjectDaoImpl->insertCiProject($ciId, $config[Db_MailImport::PROJECT_ID], $historyId);
                    }
                }
            } else {
                array_push($exceptions, array('exception' => new Exception_MailImport_MailCiTypeNotFound(), 'subject' => $message->subject));
            }
        }

        $logger->log('CIID: ' . $ciId, Zend_log::INFO);

        if (empty($ciId)) {
            array_push($exceptions, array('exception' => new Exception_MailImport_MailCiNotFound(), 'subject' => $message->subject));
        }
        $exceptions = Import_Mail_Util::handleMailContent($config, $mail, $key, $message, $exceptions, $logger, $ciId, $historyId, false);

        return $exceptions;
    }
}