<?php

class Service_Index_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 401, $themeId);
    }


    // gather all root citypes ==> source for displaying in index page 
    public function getRootCitypes()
    {

        //TODO write a new CiType DAO

        $serviceCitype = new Service_Citype_Get($this->translator, $this->logger, parent::getThemeId());
        $daoCitype     = new Dao_CiType();
        $rootCitypes   = $daoCitype->getRootCiTypesCiCount();


        return $rootCitypes;

    }


}