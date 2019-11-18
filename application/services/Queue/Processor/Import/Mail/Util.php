<?php

class Import_Mail_Util
{

    const rfc2047header        = '/=\?([^ ?]+)\?([BQbq])\?([^ ?]+)\?=/';
    const rfc2047header_spaces = '/(=\?[^ ?]+\?[BQbq]\?[^ ?]+\?=)\s+(=\?[^ ?]+\?[BQbq]\?[^ ?]+\?=)/';

    public static function handleMailContent($config, $mail, $key, $message, $exceptions, $logger, $ciId, $historyId, $isExtended = false)
    {

        $messageBody   = null;
        $attachments   = array();
        $mailAddress   = $message->from;
        $bodyExtension = "txt";

        // Check for attachment
        if ($message->isMultipart()) {
            $foundBody = false;
            foreach (new RecursiveIteratorIterator($message) as $part) {
                try {

                    $content = strtok($part->contentType, ';');
                    if ($content == 'text/plain') {
                        try {
                            $encoding = $part->getHeader('content-transfer-encoding');
                        } catch (Exception $e) {
                            $encoding = "base64";
                        }

                        if ($foundBody) {
                            $fileName = self::getAttachmentDefinition($part, $logger);

                            $attachment = $part->getContent();
                            $attachment = Import_Mail_Util::_contentDecoder($encoding, $attachment);

                            $attachments[] = array(
                                'file_name' => $fileName,
                                'content'   => $attachment,
                                'type'      => 'txt');
                        } else {
                            $messageBody = $part->getContent();
                            $messageBody = Import_Mail_Util::_contentDecoder($encoding, $messageBody);
                            $foundBody   = true;
                        }
                    } else if ($content == 'text/html') {
                        try {
                            $encoding = $part->getHeader('content-transfer-encoding');
                        } catch (Exception $e) {
                            $encoding = "quoted-printable";
                        }

                        if ($foundBody) {


                            $fileName = self::getAttachmentDefinition($part, $logger);

                            $attachment = $part->getContent();
                            $attachment = Import_Mail_Util::_contentDecoder($encoding, $attachment);

                            $attachments[] = array(
                                'file_name' => $fileName,
                                'content'   => $attachment,
                                'type'      => 'txt');
                        } else {
                            $messageBody = $part->getContent();
                            $messageBody = Import_Mail_Util::_contentDecoder($encoding, $messageBody);

                            require_once('Html2Text.php');
                            $h2t           = new \Html2Text\Html2Text($messageBody);
                            $messageBody   = $h2t->getText();
                            $bodyExtension = "txt";
                            $foundBody     = true;
                        }
                    } else {
                        $fileName = self::getAttachmentDefinition($part, $logger);

                        $attachment    = $part->getContent();
                        $attachments[] = array(
                            'file_name' => $fileName,
                            'content'   => $attachment,
                            'type'      => 'file');

                    }
                } catch (Zend_Mail_Exception $e) {
                    $logger->log($e, Zend_Log::CRIT);
                }
            }

            $logger->log('found ' . count($attachments) . ' attachments', Zend_Log::INFO);
        } else {
            // not attachment?
            try {
                $encoding = $message->getHeader('content-transfer-encoding');
            } catch (Exception $e) {
                $encoding = "base64";
            }

            $messageBody = $message->getContent();
            $messageBody = Import_Mail_Util::_contentDecoder($encoding, $messageBody);
        }

        $uploadConfig   = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $useDefaultPath = $uploadConfig->file->upload->path->default;
        $defaultFolder  = $uploadConfig->file->upload->path->folder;

        $path = "";
        if ($useDefaultPath) {
            $path = APPLICATION_PUBLIC .'/'. $defaultFolder;
        } else {
            $path = $uploadConfig->file->upload->path->custom;
        }

        $enabled     = $uploadConfig->file->upload->mail->enabled;
        $folder      = $uploadConfig->file->upload->mail->folder;
        $maxfilesize = $uploadConfig->file->upload->mail->maxfilesize;

        // setting default value
        if (!$enabled) {
            $enabled = false;
        }
        if (!$folder) {
            $folder = "attachment";
        }
        if (!$maxfilesize) {
            $maxfilesize = 52428800;
        }

        $folder = $folder .'/'. $ciId;

        if (!$enabled) {
            // FIXME: find a better way
            array_push($exceptions, array('exception' => new Exception_File_FileUploadNotEnabled()));
        }

        if (!is_dir($path . $folder)) {
            @mkdir($path . $folder, 0777);
            chmod($path . $folder, 0777);
        }

        $ciDaoImpl = new Dao_Ci();
        if (!$historyId) {
            $historizationUtil = new Util_Historization();
            $historyId         = $historizationUtil->createHistory('0', Util_Historization::MESSAGE_IMPORT_MAIL);
        }

        $attachmentPart = 0;


        if ($isExtended) {

            if (!$messageBody) {
                array_push($exceptions, array('exception' => new Exception_File_FileUploadNotEnabled()));
            } else {
                // TODO: extended handling
                $exceptions = Import_Mail_Extended::handleMessageBody($messageBody, $exceptions, $ciId, $historyId);
            }


        } else if ($config[Db_MailImport::IS_ATTACH_BODY] && $messageBody) {
            // attach body TODO
            $date = date("YmdHms\_");

            // rename file
            $newFilename = $date . '_' . $attachmentPart . '.' . $bodyExtension;
            $attachmentPart++;
            //$newFilename = Import_Mail_Util::decode($newFilename);

            $messageSubject = $string = utf8_encode($message->subject);

            $f = fopen($path . $folder .'/'. $newFilename, "w");

            fwrite($f, $messageBody);
            fclose($f);

            $data                             = array();
            $data[Db_CiAttribute::HISTORY_ID] = $historyId;
            $data[Db_CiAttribute::VALUE_TEXT] = $newFilename;
            $data[Db_CiAttribute::NOTE]       = $messageSubject;

            $ciAttributeId = $ciDaoImpl->addCiAttributeArray($ciId, $config[Db_MailImport::BODY_ATTRIBUTE_ID], $data, '0');
        }

        if ($attachments)
            foreach ($attachments as $attachment) {
                try {
                    $filename = $attachment['file_name'];
                    $logger->log('add attachment with name: ' . $filename, Zend_Log::INFO);
                    if ($attachment['content'] && $filename) {
                        $date = date("YmdHms\_");

                        if (!$filename)
                            $filename = 'attachment_' . $attachmentPart;

                        // generate prefix for the uploaded file
                        $path_info = pathinfo($filename);
                        $ext       = $path_info['extension'];

                        $ext = str_replace('"', '', $ext);
                        $ext = str_replace("'", '', $ext);

                        // rename file
                        $newFilename = $date . '_' . $attachmentPart . '.' . $ext;
                        $attachmentPart++;
                        //$newFilename = Import_Mail_Util::decode($newFilename);

                        $logger->log('add attachment with new name: ' . $newFilename, Zend_Log::INFO);

                        $fh = fopen($path . $folder .'/'. $newFilename, 'w');

                        if ($attachment['type'] == 'txt') {
                            fwrite($fh, $attachment['content']);
                        } else {
                            fwrite($fh, base64_decode($attachment['content']));
                        }
                        fclose($fh);


                        $data                             = array();
                        $data[Db_CiAttribute::HISTORY_ID] = $historyId;
                        $data[Db_CiAttribute::VALUE_TEXT] = $newFilename;
                        $data[Db_CiAttribute::NOTE]       = $filename;

                        $ciAttributeId = $ciDaoImpl->addCiAttributeArray($ciId, $config[Db_MailImport::ATTACHMENT_ATTRIBUTE_ID], $data, '0');
                    } else {
                        $logger->log('invalid attachment (no content or name)', Zend_Log::CRIT);
                    }
                } catch (Exception $e) {
                    $logger->log('create attachment FAILED!!', Zend_Log::CRIT);
                    $logger->log($e, Zend_Log::CRIT);
                }
            }

        // delete mail
        if ($config[Db_MailImport::PROTOCOL] == 'IMAP' && isset($config[Db_MailImport::MOVE_FOLDER])) {
            $mail->moveMessage($key, $config[Db_MailImport::MOVE_FOLDER]);
        } else {
            $mail->removeMessage($key);
        }

        return $exceptions;
    }


    /**
     * @param unknown_type $part
     *
     * @throws Exception
     */
    private static function getAttachmentDefinition(&$part, $logger)
    {
        try {
            $fileName = null;
            try {
                $fileName = $part->getHeader('content-description');
                $logger->log('Attachment Step 1: ' . $fileName);
                if (!$fileName)
                    throw new Exception();
            } catch (Exception $e) {

                try {
                    $header = $part->getHeader('content-disposition');
                    $logger->log('Attachment Step 2 header: ' . $header);
                    preg_match("/filename=(.*)/", $header, $filename);
                    $fileName = $filename[1];
                    $logger->log('Attachment Step 2: ' . $fileName);
                    if (!$fileName)
                        throw new Exception();
                } catch (Exception $e) {
                    // description does not exist. try type
                    $header = $part->getHeader('content-type');
                    $logger->log('Attachment Step 3 header: ' . $header);
                    preg_match("/name=(.*)/", $header, $filename);
                    $fileName = $filename[1];
                    $logger->log('Attachment Step 3: ' . $fileName);
                }
            }

            //for mb_detect_encoding: try this order, if encoding fails, cause not all chars are encoded correctly, try next encoding
            //there is no 'correct' list of encodings, cause it depends from region
            //list of encodings: http://php.net/manual/en/mbstring.supported-encodings.php
            //ATTENTION: sometimes chars in one encoding have an other sign in another encoding!
            $charset_encoding_detect_order = array(
                'ASCII',
                'UTF-8',
                'ISO-8859-1', //german
                'WINDOWS-1252', //ANSI
            );

            $logger->log("new function: " . self::decode_header($fileName));
            $fileName = self::decode_header($fileName);//decode mail header(=?utf-8?B?, etc.)
            $fileName = iconv(mb_detect_encoding($fileName, $charset_encoding_detect_order, true), "UTF-8", $fileName);//convert to utf8
            $logger->log("converted filename: " . $fileName);


            return $fileName;

        } catch (Exception $e) {
            return null;
        }
    }

    public static function _contentDecoder($encoding, $content, $ignore = false)
    {
        switch ($encoding) {
            case 'quoted-printable':
                $result = quoted_printable_decode($content);
                //$result = utf8_encode($result);
                break;
            case 'base64':
                $result = base64_decode($content);
                //$result = utf8_encode($result);
                break;
            default:
                $result = $content;
                //$result = utf8_encode($result);
                break;
        }

        $result = str_replace("\n", "\r\n", $result);

        if ($ignore) {
            return $result;
        }

        if (!self::is_utf8($result)) {
            $result = utf8_encode($result);
        }
        //$result = mb_convert_encoding($result, 'UTF-8', mb_detect_encoding($result));
        return $result;
    }


    public static function is_utf8($str)
    {
        $c    = 0;
        $b    = 0;
        $bits = 0;
        $len  = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) return false;
                elseif ($c >= 252) $bits = 6;
                elseif ($c >= 248) $bits = 5;
                elseif ($c >= 240) $bits = 4;
                elseif ($c >= 224) $bits = 3;
                elseif ($c >= 192) $bits = 2;
                else return false;
                if (($i + $bits) > $len) return false;
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    }


    public static function decode($string)
    {
        $string = str_replace(':', '', $string);
        $string = str_replace(' ', '_', $string);
        $string = str_replace('&', '', $string);
        $string = str_replace('?', '', $string);
        $string = str_replace('#', '', $string);
        $string = str_replace('@', '', $string);
        $string = str_replace('/', '', $string);
        $string = str_replace('(', '', $string);
        $string = str_replace(')', '', $string);
        $string = str_replace('$', '', $string);
        $string = str_replace('§', '', $string);
        $string = str_replace('=', '', $string);
        $string = str_replace('{', '', $string);
        $string = str_replace('}', '', $string);
        $string = str_replace('"', '', $string);
        $string = str_replace('\'', '', $string);
        $string = str_replace('\\', '', $string);
        $string = str_replace(',', '_', $string);

        $string = utf8_encode($string);
        $string = str_replace('ä', 'ae', $string);
        $string = str_replace('ö', 'oe', $string);
        $string = str_replace('ü', 'ue', $string);
        $string = str_replace('Ä', 'Ae', $string);
        $string = str_replace('Ö', 'Oe', $string);
        $string = str_replace('Ü', 'Ue', $string);
        $string = str_replace('ß', 'ss', $string);
        return $string;
    }

    /**
     * http://www.rfc-archive.org/getrfc.php?rfc=2047
     *
     * =?<charset>?<encoding>?<data>?=
     *
     * @param string $header
     */
    public static function is_encoded_header($header)
    {
        // e.g. =?utf-8?q?Re=3a=20Support=3a=204D09EE9A=20=2d=20Re=3a=20Support=3a=204D078032=20=2d=20Wordpress=20Plugin?=
        // e.g. =?utf-8?q?Wordpress=20Plugin?=
        return preg_match(self::rfc2047header, $header) !== 0;
    }

    public static function header_charsets($header)
    {
        $matches = null;
        if (!preg_match_all(self::rfc2047header, $header, $matches, PREG_PATTERN_ORDER)) {
            return array();
        }
        return array_map('strtoupper', $matches[1]);
    }

    public static function decode_header($header)
    {
        $matches = null;

        /* Repair instances where two encodings are together and separated by a space (strip the spaces) */
        $header = preg_replace(self::rfc2047header_spaces, "$1$2", $header);

        /* Now see if any encodings exist and match them */
        if (!preg_match_all(self::rfc2047header, $header, $matches, PREG_SET_ORDER)) {
            return $header;
        }
        foreach ($matches as $header_match) {
            list($match, $charset, $encoding, $data) = $header_match;
            $encoding = strtoupper($encoding);
            switch ($encoding) {
                case 'B':
                    $data = base64_decode($data);
                    break;
                case 'Q':
                    $data = quoted_printable_decode(str_replace("_", " ", $data));
                    break;
                default:
                    throw new Exception("preg_match_all is busted: didn't find B or Q in encoding $header");
            }
            // This part needs to handle every charset
            switch (strtoupper($charset)) {
                case "UTF-8":
                    break;
                default:
                    /* Here's where you should handle other character sets! */
                    throw new Exception("Unknown charset in header - time to write some code.");
            }
            $header = str_replace($match, $data, $header);
        }
        return $header;
    }
}