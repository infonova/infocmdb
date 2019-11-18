<?php

/**
 *
 *
 *
 */
class Service_Event_Get extends Service_Abstract
{


    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3701, $themeId);
    }


    public function getEventList($page, $orderBy = null, $direction = null, $ciid = null, $filter = null)
    {
        try {
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/event.ini', APPLICATION_ENV);

            $itemsCountPerPage = $config->pagination->itemsCountPerPage;
            $itemsPerPage      = $config->pagination->itemsPerPage;
            $scrollingStyle    = $config->pagination->scrollingStyle;
            $scrollingControl  = $config->pagination->scrollingControl;


            $form   = new Form_Event_Filter($this->translator);
            $select = array();
            if ($filter) {
                $filterArray           = array();
                $filterArray['search'] = $filter;
                $form->populate($filterArray);
            }


            $dao    = new Dao_Event();
            $select = $dao->getEventsForPagination($orderBy, $direction, $ciid, $filter);

            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage($itemsCountPerPage);

            Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
            Zend_View_Helper_PaginationControl::setDefaultViewPartial(
                $scrollingControl
            );

            $result               = array();
            $result['searchForm'] = $form;
            $result['paginator']  = $paginator;
            return $result;
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
        }
    }

}