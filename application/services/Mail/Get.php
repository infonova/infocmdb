<?php

/**
 *
 *
 *
 */
class Service_Mail_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3201, $themeId);
    }


    /**
     * retrieves a list of mail templates for pagination by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getMailList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $this->logger->log("Service_Mail_Get: getMailList('$page', '$orderBy', '$direction', '$filter') has been invoked", Zend_Log::DEBUG);

        $config            = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/mail.ini', APPLICATION_ENV);
        $itemsCountPerPage = $config->pagination->itemsCountPerPage;
        $itemsPerPage      = $config->pagination->itemsPerPage;
        $scrollingStyle    = $config->pagination->scrollingStyle;
        $scrollingControl  = $config->pagination->scrollingControl;


        $mailDao = new Dao_Mail();

        $form   = new Form_Mail_Filter($this->translator);
        $select = array();
        if ($filter) {
            $filterArray           = array();
            $filterArray['search'] = $filter;
            $form->populate($filterArray);
        }

        $select = $mailDao->getMailTemplatesForPagination($orderBy, $direction, $filter);
        unset($mailDao);


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

    public static function getMimeTranslation($mimeType)
    {
        $translator    = Zend_Registry::get('Zend_Translate');
        $mimeTypeParts = explode('/', $mimeType);

        $mimeTransKey = 'mailMimeType';
        foreach ($mimeTypeParts as $part) {
            $mimeTransKey .= ucfirst($part);
        }

        return $translator->translate($mimeTransKey);
    }

}