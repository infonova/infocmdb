<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class FavouritesController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/favourites_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/favourites_en.csv', 'en');
            parent::addUserTranslation('favourites');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function addAction()
    {
        $ciId  = $this->_getParam('ciid');
        $group = $this->_getParam('group');

        if (!$group)
            $group = 'default';

        $favouritesServiceCreate = new Service_Favourites_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $favouritesServiceCreate->addCiToFavourites($ciId, parent::getUserInformation()->getId(), $group);
        $this->_redirect('ci/detail/ciid/' . $ciId);
    }

    public function removeAction()
    {
        $ciId  = $this->_getParam('ciid');
        $group = $this->_getParam('group');

        if (!$group)
            $group = 'default';

        $favouritesServiceDelete = new Service_Favourites_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $favouritesServiceDelete->removeCiFromFavourites($ciId, parent::getUserInformation()->getId());
        $this->_redirect('favourites/index/group/' . $group);
    }

    public function indexAction()
    {
        $favouritesServiceGet = new Service_Favourites_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        // handle list action
        $color     = 'default';
        $page      = $this->_getParam('page');
        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $ciResult = $favouritesServiceGet->getFavouriteCiList($color, parent::getUserInformation()->getId(), parent::getUserInformation()->getThemeId(), parent::getCurrentProjectId(), $page, $orderBy, $direction);

        if (!$ciResult) {
            $this->view->noItems = true;
        } else {
            $paginator  = $ciResult['paginator'];
            $ciList     = $ciResult['ciList'];
            $numberRows = $ciResult['numberOfRows'];

            $this->view->color      = $color;
            $this->view->paginator  = $paginator;
            $this->view->numberRows = $numberRows;
            $this->view->valueList  = $ciList;
        }

    }
}