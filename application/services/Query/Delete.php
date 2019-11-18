<?php

/**
 *
 *
 *
 */
class Service_Query_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3003, $themeId);
    }


    public function deleteQuery($queryId)
    {
        $queryDaoImpl = new Dao_Query();
        return $queryDaoImpl->deleteQuery($queryId);
    }

}