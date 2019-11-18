<?php
require_once 'AbstractAppAction.php';

/**
 *
 * @author Michael.Fischer
 *
 */
class FileuploadController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/fileupload_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/fileupload_en.csv', 'en');
            parent::addUserTranslation('fileupload');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);
        $this->_helper->layout->setLayout('popup');

        // expect a type
        $fileType    = $this->_getParam('filetype');
        $attributeId = $this->_getParam('attributeId');
        $ciId        = $this->_getParam('ciId');

        if (!$ciId)
            $ciId = $this->_getParam('ciid');

        $key                = ($this->_getParam('genId')) ? $this->_getParam('genId') : '';
        $this->view->onLoad = "#";

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);


        $useDefaultPath = $config->file->upload->path->default;
        $defaultFolder  = $config->file->upload->path->folder;


        $path = "";
        if ($useDefaultPath) {
            $path = APPLICATION_PUBLIC . $defaultFolder;
        } else {
            $path = $config->file->upload->path->custom;
        }

        $enabled     = $config->file->upload->$fileType->enabled;
        $folder      = $config->file->upload->$fileType->folder;
        $maxfilesize = $config->file->upload->$fileType->maxfilesize;

        // setting default value
        if (!$enabled) {
            $enabled = false;
        }
        if (!$folder) {
            $folder = $fileType;
        }
        if (!$maxfilesize) {
            $maxfilesize = 52428800;
        }


        if ($ciId) {
            $folder = $folder .'/'. $ciId;
        }
        if ($enabled) {
            $form = new Form_Fileupload_Create($this->translator, $fileType, $attributeId, $path, $folder, $maxfilesize);
        } else {
            throw new Exception_File_FileUploadNotEnabled();
        }

        $this->view->form = $form;

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $this->logger->log('Form is valid', Zend_Log::DEBUG);
                $uploadedData = $form->getValues();


                // generate prefix for the uploaded file
                $date = date("YmdHms\_");

                // rename file
                $currentFilename = $uploadedData['filePath'];
                $replaceChars    = array(
                    ' '  => '_',
                    'Ä'  => 'Ae',
                    'Ö'  => 'Oe',
                    'Ü'  => 'Ue',
                    'ä'  => 'ae',
                    'ö'  => 'oe',
                    'ü'  => 'ue',
                    'ß'  => 'ss',
                    '!'  => '',
                    '§'  => 'PARAGRAPH',
                    '$'  => 'DOLLAR',
                    '€'  => 'EURO',
                    '%'  => 'PERCENT',
                    '='  => '',
                    '#'  => '',
                    '\'' => '',
                    '"'  => '',
                    ','  => '',
                    ';'  => '',
                );
                $newFilename     = str_replace(array_keys($replaceChars), $replaceChars, utf8_decode($date . $currentFilename));

                if (!rename($path . $folder .'/'. $currentFilename, $path . $folder .'/'. $newFilename)) {
                    throw new Exception_File_RenamingFailed();
                }


                chmod($path . $folder .'/'. $newFilename, 0755);

                $dos2unix  = false;
                $extension = array_pop(explode(".", $newFilename));
                $extension = strtolower($extension);

                switch ($extension) {
                    case 'txt':
                        $dos2unix = true;
                        break;
                    default:
                        $dos2unix = false;
                }

                if ($dos2unix) {
                    $command = 'dos2unix ' . $path . $folder .'/'. $newFilename;
                    system($command);
                }

                $descriptionProperty = $attributeId . $key . 'description';
                $filenameProperty    = $attributeId . $key . 'filename';


                $desc = $uploadedData['description'] == '' ? $currentFilename : $uploadedData['description'];


                /*
                 * Disabled because Lucene does a reindex every day
                if(isset($ciId))
                $this->addDocumentToFilesearch($path.$folder, $newFilename, $desc, $ciId);
                */

                $this->view->onLoad = "javascript:finalizeFileUpload('" . $filenameProperty . "', '" . $newFilename . "', '" . $descriptionProperty . "', '" . str_replace("'", "\\'", $desc) . "')";
            } else {
                $form->populate($formData);
            }
        }
    }


    private function addDocumentToFilesearch($dir, $file, $title, $ciId = null)
    {
        try {
            $stat = stat("'" . $dir . $file . "'");
            // store the information in array and add to index
            $data = array();

            // needed
            $data['Filename'] = $file;
            $data['key']      = $file;


            $data['CIID'] = $ciId;

            // optional
            $data['Title']        = $title;
            $data['Subject']      = $file;
            $data['Author']       = $stat['uid'];
            $data['CreationDate'] = date('Y-m-d H:i:s', $stat['mtime']);
            $data['ModDate']      = date('Y-m-d H:i:s', $stat['mtime']);


            $fileType = end(explode(".", $file));


            $fileSearch = new Util_Search_File();
            $fileSearch->createDocument($data, $fileType, $dir .'/'. $file);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }
    }

    public function recreateindexAction()
    {
        $fileSearch = new Util_Search_File();
        $fileSearch->recreateIndex();
        exit;
    }


    public function editAction()
    {
        $this->view->headScript()->appendFile(APPLICATION_URL . 'js/tinymce/jscripts/tiny_mce/tiny_mce.js', 'text/javascript');
        $this->_helper->layout->setLayout('popup');

        $file     = $this->_getParam('file');
        $fileType = $this->_getParam('filetype');
        $ciId     = $this->_getParam('ciId');
        $editor   = $this->_getParam('editor');

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);

        $useDefaultPath = $config->file->upload->path->default;
        $defaultFolder  = $config->file->upload->path->folder;

        $path = "";
        if ($useDefaultPath) {
            $path = APPLICATION_PUBLIC . $defaultFolder;
        } else {
            $path = $config->file->upload->path->custom;
        }

        $folder = $config->file->upload->$fileType->folder;

        if ($ciId) {
            $folder = $folder .'/'. $ciId;
        }

        try {
            $myFile = $path . $folder .'/'. $file;

            $logger = Zend_Registry::get('Log');
            $logger->log($myFile);


            $form = new Form_Fileupload_Edit($this->translator, $editor);

            if ($this->_request->isPost()) {
                $formData = $this->_request->getPost();

                $content = $formData['file'];
                $content = utf8_encode($content);

                $fh = fopen($myFile, 'w');
                fwrite($fh, $content);
                fclose($fh);

                $command = 'dos2unix ' . $myFile;
                system($command);

                chmod($myFile, 0777);

                $this->view->onLoad = "javascript:finalizeFileUpdate()";

            } else {
                $content = file_get_contents($myFile);
                $form->populate(array('file' => $content));
            }


        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            $notification['error'] = $this->translator->translate('fileUpdateFailed');
            $this->_helper->FlashMessenger($notification);
        }

        $this->view->form     = $form;
        $this->view->file     = $file;
        $this->view->filetype = $fileType;
        $this->view->ciId     = $ciId;
    }

    /* handling fileuploads for CIs */

    /** AJAX **/
    public function ciattachmentAction()
    {
        // expect a type
        $this->logger->log('ciattachment action has been invoked', Zend_Log::DEBUG);
        $fileType    = $this->_getParam('filetype');
        $attributeId = $this->_getParam('attributeId');
        $ciId        = $this->_getParam('ciId');

        if (!$ciId) {
            $ciId = $this->_getParam('ciid');
        }

        $key = ($this->_getParam('genId')) ? $this->_getParam('genId') : '';

        try {
            $config         = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
            $useDefaultPath = $config->file->upload->path->default;
            $defaultFolder  = $config->file->upload->path->folder;
            $customPath     = $config->file->upload->path->custom;

            $enabled     = $config->file->upload->$fileType->enabled;
            $folder      = $config->file->upload->$fileType->folder;
            $maxfilesize = $config->file->upload->$fileType->maxfilesize;
        } catch (Exception $ex) {
            $this->logger->log("CI ATTACHMENT Error reading config file: " . $ex, Zend_Log::WARN);
        }

        if (!isset($defaultFolder) || !$defaultFolder) {
            $defaultFolder = '_uploads/';
        }
        if (!isset($customPath) || !$customPath) {
            $customPath = '_uploads/';
        }

        $path = "";
        if (isset($useDefaultPath) && $useDefaultPath) {
            $path = APPLICATION_PUBLIC . $defaultFolder;
        } else {
            $path = $customPath;
        }


        // setting default value
        if (!isset($enabled) || !$enabled) {
            $enabled = false;
        }
        if (!isset($folder) || !$folder) {
            $folder = $fileType;
        }
        if (!isset($maxfilesize) || !$maxfilesize) {
            $maxfilesize = 52428800;
        }

        /* if ciid ( => ci exists) check if folder exists for uploads for this ci (not the case if there is no attachment when ci is created)
         *      if yes use that path as destination for fileupload
         *      if not, create folder and then use that path as destination for fileupload
         */
        if ($ciId) {
            $ci_Path = $folder .'/'. $ciId;
            if (!is_dir($path . $ci_Path)) {
                if (mkdir($path . $ci_Path, 0777)) {
                    $folder = $ci_Path;
                }
            } else {
                $folder = $ci_Path;
            }
        }

        if ($this->_request->isPost()) {
            $returnMsg = null;
            $upload    = new Zend_File_Transfer();
            $upload->receive();
            $fileName = $upload->getfileName();

            // generate prefix for the uploaded file
            $date = date("YmdHms\_");

            // rename file
            $currentFilename = array_pop(explode('/', $fileName));
            $currentFilename = filter_var($currentFilename, FILTER_UNSAFE_RAW);
            $newFilename  = Util_FileUpload::sanitizeFilename($currentFilename);

            if (!is_file($fileName) || !rename($fileName, $path . $folder .'/'. $newFilename)) {
                $this->logger->log("CI ATTACHMENT Rename ERROR", Zend_Log::ERR);

                // return error + message
                $this->getResponse()->setHttpResponseCode(500);
                $returnMsg = array($this->translator->_('errorOccurred'));
            }

            chmod($path . $folder .'/'. $newFilename, 0755);

            $dos2unix  = false;
            $extension = array_pop(explode(".", $newFilename));
            $extension = strtolower($extension);

            switch ($extension) {
                case 'txt':
                    $dos2unix = true;
                    break;
                default:
                    $dos2unix = false;
            }

            if ($dos2unix) {
                $command = 'dos2unix ' . $path . $folder .'/'. $newFilename;
                system($command);
            }

            $descriptionProperty = $attributeId . $key . 'description';
            $filenameProperty    = $attributeId . $key . 'filename';
            if (!isset($returnMsg)) {
                $returnMsg = array('status' => 'OK', 'filenameProperty' => $filenameProperty, 'newFilename' => $newFilename, 'descriptionProperty' => $descriptionProperty, 'oldFilename' => $currentFilename);
            }

            $this->_helper->json($returnMsg);


        } /*else {
                $this->_helper->json(array('status' => 'error'));
            }*/

    }

    /* CI Attachment delete file
    * currently not in use
    */
    /** AJAX **/
    public function unlinkfileAction()
    {
        $file = $this->_getParam('filename');
        $ciid = $this->_getParam('ciid');

        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/fileupload.ini', APPLICATION_ENV);
        $defaultFolder = $config->file->upload->path->folder;
        $folder        = $config->file->upload->attachment->folder;

        $path = APPLICATION_PUBLIC . $defaultFolder . $folder . '/';

        if ((!empty($ciid)) && ($ciid !== undefined || $ciid !== 'undefined')) {
            $path .= $ciid . '/';
        }
        $path .= $file;

        //$this->logger->log('unlinkfile path: ' . $path, Zend_Log::CRIT);

        if (unlink($path)) {
            $this->_helper->json(array('status' => 'OK'));
        } else {
            $this->_helper->json(array('status' => 'error', 'message' => $file . ' could not be deleted'));
        }
    }

}
