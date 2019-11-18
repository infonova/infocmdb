<?php

/**
 *
 *
 *
 */
class Service_Theme_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2501, $themeId);
    }


    /**
     * retrieves a list of themes by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getThemeList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/theme.ini', APPLICATION_ENV);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['theme'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('Theme page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $themeDaoImpl = new Dao_Theme();
        $select       = $themeDaoImpl->getThemesForPaginator($orderBy, $direction, $filter);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return $paginator;
    }


    /**
     * @param string $filter
     */
    public function getFilterForm($filter = null)
    {
        $form = new Form_Filter($this->translator);

        if ($filter) {
            $form->populate(array('search' => $filter));
        }
        return $form;
    }


    /**
     * retrieves a single Theme
     *
     * @param int $themeId
     */
    public function getTheme($themeId)
    {
        try {
            $themeDaoImpl = new Dao_Theme();
            $ret          = $themeDaoImpl->getTheme($themeId);
            if (!$ret) {
                throw new Exception_Theme_RetrieveNotFound();
            }
            return $ret;
        } catch (Exception $e) {
            if ($e instanceof Exception_Theme)
                throw $e;
            throw new Exception_Theme_RetrieveFailed($e);
        }

    }

    /**
     * retrieves a single Theme
     *
     * @param int $themeId
     */
    public function getExtendedTheme($themeId)
    {
        try {
            $themeDaoImpl = new Dao_Theme();
            $ret          = $themeDaoImpl->getExtendedTheme($themeId);
            if (!$ret) {
                throw new Exception_Theme_RetrieveNotFound();
            }
            return $ret;
        } catch (Exception $e) {
            if ($e instanceof Exception_Theme)
                throw $e;
            throw new Exception_Theme_RetrieveFailed($e);
        }

    }

    public function getCurrentMenus($themeId)
    {
        $menuDaoImpl = new Dao_Menu();
        $menuList    = $menuDaoImpl->getMenusByThemeId($themeId);

        return $menuList;
    }

    /**
     * retrieve Theme-Menu Mapping
     *
     * @param int $themeId
     *
     * @return array
     */
    public function getThemeMenus($themeId)
    {
        try {
            $themeDaoImpl = new Dao_Theme();
            $ret          = $themeDaoImpl->getThemeMenusByThemeId($themeId);
            return $ret;
        } catch (Exception_Theme $e) {
            throw new Exception_Theme_RetrieveNotFound($e);
        } catch (Exception $e) {
            if ($e instanceof Exception_Theme)
                throw $e;
            throw new Exception_Theme_RetrieveFailed($e);
        }

    }
}