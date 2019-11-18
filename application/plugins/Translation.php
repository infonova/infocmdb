<?php

/**
 * Plugin_Translation
 *
 *
 *
 */
class Plugin_Translation extends Zend_Controller_Plugin_Abstract
{

    private $translator           = null;
    private $translatorProperties = null;
    private $languagePath         = null;

    private $logger = null;


    /**
     * preDispatch
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {

        $this->logger = Zend_Registry::get('Log');
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();

        if (is_null($this->translatorProperties)) {
            $this->translatorProperties = new Zend_Config_Ini(APPLICATION_PATH . '/configs/translation.ini', APPLICATION_ENV);
            $this->logger->log('Loaded Translation Properties', Zend_Log::DEBUG);
            $this->languagePath = $this->translatorProperties->translation->dir;
        }


        // init the translator
        if (is_null($this->translator)) {
            $cacheDir = $this->translatorProperties->translation->cache->dir;
            $lifetime = $this->translatorProperties->translation->lifetime;
            $useApc   = $this->translatorProperties->translation->cache->apc;

            $this->enableTranslationCache($cacheDir, $lifetime, $useApc);
            $this->logger->log('Initialized Translation Cache', Zend_Log::DEBUG);

            // initializing the translator
            $this->translator = new Zend_Translate('csv', $this->languagePath . '/de/global_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/global_en.csv', 'en');
            $this->translator->addTranslation($this->languagePath . '/de/form_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/form_en.csv', 'en');
            if (is_file(APPLICATION_PUBLIC . '/translation/de/global_de.csv'))
                $this->translator->addTranslation(APPLICATION_PUBLIC . '/translation/de/global_de.csv', 'de');
            if (is_file(APPLICATION_PUBLIC . '/translation/en/global_en.csv'))
                $this->translator->addTranslation(APPLICATION_PUBLIC . '/translation/en/global_en.csv', 'en');

            $language = 'de';
            if (
                isset($options['resources']) &&
                isset($options['resources']['view']) &&
                isset($options['resources']['view']['language']) &&
                $this->translator->isAvailable($options['resources']['view']['language'])
            ) {
                $language = $options['resources']['view']['language'];
            }

            $this->translator->setLocale($language);
            Zend_Locale::setDefault($language);

            $this->logger->log('reset local settings to ' . $this->translator->getLocale(), Zend_Log::DEBUG);
            $this->logger->log('Initialized Translator', Zend_Log::DEBUG);
        }

        Zend_Registry::set('Zend_Translate', $this->translator);
        Zend_Registry::set('Language_Path', $this->languagePath);
    }


    /**
     * this method enables the translation cache
     *
     * @param $cacheDir the directory where the cache files should be saved
     *
     * @return unknown_type
     */
    private function enableTranslationCache($cacheDir, $lifetime, $useApc = false)
    {

        $frontendOptions = array(
            'lifetime'                => $lifetime,
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

        try {
            Zend_Translate::setCache($cache);
        } catch (Zend_Cache_Exception $e) {
            // TODO: handle exception
        }
    }
}