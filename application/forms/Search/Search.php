<?php

/**
 * This class is used to create the citype filter
 *
 * @param Zend_Translator the translator that should be used to translate the Form fields and the excpetion messages
 * @param configFile the configuration Item where the config information are stored
 *
 */
class Form_Search_Search extends Form_AbstractAppForm
{
    public function __construct($translator, $isFileSearch = false, $options = null)
    {
        parent::__construct($translator, $options);
        $this->setAttrib('id', 'search_form');
        $this->setName('search');
        $this->setAttrib('enctype', 'multipart/form-data');
        $this->setMethod('GET');

        if ($isFileSearch) {
            $this->setAction(APPLICATION_URL . 'search/filesearch');
        } else {
            $this->setAction(APPLICATION_URL . 'search/index');
        }

        $searchField = new Zend_Form_Element_Text('searchstringAjax');
        $searchField->setLabel('searchstring');

        $this->addElement($searchField);

        $pageField = new Zend_Form_Element_Hidden('page');
        $this->addElement($pageField);

        $sessionField = new Zend_Form_Element_Hidden('session');
        $this->addElement($sessionField);

        $searchButton = new Zend_Form_Element_Submit('submit');
        $searchButton->setLabel('search');
        $searchButton->setAttrib('class', 'attribute_search_button');
        $destination = APPLICATION_URL . 'search/searchajax/isConsole/1/';

        $history = new Zend_Form_Element_Checkbox('history');
        $history->setLabel('history');
        $this->addElement($history);

        if (!$isFileSearch)
            $searchButton->setRequired(true);


        $this->addElement($searchButton);

        $fileUploadConfig    = new Util_Config('fileupload.ini', APPLICATION_ENV);
        $fileIndexingEnabled = $fileUploadConfig->getValue('file.search.index.enabled', false, Util_Config::BOOL);
        if ($fileIndexingEnabled === true) {
            $fileSearchButton = new Zend_Form_Element_Submit('filesearch');
            $fileSearchButton->setLabel('filesearch');
            //$fileSearchButton->setAttrib('OnClick', 'javascript:doFilesearch()');
            $fileSearchButton->setAttrib('class', 'attribute_search_button');

            $this->addElement($fileSearchButton);
        }


    }
}