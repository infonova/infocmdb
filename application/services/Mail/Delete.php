<?php

/**
 *
 *
 *
 */
class Service_Mail_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3204, $themeId);
    }


    public function deleteMail($mailId)
    {
        try {
            // TODO: delete all mail mappings!!
            $mailDao = new Dao_Mail();
            $rows    = $mailDao->deleteMail($mailId);
            if ($rows != 1) {
                throw new Exception();
            }
        } catch (Exception $e) {
            throw new Exception_Mail_DeleteFailed($e);
        }
    }
}