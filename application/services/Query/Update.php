<?php

/**
 *
 *
 *
 */
class Service_Query_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3003, $themeId);
    }


    /**
     * retrieves Form for Query Create
     */
    public function getUpdateQueryForm($queryId)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);

        $queryDaoImpl = new Dao_Query();
        $query        = $queryDaoImpl->getQueryById($queryId);

        if ($query[Db_StoredQuery::IS_DEFAULT]) {
            throw new Exception_Query_Forbidden();
        }

        $storedFormData                = array();
        $storedFormData['name']        = $query[Db_StoredQuery::NAME];
        $storedFormData['description'] = $query[Db_StoredQuery::NOTE];
        $storedFormData['query']       = $query[Db_StoredQuery::QUERY];

        $form = new Form_Query_Update($this->translator, $config);
        return array('form' => $form, 'formdata' => $storedFormData, 'query' => $query);
    }


    public function updateQuery($queryId, $values)
    {
        $query                        = array();
        $query[Db_StoredQuery::NAME]  = $this->xssClean($values['name']);
        $query[Db_StoredQuery::QUERY] = $values['query'];
        $query[Db_StoredQuery::NOTE]  = $this->xssClean($values['description']);

        $queryDaoImpl = new Dao_Query();
        return $queryDaoImpl->updateQuery($queryId, $query);
    }

}