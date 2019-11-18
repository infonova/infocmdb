<?php

class Notification_Gateway_Mail implements Notification_Gateway
{

    /*
     * @var Util_Config
     */
    protected $config;

    public function __construct(Util_Config $config)
    {
        $this->config = $config;
    }


    public function send($from = null, $fromname = null, array $reciever, $subject, $body, array $attachments = array(), array $parameter = array(), array $recievercc, array $recieverbcc)
    {
        if ((count($reciever) <= 0) && (count($recievercc) <= 0) && (count($recieverbcc) <= 0)) {
            return;
        }

        $mailConfig = $this->config;

        if (!isset($fromname)) {
            $fromname = $mailConfig->getValue('mail.sender.name', null);
        }

        if (!isset($from)) {
            $from = $mailConfig->getValue('mail.sender.address', null);
        }

        $smtp     = $mailConfig->getValue('mail.sender.smtp', null);
        $sendmail = $mailConfig->getValue('mail.sender.sendmail.enabled', null);

        $authEnabled  = $mailConfig->getValue('mail.sender.auth.enabled', null);
        $authMethod   = $mailConfig->getValue('mail.sender.auth.method', null);
        $authUser     = $mailConfig->getValue('mail.sender.auth.user', null);
        $authPassword = $mailConfig->getValue('mail.sender.auth.password', null);

        $port          = $mailConfig->getValue('mail.sender.port', null);
        $secureEnabled = $mailConfig->getValue('mail.sender.secure.enabled', null);
        $secureMethod  = $mailConfig->getValue('mail.sender.secure.method', null);

        $isPlain = $mailConfig->getValue('mail.sender.plain', null);

        $bodyMimeType = Zend_Mime::TYPE_HTML;
        if (isset($parameter['BodyMimeType'])) {
            $bodyMimeType = $parameter['BodyMimeType'];
        } elseif (!preg_match("/([\<])([^\>]{1,})*([\>])/i", $body)) {
            $bodyMimeType = Zend_Mime::TYPE_TEXT;
        }

        $charset = 'iso-8859-1';
        if (isset($parameter['Charset'])) {
            $charset = $parameter['Charset'];
        }


        if ($authEnabled) {

            if (!$authMethod) {
                $authMethod = 'login';
            }

            $config = array(
                'auth'     => $authMethod,
                'username' => $authUser,
                'password' => $authPassword,
            );

            if ($port) {
                $config['port'] = $port;
            }

            if ($secureEnabled && $secureMethod) {
                $config['ssl'] = $secureMethod;
            }

            $transport = new Zend_Mail_Transport_Smtp($smtp, $config);
        } else if ($sendmail) {
            $transport = new Zend_Mail_Transport_Sendmail();
        } else if ($isPlain) {
            $transport = new Zend_Mail_Transport_Smtp($smtp);
        } else {
            $config = array();

            if ($port) {
                $config['port'] = $port;
            }

            if ($secureEnabled && $secureMethod) {
                $config['ssl'] = $secureMethod;
            }

            $transport = new Zend_Mail_Transport_Smtp($smtp, $config);
        }


        $mail = new Zend_Mail($charset);
        $mail->setFrom($from, $fromname);
        $mail->setSubject($subject);

        if ($bodyMimeType === Zend_Mime::TYPE_HTML) {
            $mail->setBodyHtml($body);
        } elseif ($bodyMimeType === Zend_Mime::TYPE_TEXT) {
            $mail->setBodyText($body);
        }

        if (count($reciever) > 0) {
            foreach ($reciever as $rec) {
                if ($rec['type'] == 'mail') {
                    $mail->addTo($rec['address'], $rec['address']);
                }
            }
        }

        if (count($recievercc) > 0) {
            foreach ($recievercc as $rec) {
                if ($rec['type'] == 'mail') {
                    $mail->addCC($rec['address'], $rec['address']);
                }
            }
        }

        if (count($recieverbcc) > 0) {
            foreach ($recieverbcc as $rec) {
                if ($rec['type'] == 'mail') {
                    $mail->addBcc($rec['address'], $rec['address']);
                }
            }
        }

        foreach ($attachments as $attachment) {
            try {

                if (is_array($attachment)) {
                    $filename = $attachment['name'];
                    $filepath = $attachment['file'];

                } else {
                    $filename = $attachment;
                    $filepath = $attachment;
                }

                $file            = file_get_contents($filepath);
                $at              = new Zend_Mime_Part($file);
                $at->filename    = $filename;
                $at->encoding    = Zend_Mime::ENCODING_BASE64;
                $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                $mail->addAttachment($at);
            } catch (Exception $e) {
                $logger = Zend_Registry::get('Log');
                $logger->log($e, Zend_Log::CRIT);
            }
        }

        if ($parameter['meetingrequest']) {
            $organizerName = $parameter['organizerName'];
            $organizerMail = $parameter['organizerMail'];
            $meetingstamp  = strtotime($parameter['start']);
            $duration      = $parameter['duration']; // in seconds
            $location      = $parameter['location'];

            $dtstart    = gmdate("Ymd\THis\Z", $meetingstamp);
            $dtend      = gmdate("Ymd\THis\Z", $meetingstamp + $duration);
            $todaystamp = gmdate("Ymd\THis\Z");

            $cal_uid = date('Ymd') . 'T' . date('His') . "-" . rand() . "@infocmdb";

            if (!$organizerName) {
                $organizerName = $organizerMail;
            }

            // the following code tries to imitate an outlook appointment
            $ical = 'BEGIN:VCALENDAR
PRODID:-//Microsoft Corporation//Outlook 12.0 MIMEDIR//EN
VERSION:2.0
METHOD:REQUEST
X-MS-OLK-FORCEINSPECTOROPEN:TRUE
BEGIN:VEVENT';

            foreach ($reciever as $rec) {
                if ($rec['type'] == 'mail') {
                    $ical .= '
ATTENDEE;CN="' . $rec['address'] . '";RSVP=TRUE:mailto:' . $rec['address'] . '';
                }
            }

            $ical .= '
ORGANIZER;CN="' . $organizerName . '":MAILTO:' . $organizerMail . '
DTSTART:' . $dtstart . '
DTEND:' . $dtend . '
LOCATION:' . $location . '
TRANSP:OPAQUE
SEQUENCE:0
UID:' . $cal_uid . '
DTSTAMP:' . $todaystamp . '
PRIORITY:5
X-MICROSOFT-CDO-BUSYSTATUS:TENTATIVE
X-MICROSOFT-CDO-IMPORTANCE:1
X-MICROSOFT-CDO-INTENDEDSTATUS:BUSY
X-MICROSOFT-DISALLOW-COUNTER:FALSE
X-MS-OLK-ALLOWEXTERNCHECK:TRUE
X-MS-OLK-CONFTYPE:0
CLASS:PUBLIC
BEGIN:VALARM
TRIGGER:-PT15M
ACTION:DISPLAY
DESCRIPTION:Reminder
END:VALARM
END:VEVENT
END:VCALENDAR';


            $at              = new Zend_Mime_Part($ical);
            $at->type        = 'text/calendar"';
            $at->disposition = Zend_Mime::DISPOSITION_INLINE;
            $at->encoding    = Zend_Mime::ENCODING_8BIT;
            $at->filename    = 'termin.ics';

            $mail->addAttachment($at);
        }

        $mail->send($transport);
    }
}