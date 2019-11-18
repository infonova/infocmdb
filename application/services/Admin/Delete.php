<?php

/**
 * THIS IS AN ADMIN CLASS ONLY! be very careful when using this class
 *
 *
 *
 */
class Service_Admin_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 4104, $themeId);
    }

    /**
     *
     * kills an active user session and forces user to relogin
     *
     * all open forms and searchpages of the user will are lost!
     *
     */
    public function killSession($sessionId)
    {
        try {
            $adminDao = new Dao_Admin();
            $adminDao->deleteSession($sessionId);
        } catch (Exception $e) {
            throw new Exception_Attribute_KillSessionFailed($e);
        }
    }
}