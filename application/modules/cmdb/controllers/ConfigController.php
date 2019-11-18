<?php
require_once 'AbstractAppAction.php';

/**
 * used to administrate the application config
 *
 *
 */
class ConfigController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/config_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/config_en.csv', 'en');
            parent::addUserTranslation('config');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }


    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);

        $directorypath         = APPLICATION_PATH . '/configs';
        $service               = new Service_Config_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $this->view->directory = $service->scanDirectoryRecursive($directorypath);
    }

    public function editAction()
    {
        $type = $this->_request->getParam('type');
        $dir  = $this->_request->getParam('dir');

        $location = APPLICATION_PATH . '/configs/' . (($dir) ? $dir .'/': '') . $type . '.ini';
        if (!is_file($location))
            throw new Exception_InvalidParameter();

        $service = new Service_Config_Update($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        $form             = $service->createEditForm($location);
        $this->view->form = $form;

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $formData = $form->getValues();
                //write ini file
                $service->savePropertiesToFile($location, $formData);
                parent::clearIndividualizationCache();
                $this->_redirect('config/index');
            } else {
                $form->populate($formData);
            }
        }
    }


}