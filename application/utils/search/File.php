<?php

require_once('Zend/Search/Lucene.php');

class Util_Search_File
{

    /** @var Zend_Log */
    protected $logger;
    private   $config          = null;
    protected $indexingEnabled = false;
    protected $index;

    public function __construct()
    {
        $this->config          = new Util_Config('fileupload.ini', APPLICATION_ENV);
        $this->indexingEnabled = $this->config->getValue('file.search.index.enabled', false, Util_Config::BOOL);
        $this->logger          = Zend_Registry::get('Log');

        $this->openIndex();
    }


    private function openIndex()
    {
        if ($this->indexingEnabled === true) {
            $indexPath = $this->config->getValue('file.search.index.path', APPLICATION_PATH . '/../data/cache/search/');
            try {
                $this->index = Zend_Search_Lucene::open($indexPath);
            } catch (Zend_Search_Lucene_Exception $e) {
                $this->logger->log($e->getMessage(), Zend_Log::ERR);
                try {
                    $this->index = Zend_Search_Lucene::create($indexPath);
                } catch (Zend_Search_Lucene_Exception $e) {
                    throw new Exception(
                        'Couldn\'t open or create index in: ' . $indexPath
                    );
                }
            }
        }
    }


    private function closeIndex()
    {
        if ($this->indexingEnabled === true) {
            try {
                if ($this->index)
                    $this->index->removeReference();
            } catch (Exception $e) {
                // bad
            }
        }
    }

    public function createDocument($data, $fileType, $fileName)
    {
        if ($this->indexingEnabled === true) {
            if (!is_array($data)) {
                throw new Exception(
                    'Argument for createDocument isn\t an Array'
                );
            }

            $doc = null;
            switch ($fileType) {

                case 'docx':
                    $doc = new Util_Search_Document_Docx($data, $fileName);
                    break;
                case 'pptx':
                    $doc = new Util_Search_Document_Pptx($data, $fileName);
                    break;
                case 'xlsx':
                    $doc = new Util_Search_Document_Xlsx($data, $fileName);
                    break;
                case 'html':
                    $doc = new Util_Search_Document_Html($data, $fileName);
                    break;
                case 'pdf':
                    $doc = new Util_Search_Document_Pdf($data, $fileName);
                    break;
                case 'txt':
                    $data['Contents'] = file_get_contents($fileName);
                    $doc              = new Util_Search_Document_Txt($data);
                    break;
                default:
                    return true;
            }

            $this->index->addDocument($doc);
            $this->index->commit();
            return true;
        }
    }


    public function deleteDocument($selector)
    {
        if ($this->indexingEnabled === true) {
            if (!is_array($selector)) {
                throw new Exception(
                    'Argument for deleteDocument isn\'t an Array'
                );
            }
            if (count($selector) != 2) {
                throw new Exception(
                    'Argument for deleteDocument isn\'t a valid Array'
                );
            }
            $hits = $this->index->find(implode(':', $selector));
            foreach ($hits as $hit) {
                $this->index->delete($hit->id);
            }
            return true;
        }
    }


    public function updateDocument($selector, $data)
    {
        if ($this->indexingEnabled === true) {
            $this->deleteDocument($selector);
            $this->createDocument($data);
            return true;
        }
    }

    public function search($searchstring)
    {
        if ($this->indexingEnabled === true) {
            Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0);
            $hits = null;

            $searchstring = utf8_decode($searchstring);


            try {
                $hits = $this->index->find($searchstring);
            } catch (Zend_Search_Lucene_Exception $ex) {
                $hits = array();
            }

            return $hits;
        }
    }


    public function recreateIndex()
    {
        if ($this->indexingEnabled === true) {
            // delete old entries??
            $this->closeIndex();
            $indexPath = $this->config->getValue('file.search.index.path', APPLICATION_PATH . '/../data/cache/search/');
            foreach (glob($indexPath . '/*.*') as $indexFile) {
                if (unlink($indexFile) === false) {
                    $this->logger->log(
                        sprintf('Util_Search_File::recreateIndex: Failed to unlink file: %s', $indexFile),
                        Zend_Log::ERR
                    );
                }
            }

            $this->openIndex();
            $baseFolder = null;
            // get directory
            if ($this->config->getValue('file.upload.path.default', true, Util_Config::BOOL)) {
                $baseFolder = APPLICATION_PUBLIC .'/'. $this->config->getValue('file.upload.path.folder', '_uploads/');
            } else {
                $baseFolder = $this->config->getValue('file.upload.path.custom', '');
            }

            $this->handleFolder($baseFolder . $this->config->getValue('file.upload.attachment.folder', 'attachment'));

            /*
             * The optimize-method merges all segments created by Lucene.
             * Segments are created every time a index is opened and closed again. (e.g. after file-upload)
             * Because we're recreating the full index and opening only one index in this process, there is only one segment.
             * An optimize-call is not necessary!
             */
            //$this->index->optimize();
        }
    }


    private function handleFolder($dir)
    {
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_file($dir .'/'. $file) && substr($file, 0, 1) != '.') {
                        $ciId = basename(dirname($dir .'/'. $file));
                        $this->handleFile($dir, $file, $ciId);
                    } else if (is_dir($dir .'/'. $file) && substr($file, 0, 1) != '.') {
                        $this->handleFolder($dir .'/'. $file);
                    }
                }
                closedir($dh);
            }
        }// else???
    }

    private function handleFile($dir, $file, $ciId)
    {
        $stat = stat($dir .'/'. $file);

        // store the information in array and add to index
        $data = array();

        $title = $file;
        if ($ciId) {
            $daoCi       = new Dao_Ci();
            $ciAttribute = $daoCi->getAttachmentCiAttribute($ciId, $file);
            $title       = $ciAttribute[Db_CiAttribute::NOTE];

            if (!$title)
                $title = $file;
        }

        // needed
        $data['Filename'] = $file;
        $data['key']      = $file;
        $data['CIID']     = $ciId;
        // optional
        $data['Title']        = $title;
        $data['Subject']      = $file;
        $data['Author']       = $stat['uid'];
        $data['CreationDate'] = date('Y-m-d H:i:s', $stat['mtime']);
        $data['ModDate']      = date('Y-m-d H:i:s', $stat['mtime']);
        $data['Size']         = $stat['size'];

        $fileType = end(explode(".", $file));

        $this->createDocument($data, $fileType, $dir .'/'. $file);
    }
}