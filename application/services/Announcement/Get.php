<?php

/**
 *
 *
 *
 */
class Service_Announcement_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 1501, $themeId);
    }

    /**
     * retrieves a list of announcement by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter    is search string
     */
    public function getAnnouncementList($page = null, $orderBy, $direction, $filter = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/announcement.ini', APPLICATION_ENV);

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['announcement'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        if (is_null($page)) {
            $this->logger->log('Announcement page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        if (!$filter || $filter == 'Filter' || $filter == '%') {
            $filter = null;
        }
        if (is_string($filter)) {
            $filter = str_replace(array('%', '*'), array('\%', '%'), $filter);
        }

        $announcementDaoImpl = new Dao_Announcement();
        $select              = $announcementDaoImpl->getAllAnnouncements($filter, $orderBy, $direction);

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

    public function getAnnouncement($announcementId, $language = 'en')
    {
        $announcementDao = new Dao_Announcement();
        $row             = $announcementDao->getAnnouncementById($announcementId);

        $titleKey   = 'title_' . $language;
        $messageKey = 'message_' . $language;

        $row['title']   = '';
        $row['message'] = '';

        if (isset($row[$titleKey])) {
            $row['title'] = $row[$titleKey];
        }

        if (isset($row[$messageKey])) {
            $row['message'] = $row[$messageKey];
        }

        return $row;
    }

    public function getAllActiveAnnouncementIds()
    {
        $announcementUserDao = new Dao_AnnouncementUser();
        return $announcementUserDao->getAllActiveAnnouncementIds();
    }

    public function userHasAcceptedAnnouncement($userId, $announcementId)
    {
        $announcementUserDao = new Dao_AnnouncementUser();
        return $announcementUserDao->userHasAcceptedAnnouncement($userId, $announcementId);
    }

    public function userSetAnnouncementAction($isAccepted, $userId, $acceptedAnnouncementId)
    {
        $announcementUserDao = new Dao_AnnouncementUser();
        return $announcementUserDao->userSetAnnouncementAction($isAccepted, $userId, $acceptedAnnouncementId);
    }
}