<?php

/**
 *
 *
 *
 */
class Service_Attributegroup_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2701, $themeId);
    }


    /**
     * retrieves a list of attributegroups by the given restrictions
     *
     * @param int    $page      the page to display
     * @param string $orderBy   name to sort by
     * @param string $direction ASC/DESC
     * @param string $filter
     */
    public function getAttributeGroupList($page = null, $orderBy = null, $direction = null, $filter = null)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination/attributegroup.ini', APPLICATION_ENV);

        if (is_null($page)) {
            $this->logger->log('page var was null. using default value 1 for user display', Zend_Log::DEBUG);
            $page = 1;
        }

        $itemCountPerPageSession = new Zend_Session_Namespace('itemCountPerPage');
        $itemsCountPerPage       = $itemCountPerPageSession->itemCountPerPage['attributegroup'];

        $itemsPerPage     = $config->pagination->itemsPerPage;
        $scrollingStyle   = $config->pagination->scrollingStyle;
        $scrollingControl = $config->pagination->scrollingControl;

        $attributeGroupDaoImpl = new Dao_AttributeGroup();
        $select                = $attributeGroupDaoImpl->getAttributeGroupsForPagination($orderBy, $direction, $filter);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($itemsCountPerPage);

        Zend_Paginator::setDefaultScrollingStyle($scrollingStyle);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            $scrollingControl
        );

        $result              = array();
        $result['paginator'] = $paginator;
        return $result;
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
     * retrieves a single attributeGroup
     *
     * @param int $attributeGroupId
     */
    public function getAttributeGroup($attributeGroupId)
    {
        try {
            $attributeGroupDaoImpl = new Dao_AttributeGroup();
            $ret                   = $attributeGroupDaoImpl->getAttributeGroup($attributeGroupId);
            if (!$ret) {
                throw new Exception_AttributeGroup();
            }
            return $ret;
        } catch (Exception_AttributeGroup $e) {
            throw new Exception_AttributeGroup_RetrieveNotFound($e);
        } catch (Exception $e) {
            if (!($e instanceof Exception_AttributeGroup))
                throw new Exception_AttributeGroup_RetrieveFailed($e);
        }

    }

    public function getParentIfExists($attributeGroupId)
    {
        try {
            $attributeGroupDaoImpl = new Dao_AttributeGroup();
            $ret                   = $attributeGroupDaoImpl->getAttributeGroupParent($attributeGroupId);
            return $ret;
        } catch (Exception_AttributeGroup $e) {
            throw new Exception_AttributeGroup(Exception_AttributeGroup::$RETRIEVE_NOT_FOUND);
        } catch (Exception $e) {
            if (!($e instanceof Exception_AttributeGroup))
                throw new Exception_AttributeGroup(Exception_AttributeGroup::$RETRIEVE_FAILED);
        }

    }
}