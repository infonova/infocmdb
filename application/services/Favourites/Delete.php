<?php

/**
 *
 *
 *
 */
class Service_Favourites_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2904, $themeId);
    }

    public function removeCiFromFavourites($ciId, $userId)
    {
        $ciDao = new Dao_Ci();
        $ciDao->removeCiFromFavourites($ciId, $userId);
    }
}