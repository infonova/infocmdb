<?php

/**
 *
 *
 *
 */
class Service_Query_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3002, $themeId);
    }


    /**
     * retrieves Form for Query Create
     */
    public function getCreateQueryForm()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);
        return new Form_Query_Create($this->translator, $config);
    }


    public function createQuery($values)
    {
        $query                            = array();
        $query[Db_StoredQuery::NAME]      = $this->xssClean($values['name']);
        $query[Db_StoredQuery::NOTE]      = $this->xssClean($values['description']);
        $query[Db_StoredQuery::QUERY]     = $values['query'];
        $query[Db_StoredQuery::IS_ACTIVE] = '1';

        $queryDaoImpl = new Dao_Query();
        return $queryDaoImpl->insertQuery($query);
    }

}