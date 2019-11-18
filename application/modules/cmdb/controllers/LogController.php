<?php
require_once 'AbstractAppAction.php';

/**
 *
 * this class lists all logfiles
 *
 */
class LogController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/log_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/log_en.csv', 'en');
            parent::addUserTranslation('log');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    /**
     *
     * parse logfile and displays file in logbox
     */
    public function detailAction()
    {
        $this->logger->log('Detail action has been invoked', Zend_Log::DEBUG);

        $type = ($this->_getParam('type')) ? $this->_getParam('type') : 'main';
        $file = $this->_request->getParam('file');

        if ($type == 'import') {
            if (strpos($file, '>>'))
                $file = str_replace('>>', '/', $file);
            $location = APPLICATION_PUBLIC . '_uploads/import/log/' . $file;
        } else {
            $location = APPLICATION_PATH . '/../data/logs/' . $file;
        }
        $this->view->type = $type;
        $this->view->log  = file_get_contents($location);
    }

    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);

        $type = ($this->_getParam('type')) ? $this->_getParam('type') : 'main';

        if ($type == 'import') {
            $directorypath                = APPLICATION_PUBLIC . '/_uploads/import/log';
            $service                      = new Service_Log_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $this->view->importImportLogs = $service->scanDirectoryRecursive($directorypath);
            $this->view->linkType         = 'main';
        } else {
            $directorypath        = APPLICATION_DATA . '/logs';
            $service              = new Service_Log_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $this->view->mainLogs = array_reverse($service->scanDirectoryRecursive($directorypath));
            $this->view->linkType = 'import';
        }
        $this->view->type = $type;
    }

}