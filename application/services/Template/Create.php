<?php

/**
 *
 *
 *
 */
class Service_Template_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3102, $themeId);
    }


    public function getCreateTemplateForm()
    {
        return new Form_Template_Create($this->translator);
    }

    public function insertTemplate($formData)
    {
        $data                            = array();
        $data[Db_Templates::NAME]        = trim($formData['name']);
        $data[Db_Templates::DESCRIPTION] = trim($formData['descr']);
        $data[Db_Templates::NOTE]        = trim($formData['note']);
        $data[Db_Templates::FILE]        = trim($formData['filename']);

        $templateDao = new Dao_Template();
        $templateId  = $templateDao->insertTemplate($data);

        if (!$templateId)
            throw new Exception_Template_InsertFailed();

        return $templateId;
    }

}