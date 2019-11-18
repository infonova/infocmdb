<?php

/**
 *
 *
 *
 */
class Service_Favourites_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2902, $themeId);
    }

    public function addCiToFavourites($ciId, $userId, $group = 'default')
    {
        $ciDao = new Dao_Ci();
        $ciDao->addCiToFavourites($ciId, $userId, $group = 'default');
    }
}