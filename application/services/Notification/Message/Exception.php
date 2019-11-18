<?php

class Notification_Message_Exception extends Notification_Message
{

    private $exception;

    public function setException($exception)
    {
        $this->exception = $exception;
    }

    public function getException()
    {
        return $this->exception;
    }

    public function getExceptionMessage()
    {
        try {
            return $this->exception->getMessage();
        } catch (Exception $e) {
            return "";
        }
    }


    // Override
    protected function getMessageContent()
    {
        $message = $this->body;
        $message .= "\r\n
		\r\n
		\r\n";

        if ($this->parameter && count($this->parameter) > 0) {
            $message .= "PARAMETER: \r\n
			\r\n";

            foreach ($this->parameter as $key => $parameter) {
                $message .= $key . ': ' . $parameter . " \r\n";
            }
            $message .= "PARAMETER: \r\n
			\r\n
			\r\n";
        }

        $message .= "EXCEPTION: \r\n
			\r\n" . $this->getExceptionMessage();

        return $message;
    }
}