<?php

/**
 * THIS IS AN ADMIN CLASS ONLY! be very careful when using this class
 *
 *
 *
 */
class Service_Admin_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 4101, $themeId);
    }

    /**
     *
     * get a list of all user that are currently logged in.
     *
     * @param unknown_type $page
     * @param unknown_type $orderBy
     * @param unknown_type $direction
     * @param unknown_type $filter
     */
    public function getSessionList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/admin.ini', APPLICATION_ENV);

        if (!$page)
            $page = 1;


        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;

        $adminDao = new Dao_Admin();
        $select   = $adminDao->getSessionForPagination($orderBy, $direction, $filter);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        return $paginator;
    }
}