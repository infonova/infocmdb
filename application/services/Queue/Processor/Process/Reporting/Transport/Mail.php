<?php

class Process_Reporting_Transport_Mail extends Process_Reporting_Transport
{


    protected function processValid($reporting, $file, $path)
    {


        $reportingDaoImpl = new Dao_Reporting();
        $addressses       = $reportingDaoImpl->getReportingMailAddresses($reporting[Db_Reporting::ID]);
        $mail_content     = $reporting[Db_Reporting::MAIL_CONTENT];

        $reciever = array();
        foreach ($addressses as $address) {
            array_push($reciever, array('type' => 'mail', 'address' => $address[Db_Notification::ADDRESS]));
        }

        $subject = "infocmdb Report"; // TODO: replace with predefined name

        $gatewayConfig = new Util_Config('notification/mail.ini', APPLICATION_ENV);
        $gatewayClass  = $gatewayConfig->getValue('mail.sender.class', 'Notification_Gateway_Mail');

        $message = new Notification_Message_Default();
        $message->setSubject($subject);
        $message->addGateway($gatewayClass);
        $message->setGatewayConfig($gatewayClass, $gatewayConfig);
        $message->setReciever($reciever);

        if (($mail_content !== null) && (trim(strip_tags(html_entity_decode(str_replace('&nbsp;', '', $mail_content)))) != '')) {
            $message->setBody($mail_content);

        } else {
            $message->setTemplate('reporting.phtml');
        }

        $message->addAttachment(array('name' => $file, 'file' => $path .'/'. $file));
        $message->send();
    }
}