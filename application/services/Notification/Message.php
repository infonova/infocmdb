<?php

abstract class Notification_Message
{

    protected $subject;
    protected $body;

    protected $bodyParams  = array();
    protected $attachments = array();

    protected $reciever       = array();
    protected $receiverCC     = array();
    protected $receiverBCC    = array();
    protected $gateways       = array();
    protected $gatewayConfigs = array();
    protected $parameter      = array();
    protected $template;

    protected $from;
    protected $fromname;

    protected $logger;

    function __construct()
    {
        $this->logger = Zend_Registry::get('Log');
    }

    public function setFrom($from)
    {

        $this->from = $from;

    }

    public function getFrom()
    {

        return $this->from;

    }

    public function setFromName($fromname)
    {

        $this->fromname = $fromname;

    }

    public function getFromName()
    {

        return $this->fromname;

    }


    public function setSubject($subject)
    {
        $this->subject = $subject;
    }


    public function getSubject()
    {
        return $this->subject;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function setReciever(array $reciever)
    {
        $this->reciever = $reciever;
    }

    public function setRecieverCC(array $recieverCC)
    {
        $this->receiverCC = $recieverCC;

    }

    public function setRecieverBCC(array $recieverBCC)
    {
        $this->receiverBCC = $recieverBCC;

    }

    public function getReciever()
    {
        return $this->reciever;
    }

    public function getRecieverCC()
    {
        return $this->receiverCC;
    }


    public function getRecieverBCC()
    {
        return $this->receiverBCC;
    }

    public function addReciever($rec)
    {
        array_push($this->reciever, $rec);
    }

    public function setParameter(array $parameter)
    {
        $this->parameter = $parameter;
    }

    public function getParameter()
    {
        return $this->parameter;
    }

    public function addParameter($paramName, $value)
    {
        $this->parameter[$paramName] = $value;
    }


    public function setGateways(array $gateways)
    {
        $this->gateways = $gateways;
    }

    public function getGateways()
    {
        return $this->gateways;
    }

    public function addGateway($rec)
    {
        array_push($this->gateways, $rec);
    }

    public function setGatewayConfigs(array $gatewayConfigs)
    {
        $this->gatewayConfigs = $gatewayConfigs;
    }

    public function getGatewayConfigs()
    {
        return $this->gatewayConfigs;
    }

    public function getGatewayConfig($gatewayName)
    {
        if (isset($this->gatewayConfigs[$gatewayName])) {
            $gatewayConfig = $this->gatewayConfigs[$gatewayName];
        } else {
            $gatewayConfig = new Util_Config('notification/mail.ini', APPLICATION_ENV);
        }

        $configuration = $gatewayConfig->getConfig();
        if ($configuration === null) {
            $messages = $gatewayConfig->getErrorMessages();
            if (!empty($messages)) {
                $exceptionMessage = 'Failed to load config: ' . join(' - ', $messages);
                throw new Exception($exceptionMessage);
            }
        }

        return $gatewayConfig;
    }

    public function setGatewayConfig($gatewayName, Util_Config $config)
    {
        $this->gatewayConfigs[$gatewayName] = $config;
    }

    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    public function addAttachment($rec)
    {
        array_push($this->attachments, $rec);
    }

    public function getBodyParams()
    {
        return $this->bodyParams;
    }

    public function setBodyParams(array $params = array())
    {
        $this->bodyParams = $params;
    }

    public function addBodyParam($key, $value)
    {
        $this->bodyParams[$key] = $value;
    }

    protected function getMessageContent()
    {
        if ($this->body) {
            $body = $this->body;
            foreach ($this->bodyParams as $key => $val) {
                $newKey = ':' . $key . ':';
                $body   = str_replace($newKey, $val, $body);
            }
            return $body;
        }


        if (!$this->template) {
            $this->template = 'templates/notification/mail/default.phtml';
            $scriptPath     = APPLICATION_PATH . "/modules/cmdb/views/scripts/";
        } else {
            $templatesPath = 'templates/notification/mail/';
            $scriptPath    = APPLICATION_PATH . '/modules/cmdb/views/scripts/' . $templatesPath;
        }

        $mailView = new Zend_View();
        $mailView->setEscape('htmlentities');
        $mailView->assign('data', $this->bodyParams);
        $mailView->setScriptPath($scriptPath);

        return $mailView->render($this->template);
    }

    public function send()
    {
        if (!$this->logger)
            $this->logger = Zend_Registry::get('Log');

        try {
            if ($this->gateways && count($this->gateways) > 0)
                foreach ($this->gateways as $gateway) {
                    $config  = $this->getGatewayConfig($gateway);
                    $gateway = new $gateway($config);
                    $body    = $this->getMessageContent();
                    $params  = $this->getParameter();
                    $gateway->send($this->getFrom(), $this->getFromName(), $this->getReciever(), $this->getSubject(), $body, $this->getAttachments(), $params, $this->getRecieverCC(), $this->getRecieverBCC());


                }
            return true;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            return false;
        }
    }

}