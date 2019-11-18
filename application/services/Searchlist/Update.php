<?php

class Service_Searchlist_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2403, $themeId);
    }


    public function updateSearchListStatus($formData, $searchLists)
    {
        $searchListDaoImpl = new Dao_SearchList();

        foreach ($searchLists as $list) {
            $ciType = $list[Db_SearchList::CI_TYPE_ID];

            if ($list[Db_SearchList::IS_ACTIVE] == '1' && (!$formData[$ciType] || $formData[$ciType] == '0')) {
                // deactivate
                $searchListDaoImpl->updateSearchListStatus($list[Db_SearchList::ID], '0');
            } else if ($list[Db_SearchList::IS_ACTIVE] == '0' && ($formData[$ciType] && $formData[$ciType] == '1')) {
                // activate
                $searchListDaoImpl->updateSearchListStatus($list[Db_SearchList::ID], '1');
            }
        }
    }


    public function getDetailForm($ciTypeId)
    {
        $searchListDaoImpl = new Dao_SearchList();

        $searchLists = array();
        if ($ciTypeId == 0) {
            $searchLists = $searchListDaoImpl->getSearchListAttributesDefault();
        } else {
            $searchLists = $searchListDaoImpl->getSearchListAttributesByCiTypeId($ciTypeId);
        }

        $config      = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pagination.ini', APPLICATION_ENV);
        $maxElements = $config->list->attribute->size;


        if (!$maxElements) {
            $maxElements = 10;
        }

        $form = new Form_Searchlist_Attribute($this->translator, $ciTypeId, $maxElements);

        $dbData = array();
        foreach ($searchLists as $list) {
            $dbData['scrollable']                                            = $list[Db_SearchList::IS_SCROLLABLE];
            $dbData['create_' . $list[Db_SearchListAttribute::ORDER_NUMBER]] = $list[Db_SearchListAttribute::ATTRIBUT_ID];
            $dbData['width_' . $list[Db_SearchListAttribute::ORDER_NUMBER]]  = $list[Db_SearchListAttribute::COLUMN_WIDTH];
        }
        return array(
            'form'        => $form,
            'maxElements' => $maxElements,
            'dbData'      => $dbData,
        );
    }

    public function updateSearchListAttributes($ciTypeId, $formData, $maxElements)
    {
        // check if exists
        $searchListDaoImpl = new Dao_SearchList();

        $child        = $searchListDaoImpl->getSearchListByCiTypeId($ciTypeId);
        $searchListId = $child[Db_SearchList::ID];

        $data                               = array();
        $data[Db_SearchList::IS_SCROLLABLE] = $formData['scrollable'];;
        $data[Db_SearchList::IS_ACTIVE]  = '1';
        $data[Db_SearchList::CI_TYPE_ID] = $ciTypeId;

        if (!$searchListId) {
            // create new
            $searchListId = $searchListDaoImpl->insertSearchList($data);
        } else {
            // delete configured search list attributes
            $searchListDaoImpl->updateSearchList($searchListId, $data);
            $searchListDaoImpl->deleteSearchListAttributes($searchListId);
        }

        // insert new attributes in search list attributes table
        for ($i = 1; $i <= $maxElements; $i++) {
            if ($formData['create_' . $i]) {
                $searchListDaoImpl->insertSearchListAttributes($searchListId, $formData['create_' . $i], $i, $formData['width_' . $i]);
            }
        }
    }

}