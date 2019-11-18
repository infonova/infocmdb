<?php

/**
 * Plugin_Db
 *
 * This class is used to establish a DB-connection with the DB-config proerties stored in the /configs/database.ini file
 *
 */
class Plugin_Db extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();

        try {
            $db = Zend_Db::factory($options['database']['adapter'], $options['database']['params']);
            $db->getConnection();

            $useApc = $options['database']['cache']['apc'];

            if (isset($options['isConsole']) && $options['isConsole']) {
                $this->enableDbCache($options['database']['console']['cache']['dir'], $useApc);
            } else {
                $this->enableDbCache($options['database']['cache']['dir'], $useApc);
            }
            Zend_Db_Table_Abstract::setDefaultAdapter($db);
            Zend_Registry::set('db', $db);

            // this query is executed to verify the DB connection and to prevent encoding errors
            $db->query("SET NAMES 'utf8'");
        } catch (Zend_Db_Adapter_Exception $e) {
            // most likely thrown if DB connection failed. either invalid permissions or configuration
            throw new Exception_Db_DbNotFound($e);;
        } catch (Zend_Cache_Exception $e) {
            // local DB-cache could not be created. check read/write permission of data folder & subfolder
            throw new Exception_Db_CachingError($e);
        } catch (Zend_Exception $e) {
            // either uncatched db connection error or missing/invalid zend framework settings
            throw new Exception_Db_ConnectionError($e);
        } catch (Exception $e) {
            // not a Zend exception. may be an environmental problem. check if system is setup correctly
            throw new Exception_Db_Unknown($e);
        }

    }


    /**
     * this method creates an internal cache for metadata information of
     * ALL used tableobjects.
     *
     * Tableinformations are no longer retrieved every time a DB-Query is
     * executed, which increases the performance of the used DB-Layer.
     *
     * @param String $cacheDir the directory to store the cache files
     */
    private function enableDbCache($cacheDir, $useApc = false)
    {
        $frontendOptions = array(
            'lifetime'                => 3000,
            'automatic_serialization' => true,
            'cache_id_prefix'         => str_replace('/', '', APPLICATION_FOLDER),
        );

        $backendOptions = array(
            'cache_dir' => $cacheDir,
        );

        $out = 'File';
        if ($useApc)
            $out = 'APC';
        $cache = Zend_Cache::factory('Core',
            $out,
            $frontendOptions,
            $backendOptions);

        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
    }
}