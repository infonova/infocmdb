<?php
require_once 'BaseController.php';

class Api_NotificationController extends BaseController
{

    /**
     * Default Action for this Controller
     *
     * @access    public
     * @author    unknown
     */
    public function indexAction()
    {
        $this->_forward('get');
    }

    /**
     * Handles GET-requests to this Controller
     *
     * By sending an request per GET to api/notification this function is called and calls private function send
     *
     * @access    public
     * @author    Christoph Mueller
     * @since     SVN: 5725
     */
    public function getAction()
    {
        $this->send($this->_request, $this->_response, apache_request_headers());
    }

    /**
     * Handles POST- and PUT-requests to this Controller
     *
     * By sending an request per PUT or POST to api/notification this function is called and calls private function send
     *
     * @access    public
     * @author    Christoph Mueller
     * @since     SVN: 5725
     */
    public function putAction()
    {
        $this->send($this->_request, $this->_response, apache_request_headers());
    }


    /**
     * Sends a mail via http request
     *
     * By sending an request per post, get or put to api/notification this function is called and sends a mail.
     * Errors are written into daily zend-log.
     * The following options can be passed per http header or post
     *    apikey -> authentification
     *    notify -> name of notification_template
     *    subject -> if set, subject will be replaced with this
     *    meetingrequest -> if set to 1, a meeting invitation will be created
     *    Organizername -> name of the organizer of the meeting
     *    Organizermail -> mail address of the organizer of the meeting
     *    Meetingstart -> start of the meeting in format d.m.Y H:i
     *    Meetingduration -> duration of the meeting in seconds
     *    Meetinglocation -> location of the meeting
     *    method -> output format(xml, json, plain)
     *    From -> mail address of sender
     *    FromName -> description of sender(e.g. firstname and lastname)
     *    Recipients -> recipients of the mail, separated by the ";" sign
     *    RecipientsCC -> cc-recipients of the mail, separated by the ";" sign
     *    RecipientsBCC -> bcc-recipients of the mail, separated by the ";" sign
     *    Attachments -> path to a file on server that should be attached to the mail
     *   GatewayConfig --> name of configuration file (default: mail --> mail.ini)
     *   Charset --> charset which will be set in mail header (default: iso-8859-1)
     *
     *
     * @param    object $reqest   the reqest object of the Action
     * @param    object $response the response object of the Action
     * @param    array  $headers  apache request headers
     *
     * @access    private
     * @author    Christoph Mueller, unknown
     * @since     SVN: 5725
     */
    private function send($request, $response, $headers)
    {
        $params  = $request->getParams();
        $headers = array_merge($headers, $params);

        $scriptName     = $params['notify'];
        $subject        = $params['subject'];
        $meetingrequest = $params['meetingrequest'];

        $this->logger->log('API: Notification "' . $scriptName . '" started', Zend_Log::INFO);
        try {
            $mailDaoImpl = new Dao_Mail();
            $mail        = $mailDaoImpl->getMailByName($scriptName);

            $notiDaoImpl = new Dao_Notification();
            $addresses   = $notiDaoImpl->getNotificationRecipients($mail[Db_Mail::ID]);

            if (!$subject) {
                $subject = $headers['Subject'];
            }

            $gatewayConfigName = 'mail';
            if (isset($params['GatewayConfig'])) {
                $gatewayConfigName = $params['GatewayConfig'];
            }

            $from     = $headers['From'];
            $fromname = $headers['FromName'];


            $reciever = array();
            foreach ($addresses as $address) {
                if (!empty($address[Db_Notification::ADDRESS])) {
                    array_push($reciever, array('type' => 'mail', 'address' => $address[Db_Notification::ADDRESS]));
                }
            }

            if (!$subject) {
                $subject = utf8_decode($mail[Db_Mail::SUBJECT]);
            }

            $gatewayConfig = new Util_Config('notification/' . $gatewayConfigName . '.ini', APPLICATION_ENV);
            $gatewayClass  = $gatewayConfig->getValue('mail.sender.class', 'Notification_Gateway_Mail');

            $message = new Notification_Message_Default();
            $message->setFrom($from);
            $message->setFromName($fromname);
            $message->setSubject($subject);
            $message->addGateway($gatewayClass);
            $message->setGatewayConfig($gatewayClass, $gatewayConfig);


            if ($mail[Db_Mail::TEMPLATE]) {
                $templateId  = $mail[Db_Mail::TEMPLATE];
                $templateDao = new Dao_Template();
                $template    = $templateDao->getTemplate($templateId);

                $message->setTemplate($template[Db_Templates::FILE]);
            } else {
                $message->setBody($mail[Db_Mail::BODY]);
            }

            // extract recipients
            $recipients = $headers['Recipients'];
            unset($headers['Recipients']);

            if ($recipients) {
                $recipientArray = explode(';', $recipients);
            }

            if ($recipientArray && count($recipientArray) > 0) {

                foreach ($recipientArray as $rec) {
                    $rec = trim($rec);
                    if (!empty($rec)) {
                        array_push($reciever, array('type' => 'mail', 'address' => $rec));
                    }
                }

            }

            $message->setReciever($reciever);

            // extract recipientsCC
            $recipientsCC = $headers['RecipientsCC'];
            unset($headers['RecipientsCC']);

            if ($recipientsCC) {
                $recipientCCArray = explode(';', $recipientsCC);
            }


            if ($recipientCCArray && count($recipientCCArray) > 0) {

                $recieverCC = array();
                foreach ($recipientCCArray as $rec) {
                    $rec = trim($rec);
                    if (!empty($rec)) {
                        array_push($recieverCC, array('type' => 'mail', 'address' => $rec));
                    }
                }

                if (count($recieverCC) > 0)
                    $message->setRecieverCC($recieverCC);
            }


            // extract recipientsBCC
            $recipientsBCC = $headers['RecipientsBCC'];
            unset($headers['RecipientsBCC']);

            if ($recipientsBCC) {
                $recipientBCCArray = explode(';', $recipientsBCC);
            }


            if ($recipientBCCArray && count($recipientBCCArray) > 0) {

                $recieverBCC = array();
                foreach ($recipientBCCArray as $rec) {
                    $rec = trim($rec);
                    if (!empty($rec)) {
                        array_push($recieverBCC, array('type' => 'mail', 'address' => $rec));
                    }
                }

                if (count($recieverBCC) > 0)
                    $message->setRecieverBCC($recieverBCC);
            }


            // other params
            foreach ($headers as $key => $header) {
                $params[strtolower($key)] = $header;
            }

            // if explicit placeholders are given always override using these
            if(isset($headers['placeholder'])) {
                foreach ($headers['placeholder'] as $key => $header) {
                    $params[strtolower($key)] = $header;
                }
            }

            $message->setBodyParams($params);


            // attachments
            $attachments = $headers['Attachments'];
            unset($headers['Attachments']);

            if ($attachments) {
                $attachmentArray = explode(';', $attachments);
            }

            if ($attachmentArray && count($attachmentArray) > 0) {
                foreach ($attachmentArray as $att) {
                    if ($att) {
                        $fileName = basename($att);
                        $message->addAttachment(array('name' => $fileName, 'file' => $att));
                    }
                }
            }


            //meeting request
            if ($meetingrequest) {
                $parameter                   = array();
                $parameter['meetingrequest'] = true;
                $parameter['organizerName']  = $headers['Organizername'];
                $parameter['organizerMail']  = $headers['Organizermail'];
                $parameter['start']          = $headers['Meetingstart'];
                $parameter['duration']       = $headers['Meetingduration']; // in seconds
                $parameter['location']       = $headers['Meetinglocation'];

                $message->setParameter($parameter);
            }

            $message->addParameter('BodyMimeType', $mail[Db_Mail::MIME_TYPE]);

            if (isset($params['Charset'])) {
                $message->addParameter('Charset', $params['Charset']);
            }

            //send!
            $recieverCheck = $message->getReciever();
            if (!empty($recieverCheck)) {
                $status = $message->send();
                if ($status) {
                    $responseCode = 200;
                    $notification = array('status' => 'OK', 'data' => $recieverCheck);
                } else {
                    $responseCode = 500;
                    $notification = array('status' => 'error', 'message' => 'sending message failed: unknown error');
                }
            } else {
                $responseCode = 500;
                $notification = array('status' => 'error', 'message' => 'sending message failed: no reciever');
            }

            if ($notification['status'] == 'error') {
                $this->logger->log('API: Notification "' . $scriptName . '" failed: ' . $notification['message'], Zend_Log::ERR);
            } else {
                $this->logger->log('API: Notification "' . $scriptName . '" completed', Zend_Log::INFO);
            }

            $notification = parent::getReturnValue($notification);
            $this->getResponse()
                ->setHttpResponseCode($responseCode)
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