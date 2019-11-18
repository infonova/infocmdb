<?php

/**
 *
 *
 *
 */
class Service_Template_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3104, $themeId);
    }


    public function deleteTemplate($templateId)
    {
        try {
            // TODO: delete all template mappings!!
            $templateDao = new Dao_Template();
            $rows        = $templateDao->deleteTemplate($templateId);
            if ($rows != 1) {
                throw new Exception();
            }
        } catch (Exception $e) {
            throw new Exception_Template_DeleteFailed($e);
        }
    }
}