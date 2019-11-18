<?php
require_once 'AbstractAppAction.php';

/**
 * used to administrate the translation files
 *
 *
 */
class TranslationController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/translation_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/translation_en.csv', 'en');
            parent::addUserTranslation('translation');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    private function scanDirectoryRecursive($directorypath, $dir = false, $level = null)
    {
        $directory = scandir($directorypath);

        foreach ($directory as $file) {
            if (is_file($directorypath .'/'. $file)) {
                if ($dir) {
                    if ($level && !$leveledUp) {
                        $dir       = $level . '>>' . $dir;
                        $leveledUp = true;
                    }
                    $link = APPLICATION_URL . 'translation/edit/dir/' . $dir . '/type/' . substr($file, 0, -(strlen('.ini')));
                } else
                    $link = APPLICATION_URL . 'translation/edit/type/' . substr($file, 0, -(strlen('.ini')));

                $files[] = array(
                    'title' => substr($file, 0, -(strlen('.ini'))),
                    'link'  => $link,
                );
            }
            if (is_dir($directorypath .'/'. $file)) {
                if (strpos($file, ".") !== 0) {
                    $files[$file] = $this->scanDirectoryRecursive($directorypath .'/'. $file, $file, $dir);
                }
            }
        }
        return $files;
    }

    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);

        if (is_null($this->translatorProperties)) {
            $this->translatorProperties = new Zend_Config_Ini(APPLICATION_PATH . '/configs/translation.ini', APPLICATION_ENV);
            $this->logger->log('Loaded Translation Properties', Zend_Log::DEBUG);
            $this->languagePath = $this->translatorProperties->translation->dir;
        }

        $directorypath         = $this->languagePath;
        $this->view->directory = $this->scanDirectoryRecursive($directorypath);
    }

    public function editAction()
    {
        $type = $this->_request->getParam('type');
        $dir  = $this->_request->getParam('dir');

        if (strpos($dir, '>>'))
            $dir = str_replace('>>', '/', $dir);

        if (is_null($this->translatorProperties)) {
            $this->translatorProperties = new Zend_Config_Ini(APPLICATION_PATH . '/configs/translation.ini', APPLICATION_ENV);
            $this->logger->log('Loaded Translation Properties', Zend_Log::DEBUG);
            $this->languagePath = $this->translatorProperties->translation->dir;
        }

        $location = $this->languagePath . (($dir) ? $dir .'/': '') . $type . '.csv';
        if (!is_file($location))
            throw new Exception_Translation_LoadTranslationFileFailed();


        // todo: get customized file
        $udFile = APPLICATION_PUBLIC . '/translation/' . (($dir) ? $dir .'/': '') . $type . '.csv';

        if (!is_file($udFile)) {
            try {
                $handler = fopen($udFile, 'w') or die("can't open file");
                fclose($handler);
            } catch (Exception $e) {
                $this->logger->log($e, Zend_Log::CRIT);
                $notification          = array();
                $notification['error'] = $this->translator->translate('translationfileUpdateFailed');
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('translation/index/');
            }
        }


        $this->handleConfig($location, $udFile);
    }

    private function handleConfig($location, $udFile)
    {

        $form             = $this->createEditForm($location, $udFile);
        $this->view->form = $form;

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $formData = $form->getValues();
                //write ini file
                $this->savePropertiesToFile($udFile, $formData);
                parent::clearTranslationCache();
                $notification = array('success' => $this->translator->translate('translationfileUpdateSuccess'));
                $this->_helper->FlashMessenger($notification);
                $this->_redirect('translation/index');
            } else {
                $form->populate($formData);
            }
        }

        $this->render('edit');
    }

    private function createEditForm($location, $udFile)
    {
        $value     = file_get_contents($location);
        $valueEdit = file_get_contents($udFile);

        $form = new Zend_Form('createForm');
        $form->setName('createForm');
        $form->setAttrib('enctype', 'multipart/form-data');

        $trans = new Zend_Form_Element_Textarea('transfile');
        $trans->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width: 90%')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));
        $trans->setAttrib('disabled', 'disabled');
        $trans->setValue($value);
        $form->addElement($trans);

        $curr = new Zend_Form_Element_Textarea('file');
        $curr->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width: 90%')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));

        if ($valueEdit)
            $curr->setValue($valueEdit);

        $form->addElement($curr);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('save');
        $submit->setAttrib('class', 'standard_button');
        $submit->setDecorators(array(
            'ViewHelper',
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'style' => 'width: 60%')),
            array('Label', array('tag' => 'td', 'class' => 'invisible')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        ));
        $form->addElement($submit);

        $form->addDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'table', 'class' => 'config')),
            'Form',
        ));

        return $form;
    }


    private function savePropertiesToFile($filename, $parameterList)
    {

        if (!is_resource($filename)) {
            if (!$file = fopen($filename, 'w+')) return false;
        } else {
            $file = $filename;
        }

        $content    = $parameterList['file'];
        $sFileWrite = fwrite($file, trim($content));

        if ($sFileWrite === false) {
            // Unable to write data to file
            // try to restore old file

            // exception??
            return false;
        }

        fclose($file);
        parent::clearNavigationCache();
        return true;
    }
}