<?php

/**
 *
 * 0x00000000 to 0x0000FFFF - App_Exception (for all unknown exceptions, framework exception, etc)
 * 0x00010000 to 0x0001FFFF - Cmdb_Auth
 * 0x00020000 to 0x0002FFFF - Cmdb_Db
 * 0x00030000 to 0x0003FFFF - Cmdb_File (upload/process files)
 * 0x00040000 to 0x0004FFFF - Cmdb_Session
 * 0x00050000 to 0x0005FFFF - Cmdb_Historization
 * 0x00060000 to 0x0006FFFF - Cmdb_Notification
 * 0x00070000 to 0x0007FFFF - Cmdb_FileImport
 * 0x00080000 to 0x0008FFFF - Cmdb_MailImport
 * 0x00090000 to 0x0009FFFF - Cmdb_Form (for Form validation exceptions)
 *
 * 0x000A0000 to 0x000AFFFF - Cmdb_Attribute
 * 0x000B0000 to 0x000BFFFF - Cmdb_Autodiscovery
 * 0x000C0000 to 0x000CFFFF - Cmdb_Ci
 * 0x000D0000 to 0x000DFFFF - Cmdb_Citype
 * 0x000E0000 to 0x000EFFFF - Cmdb_Config
 * 0x000F0000 to 0x000FFFFF - Cmdb_Cron
 * 0x00100000 to 0x0010FFFF - Cmdb_Customization
 * 0x00110000 to 0x0011FFFF - Cmdb_Project
 * 0x00120000 to 0x0012FFFF - Cmdb_Relation
 * 0x00130000 to 0x0013FFFF - Cmdb_Reporting
 * 0x00140000 to 0x0014FFFF - Cmdb_Role
 * 0x00150000 to 0x0015FFFF - Cmdb_Search
 * 0x00160000 to 0x0016FFFF - Cmdb_SearchList
 * 0x00170000 to 0x0017FFFF - Cmdb_Theme
 * 0x00180000 to 0x0018FFFF - Cmdb_User
 * 0x00190000 to 0x0019FFFF - CmDb_AttributeGroup
 * 0x001A0000 to 0x001AFFFF - Cmdb_Translation
 * 0x001B0000 to 0x001BFFFF - Cmdb_Map
 * 0x001C0000 to 0x001CFFFF - Cmdb_Migration
 *
 * 0x001D0000 to 0x001DFFFF - Cmdb_Query
 * 0x001E0000 to 0x001EFFFF - Cmdb_Queue
 * 0x001F0000 to 0x001FFFFF - Cmcb_Validation
 * 0x00200000 to 0x0020FFFF - Cmdb_Dashboard
 * 0x00210000 to 0x0021FFFF - Cmdb_Menu
 * 0x00220000 to 0x0022FFFF - UNBESETZT
 * 0x00230000 to 0x0023FFFF - UNBESETZT
 * 0x00240000 to 0x0024FFFF - UNBESETZT
 *
 *
 */
abstract class Exception_Abstract extends Zend_Exception
{

    protected $code          = 0;
    protected $exception     = array();
    protected $message       = 'no translation';
    protected $messageDetail = '';

    protected $logger = null;

    function __construct($exception = null, $log = true)
    {
        $classname  = get_class($this);
        $classArray = explode('_', $classname);

        if ($classArray[1])
            $translationFile = strtolower($classArray[1]);

        if ($classArray[2])
            $translationValue = strtolower($classArray[2]);

        if (!$translationFile)
            $translationFile = 'global';


        if ($exception) {
            $this->exception = $exception;
        }


        try {
            if (!$this->translationFileExist($translatorProperties->translation->dir .'/de/exceptions/', $translationFile)) {
                $translationFile = 'global';
            }

            $translatorProperties = new Zend_Config_Ini(APPLICATION_PATH . '/configs/translation.ini', APPLICATION_ENV);
            $translate            = new Zend_Translate('csv', $translatorProperties->translation->dir . '/de/exceptions/' . $translationFile . '_de.csv', 'de');
            $translate->addTranslation($translatorProperties->translation->dir . '/en/exceptions/' . $translationFile . '_en.csv', 'en');

            // translate message. ->reformate exception code from number to hexstring (translation file sync)
            $this->message = $translate->_($translationValue);

            $this->logger = Zend_Registry::get('Log');
        } catch (Exception $e) {
            //ironic..
        }

        $fullMessage = sprintf("%s: %s", $this->message, $exception);

        parent::__construct($fullMessage, $this->code);
    }

    private function translationFileExist($path, $filename)
    {
        if (!$translationFile) {
            return false;
        }

        if (!file_exists($translatorProperties->translation->dir . '/de/exceptions/' . $translationFile . '_de.csv')) {
            return false;
        }

        if (!file_exists($translatorProperties->translation->dir . '/en/exceptions/' . $translationFile . '_en.csv')) {
            return false;
        }

        // TODO: additional checks?

        return true;
    }

    /**
     * retrieves the current Exception code
     */
    public function getExceptionCode()
    {
        return $this->code;
    }


    /**
     * retrieves the current Exception message
     */
    public function getExceptionMessage()
    {
        return $this->message;
    }

    public function getExceptionMessageDetail()
    {
        return $this->messageDetail;
    }
}