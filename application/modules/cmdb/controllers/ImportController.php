<?php
require_once 'AbstractAppAction.php';

/**
 *
 * TODO: this class should not be used
 *
 *
 * @deprecated
 */
class ImportController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/fileimport_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/fileimport_en.csv', 'en');
            parent::addUserTranslation('fileimport');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    /**
     * configuration?
     */
    public function indexAction()
    {

        $isUpload    = $this->_getParam('isUpload');
        $amountFiles = $this->_getParam('files');

        $this->view->isUpload = $isUpload;
        $this->view->files    = $amountFiles;

        if ($isUpload) {

            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);

            $enabled     = $config->file->import->enabled;
            $destination = $config->file->import->destination;
            $maxfilesize = $config->file->import->maxfilesize;

            // setting default value
            if (!$enabled) {
                $enabled = false;
            }
            if (!$maxfilesize) {
                $maxfilesize = 52428800;
            }

            // check if requested path is available
            if (!is_dir($destination)) {
                @mkdir($destination, 0777);
                chmod($destination, 0777);
            }

            $form = new Zend_Form();
            $form->setAttrib('enctype', 'multipart/form-data');
            $form->setTranslator($this->translator);
            $form->removeDecorator('DtDdWrapper');

            if (!$amountFiles) {
                $amountFiles = 1;
            }

            for ($i = 1; $i <= $amountFiles; $i++)
                $form->addElement($this->getFormElementUpload($i, $destination, $maxfilesize));

            $submit = new Zend_Form_Element_Submit('submit');
            $submit->setLabel('upload');
            $submit->setRequired(true);
            $form->addElement($submit);

            $this->view->form = $form;

            if ($this->_request->isPost()) {
                $formData = $this->_request->getPost();
                if ($form->isValid($formData) && $form->getValues()) {
                } else {
                    $this->view->exceptionMessage = 'EXCEPTION!!!';
                    $form->populate($formData);
                }
            }

        }
    }


    private function getFormElementUpload($number, $destination, $maxfilesize)
    {
        $doc_file = new Zend_Form_Element_File('file_' . $number);
        $doc_file->setMaxFileSize($maxfilesize);
        $doc_file->setDestination($destination);
        $doc_file->removeDecorator('Label');
        $doc_file->removeDecorator('DtDdWrapper');

        return $doc_file;
    }


    /**
     * displays the current import configuration for external file systems
     */
    public function configAction()
    {

        $this->logger->log('Fileimport config action has been invoked', Zend_Log::DEBUG);
        // action body
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/import.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->config->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->config->itemsPerPage;
        $scrollingStyle    = $config->pagination->config->scrollingStyle;
        $scrollingControl  = $config->pagination->config->scrollingControl;

        $page      = $this->_getParam('page');
        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $importDaoImpl = new Dao_Import();
        $select        = $importDaoImpl->getImportConfigForPagination($orderBy, $direction);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $this->view->page      = $page;
        $this->view->orderBy   = $orderBy;
        $this->view->direction = $direction;
        $this->view->paginator = $paginator;
        $this->render();
    }


    /**
     * list all Files that had errors
     */
    public function failedAction()
    {
        $page = $this->_getParam('page');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->error->destination;
        $this->getPaginator($page, 'failed', $dir);


        $this->view->filePath = APPLICATION_URL . '/_uploads/import/error';
        $this->render('display');
    }


    /**
     * display all succeeded files
     */
    public function successAction()
    {
        $page = $this->_getParam('page');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->success->destination;
        $this->getPaginator($page, 'success', $dir);

        $this->view->filePath = $this->view->filePath = APPLICATION_URL . '/_uploads/import/processed';
        $this->render('display');
    }

    /**
     * show all files that are currently in the queue
     */
    public function queueAction()
    {
        $page = $this->_getParam('page');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);
        $dir    = $config->file->import->destination;
        $this->getPaginator($page, 'queue', $dir);

        $this->view->filePath = $this->view->filePath = APPLICATION_URL . '/_uploads/import';
        $this->render('display');
    }


    private function getPaginator($page, $type, $dir)
    {
        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/import.ini', APPLICATION_ENV);

        $itemsCountPerPage = $config->pagination->$type->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->$type->itemsPerPage;
        $scrollingStyle    = $config->pagination->$type->scrollingStyle;
        $scrollingControl  = $config->pagination->$type->scrollingControl;


        $fileList = array();

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_file($dir .'/'. $file) && substr($file, 0, 1) != '.') {
                        $mod = date("Y-m-d, H:i:s", filemtime($dir .'/'. $file));
                        array_push($fileList, array('file' => $file, 'time' => $mod));
                    }
                }
                closedir($dh);
            }
        }


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($fileList));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $this->view->paginator = $paginator;
        $this->view->type      = $type;
    }


    public function importAction()
    {
        $fileName = $this->_getParam('file');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);

        if (!$config->file->import->enabled) {
            return false;
        }

        $location = $config->file->import->destination;

        $args                     = array();
        $args['filename']         = $fileName;
        $args['filepath']         = $location;
        $args['importType']       = 'file';
        $args['method']           = 'ci';
        $args['manualValidation'] = 'true';

        $message = new Service_Queue_Message();
        $message->setQueueId(Service_Queue_Message::QUEUE_FILE);
        $message->setArgs($args);
        $message->setUserId(parent::getUserInformation()->getId());

        try {
            Service_Queue_Handler::add($message);
            $notification['success'] = $this->translator->translate('importFileStarted');
        } catch (Exception_Queue_InvalidMessageArgs $e) {
            $notification['error'] = $this->translator->translate('importFileMessageArgsInvalid');
        } catch (Exception_Queue_AlreadyQueued $e) {
            $notification['error'] = $this->translator->translate('importFileAlreadyQueued');
        } catch (Exception_Queue_Unknown $e) {
            $notification['error'] = $this->translator->translate('importFileUnknown');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('import/queue');
    }


    public function importattributeAction()
    {
        $fileName = $this->_getParam('file');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);

        if (!$config->file->import->enabled) {
            return false;
        }

        $location = $config->file->import->destination;

        $args             = array();
        $args['filename'] = $fileName;
        $args['filepath'] = $location;
        $args['method']   = 'attributes';

        $message = new Service_Queue_Message();
        $message->setQueueId(1); // TODO: replace me with queu select?
        $message->setArgs($args);
        $message->setUserId(parent::getUserInformation()->getId());

        try {
            Service_Queue_Handler::add($message);
            $notification['success'] = $this->translator->translate('importFileAttributesStarted');
        } catch (Exception_Queue_InvalidMessageArgs $e) {
            $notification['error'] = $this->translator->translate('importFileMessageArgsInvalid');
        } catch (Exception_Queue_AlreadyQueued $e) {
            $notification['error'] = $this->translator->translate('importFileAlreadyQueued');
        } catch (Exception_Queue_Unknown $e) {
            $notification['error'] = $this->translator->translate('importFileUnknown');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('import/queue');
    }

    /**
     * restart a failed file
     *
     * @param $fileName the name of the file to be restarted
     */
    public function restartAction()
    {
        $fileName = $this->_getParam('file');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);

        $queueDestination = $config->file->import->destination;
        $errorDestination = $config->file->import->error->destination;


        if (!rename($errorDestination .'/'. $fileName, $queueDestination .'/'. $fileName)) {
            throw new Exception_FileImport_RestartFailed();
        } else {
            $this->_redirect(APPLICATION_URL . 'import/failed');
        }
    }

    /**
     * deletes a file by the given parameter
     *
     * @param $fileName the name of the file to be deleted
     * @param $type     can be queue, failed, success (used to find the file)
     */
    public function deleteAction()
    {
        $fileName = $this->_getParam('file');
        $type     = $this->_getParam('type');

        if (!$fileName || !$type) {
            throw new Exception_InvalidParameter();
        }

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/import.ini', APPLICATION_ENV);

        $queueDestination  = $config->file->import->destination;
        $errorDestination  = $config->file->import->error->destination;
        $sucessDestination = $config->file->import->success->destination;

        $file     = null;
        $redirect = "";

        switch ($type) {
            case 'queue':
                $redirect = "import/queue";
                $file     = $queueDestination .'/'. $fileName;
                break;
            case 'failed':
                $redirect = "import/failed";
                $file     = $errorDestination .'/'. $fileName;
                $logFile  = $errorDestination . '/log/' . $fileName . '.log';
                unlink($logFile);
                break;
            case 'success':
                $redirect = "import/success";
                $file     = $sucessDestination .'/'. $fileName;
                break;
            default:
                $redirect = "import/index";
                $file     = $errorDestination .'/'. $fileName;
                break;
        }

        unlink($file);
        $this->_redirect(APPLICATION_URL . $redirect);
    }
}