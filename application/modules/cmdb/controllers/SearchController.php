<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class SearchController extends AbstractAppAction
{


    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/search_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/search_en.csv', 'en');
            parent::addUserTranslation('search');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }


    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);
        // action body

        $searchServiceGet = new Service_Search_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form             = $searchServiceGet->getSearchForm($isFileSearch = false);
        $searchstring     = $this->_getParam('searchstring');
        $searchstringAjax = $this->_getParam('searchstringAjax');

        if ($searchstringAjax)
            $searchstring = $searchstringAjax;
        $page = $this->_getParam('page');

        if ($this->_request->isPost() || !is_null($searchstring)) {
            $formData           = $this->_request->getPost();
            $this->view->result = $this->handleSearch($searchServiceGet, $form, $searchstring, $page, $formData);
        }

        $this->view->form = $form;
    }


    public function searchajaxAction()
    {
        $this->logger->log('ajax Search has been invoked', Zend_Log::DEBUG);
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $this->_helper->layout->setLayout('print', false);

        $searchServiceGet = new Service_Search_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $form             = $searchServiceGet->getSearchForm($isFileSearch = false);

        $searchstring = filter_var($this->_getParam('searchstring'), FILTER_DEFAULT);
        $page         = $this->_getParam('page');
        $session      = $this->_getParam('session');
        $history      = $this->_getParam('history') == 'true' ? true : false;


        $formData            = $this->_request->getPost();
        $formData['session'] = $session;
        $res                 = $this->handleSearch($searchServiceGet, $form, $searchstring, $page, $formData, true, $history);
        echo $res;
        exit;
        $this->_response->appendBody($res);
        $this->logger->log('ajax Search got response! GOGOGO', Zend_Log::INFO);

    }


    private function generateSearchForm($isFileSearch = false)
    {
        $form = new Zend_Form('search');
        $form->setTranslator($this->translator);
        $form->setName('search');

        if ($isFileSearch) {
            $form->setAction(APPLICATION_URL . 'search/filesearch');
        } else {
            $form->setAction(APPLICATION_URL . 'search/index');
        }

        $searchField = new Zend_Form_Element_Text('searchstring');
        $searchField->setLabel('searchstring');

        $form->addElement($searchField);

        $pageField = new Zend_Form_Element_Hidden('page');

        $form->addElement($pageField);

        $searchButton = new Zend_Form_Element_Submit('search');
        $searchButton->setLabel('search');
        $searchButton->setAttrib('class', 'attribute_search_button');

        if (!$isFileSearch)
            $searchButton->setRequired(true);

        $form->addElement($searchButton);


        $fileSearchButton = new Zend_Form_Element_Submit('filesearch');
        $fileSearchButton->setLabel('filesearch');
        $fileSearchButton->setAttrib('OnClick', 'javascript:doFilesearch()');
        $fileSearchButton->setAttrib('class', 'attribute_search_button');

        if ($isFileSearch)
            $fileSearchButton->setRequired(true);

        $form->addElement($fileSearchButton);

        return $form;
    }


    private function handleSearch(&$searchServiceGet, $form, $searchstring, $page, $formData, $isAjax = false, $history = false)
    {
        $view = new Zend_View();
        $view->setEscape('htmlentities');
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/search/');
        $view->setEncoding('UTF-8');
        $view->headMeta()->appendName('charset', "UTF-8");
        $view->doctype("HTML5");


        if ($formData['search']) {
            unset($formData['session']);
        }

        if (!$formData || !is_null($searchstring)) {
            $formData['searchstring'] = $searchstring;
            $formData['page']         = $page;

        }

        $session = $this->_getParam('sess');
        if (!$formData || !is_null($session)) {
            $formData['session'] = $session;
        }

        $messungStart = strtok(microtime(), " ") + strtok(" ");
        $searchResult = $searchServiceGet->handleSearchAction(parent::getUserInformation(), parent::getCurrentProjectId(), $form, $formData, $history);
        $messungEnde  = strtok(microtime(), " ") + strtok(" ");

        $searchTime = number_format($messungEnde - $messungStart, 6);
        $this->logger->log('overall Search for "' . $searchstring . '" took ' . $searchTime . ' seconds', Zend_Log::INFO);

        $newValueList       = $searchResult['items'];
        $view->searchstring = $searchResult['searchstring'];
        $view->paginator    = $searchResult['paginator'];
        $view->numberRows   = $searchResult['numberRows'];
        $view->session      = $searchResult['session'];
        $view->page         = $searchResult['page'];
        $view->history      = $searchResult['history'];
        $view->searchTime   = $searchTime;

        if ($searchResult['numberRows'] == 1) {
            foreach ($newValueList as $key => $list) {
                if (count($list['ciList']) == 1) {
                    // TODO: what to do with ajax requests???
                    if (!$isAjax)
                        $this->_redirect(APPLICATION_URL . 'ci/detail/ciid/' . $list['ciList'][0]['id']);
                }
            }
        }


        unset($searchResult);
        $view->posted    = true;
        $view->valueList = $newValueList;
        return $view->render('_result.phtml');
    }


    /**
     * this method is called if the filesearch button was pressed.
     */
    public function filesearchAction()
    {
        $this->logger->log('ajax filesearch has been invoked', Zend_Log::DEBUG);
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->setLayout('clean', false);

        $view = new Zend_View();
        $view->setEscape('htmlentities');
        $view->addScriptPath(APPLICATION_PATH . '/modules/cmdb/views/scripts/search');

        $form       = $this->generateSearchForm(true);
        $view->form = $form;

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if ($form->isValid($formData)) {
                $form->populate($formData);
                $values         = $form->getValues();
                $values['page'] = null;
            } else {
                $values = $form->getValues();
                $form->populate($formData);
            }

            $searchstring = filter_var($this->_getParam('searchstring'), FILTER_DEFAULT);

            $fileSearch = new Util_Search_File();
//			$fileSearch->recreateIndex();
            $hits = $fileSearch->search($searchstring);

            $view->numHits = count($hits);
            $view->hits    = $hits;

            echo $view->render('_filesearch.phtml');
            exit;
        }
    }


}