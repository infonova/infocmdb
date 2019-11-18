<?php

interface Notification_Gateway
{

    public function send($from = null, $fromname = null, array $reciever, $subject, $body, array $attachments = array(), array $parameter = array(), array $recievercc, array $recieverbcc);
}