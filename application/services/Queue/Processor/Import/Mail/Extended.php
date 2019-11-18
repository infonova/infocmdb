<?php

class Import_Mail_Extended
{

    public static function process($config, $mail, $key, $message, $exceptions)
    {
        $logger = Zend_Registry::get('Log');

        $ciTypeId  = $config[Db_MailImport::CI_TYPE_ID];
        $projectId = $config[Db_MailImport::PROJECT_ID];
        $ciId      = null;

        if ($ciTypeId) {
            $daoCi     = new Dao_Ci();
            $hist      = new Util_Historization();
            $historyId = $hist->createHistory('0', Util_Historization::MESSAGE_IMPORT_MAIL);
            $ciId      = $daoCi->createCi($ciTypeId, null, $historyId);

            if ($projectId && $ciId) {
                // add ci project
                $ciProjectDaoImpl = new Dao_CiProject();
                $ciProjectDaoImpl->insertCiProject($ciId, $projectId, $historyId);
            }
        }


        if (!$ciId) {
            if ($config[Db_MailImport::PROTOCOL] == 'IMAP' && isset($config[Db_MailImport::MOVE_FOLDER])) {
                $mail->moveMessage($key, $config[Db_MailImport::MOVE_FOLDER]);
            } else {
                $mail->removeMessage($key);
            }
            array_push($exceptions, array('exception' => new Exception_MailImport_MailCiTypeNotFound(), 'subject' => $message->subject));
            return $exceptions;
        } else {
            $exceptions = Import_Mail_Util::handleMailContent($config, $mail, $key, $message, $exceptions, $logger, $ciId, $historyId, true);
        }

        return $exceptions;
    }


    public static function handleMessageBody($messageBody, $exceptions, $ciId, $historyId)
    {
        $logger = Zend_Registry::get('Log');
        $logger->log('start parse body for $ciId: ' . $ciId, Zend_Log::CRIT);
        $daoAttribute  = new Dao_Attribute();
        $ciDaoImpl     = new Dao_Ci();
        $triggerUtil   = new Util_Trigger($logger);
        $importDaoImpl = new Dao_Import();

        $newBody   = nl2br($messageBody);
        $lines     = explode('<br />', $newBody);
        $multiline = false;

        foreach ($lines as $line) {
            if (preg_match("/\{\{\{/", $line)) {
                $multiline   = true;
                $lineContent = "";
            }
            if ($multiline == true) {
                $lineContent .= $line;
                if (preg_match("/\}\}\}/", $line)) {
                    $multiline   = false;
                    $lineContent = trim(str_replace(array("{{{", "}}}", "\r"), "", $lineContent));#remove separator and double new lines
                } else {
                    continue;
                }
            } else {
                $lineContent = trim($line);
            }

            if (!$lineContent || empty($lineContent)) {
                continue;
            }

            // check id -> value
            $attName = trim(strstr($lineContent, ':', true));
            $content = trim(substr(strstr($lineContent, ':'), 1));

            if (!$attName || !$content || empty($attName) || empty($content)) {
                continue;
            }


            // check if attribute exists
            try {
                $attribute = $daoAttribute->getAttributeWithTypeByName($attName);

                if (!$attribute) {
                    $logger->log('attribute "' . $attName . '" not found', Zend_Log::CRIT);
                    $logger->log('attribute not found', Zend_Log::CRIT);
                    array_push($exceptions, array('exception' => new Exception_MailImport_MailAttributeNotFound(), 'subject' => $message->subject));
                    continue;
                }

            } catch (Exception $e) {
                $logger->log('attribute "' . $attName . '" not found', Zend_Log::CRIT);
                array_push($exceptions, array('exception' => new Exception_MailImport_MailAttributeNotFound(), 'subject' => $message->subject));
                continue;
            }

            try {
                if (!$attribute['typeName']) {
                    $logger->log('attribute Type not found', Zend_Log::CRIT);
                    continue;
                }

                // insert new content;
                $attributeType = Util_AttributeType_Factory::get($attribute['typeName']);

                $values          = array();
                $values['value'] = $content;
                $ret             = $attributeType->returnFormData($values, $attribute[Db_Attribute::ID]);

                if (!is_null($ret[Db_CiAttribute::VALUE_DEFAULT])) {
                    $val = $importDaoImpl->getDefaultValueIdByName($attribute[Db_Attribute::ID], $ret[Db_CiAttribute::VALUE_DEFAULT]);
                    if ($val)
                        $ret[Db_CiAttribute::VALUE_DEFAULT] = $val[Db_AttributeDefaultValues::ID];
                }

                if (!is_null($ret[Db_CiAttribute::VALUE_DATE])) {
                    $logger->log('value date is: ' . $ret[Db_CiAttribute::VALUE_DATE], Zend_Log::CRIT);
                }

                if ($ret || is_array($ret)) {
                    // insert in ci_attributes
                    $ret[Db_CiAttribute::HISTORY_ID] = $historyId;
                    $ciAttributeId                   = $ciDaoImpl->addCiAttributeArray($ciId, $attribute[Db_Attribute::ID], $ret, '1');

                    $triggerUtil->createAttribute($ciAttributeId, '0');
                }


            } catch (Exception $e) {
                $logger->log('create ci_attribute failed', Zend_Log::ERR);
                $logger->log($e, Zend_Log::WARN);
                array_push($exceptions, array('exception' => new Exception_MailImport_MailCreateCiAttributeFailed(), 'subject' => $message->subject));
                continue;
            }
        }

        return $exceptions;
    }
}