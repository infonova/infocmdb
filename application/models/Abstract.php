<?php

/**
 *    Interface for DAO Layer. May be abstract?
 *
 *
 *
 */
class Dao_Abstract
{

    /** @var Zend_Db_Adapter_Abstract $db*/
    protected $db = null;

    public function __construct($enableMySQLBuffer = true)
    {
        $this->db = Zend_Registry::get('db');

        //enabling buffer causes huge memory usage, but increases speed on small operations
        if ($enableMySQLBuffer == false) {
            $this->db->getConnection()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
    }

    public function __destruct()
    {
        $this->db = null;
    }

    public function reconnect()
    {
        $this->db->closeConnection();
        $this->db->getConnection();
    }

}