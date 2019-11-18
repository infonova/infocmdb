<?php

class Service_Searchlist_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2401, $themeId);
    }


    public function getSearchList()
    {
        $searchListDaoImpl = new Dao_SearchList();
        $searchLists       = $searchListDaoImpl->getSearchLists();

        $ciTypeDaoImpl = new Dao_CiType();
        $ciTypes       = $ciTypeDaoImpl->getCiTypeRowset();

        $form = $this->getOverviewForm($ciTypes);

        $newTypeList = array();
        foreach ($ciTypes as $type) {
            $newTypeList[$type[Db_CiType::ID]][Db_CiType::ID]          = $type[Db_CiType::ID];
            $newTypeList[$type[Db_CiType::ID]][Db_CiType::NAME]        = $type[Db_CiType::NAME];
            $newTypeList[$type[Db_CiType::ID]][Db_CiType::DESCRIPTION] = $type[Db_CiType::DESCRIPTION];
            $newTypeList[$type[Db_CiType::ID]][Db_CiType::NOTE]        = $type[Db_CiType::NOTE];
        }
        unset($ciTypes);

        foreach ($searchLists as $list) {
            $newTypeList[$list[Db_SearchList::CI_TYPE_ID]]['searchList'] = $list;
        }


        return array(
            'form'        => $form,
            'ciTypes'     => $newTypeList,
            'searchLists' => $searchLists,
        );
    }


    public function getOverviewForm($ciTypes)
    {
        return new Form_Searchlist_Overview($this->translator, $ciTypes);
    }

}