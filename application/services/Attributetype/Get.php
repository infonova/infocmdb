<?php

class Service_Attributetype_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 4101, $themeId);
    }

    /**
     * retrieves a list of attributes by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getAttributetypeList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $this->logger->log("Service_Attributetype: getAttributetypeList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        if (!$orderBy)
            $orderBy = Db_Menu::ORDER_NUMBER;
        $config            = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/attributetype.ini', APPLICATION_ENV);
        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('Service_Attributetype: getAttributetypeList page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $attributetypeDaoImpl = new Dao_AttributeType();

        $select = array();
        $select = $attributetypeDaoImpl->getAttributetypePagination($orderBy, $direction, $filter);

        unset($attributetypeDaoImpl);


        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
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

}