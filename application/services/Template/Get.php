<?php

/**
 *
 *
 *
 */
class Service_Template_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3101, $themeId);
    }


    /**
     * retrieves a list of templates for pagination by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getTemplateList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $this->logger->log("Service_Template_Get: getTemplateList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $config            = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/template.ini', APPLICATION_ENV);
        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;


        $templateDao = new Dao_Template();

        $form   = new Form_Template_Filter($this->translator);
        $select = array();
        if ($filter) {
            $filterArray           = array();
            $filterArray['search'] = $filter;
            $form->populate($filterArray);
        }

        $select = $templateDao->getTemplatesForPagination($orderBy, $direction, $filter);
        unset($templateDao);


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
    }
}