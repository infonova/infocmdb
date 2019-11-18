<?php

/**
 *
 *
 *
 */
class Service_Fileimport_Delete extends Service_Abstract
{


    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1104, $themeId);
    }


    public function deleteFileImportHistory($historyId)
    {
        $importDao = new Dao_Import();
        $importDao->deleteImportHistory($historyId);
    }

    public function deleteActiveFileImport($file)
    {
        // XXX: implement me? forbidden???

        // TODO: check Queue??
        // TODO: how to synch with import???

        unlink($file);
    }
}