<?php

include('config.php');
require_once(APPLICATION_PATH . '/models/mapping/Ci.php');
require_once(APPLICATION_PATH . '/models/mapping/CiAttribute.php');
require_once(APPLICATION_PATH . '/models/mapping/AttributeDefaultValues.php');
require_once(APPLICATION_PATH . '/thread/ThreadInstance.php');

class CiAttribute extends ThreadInstance
{

    public function __construct()
    {
        $this->setup();
    }


    function search($searchstring, $sessionId)
    {
        $messungStart = strtok(microtime(), " ") + strtok(" ");
        $searchstring = utf8_decode($searchstring);

        $sql = "
			INSERT INTO search_result(session, ci_id, citype_id)
				SELECT '$sessionId',
				" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
				" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
				FROM " . Db_CiAttribute::TABLE_NAME . "
				LEFT JOIN " . Db_Ci::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "  
				WHERE (" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . " LIKE '" . $searchstring . "' 
						OR " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_DEFAULT . " LIKE '" . $searchstring . "' 
					  )
				GROUP BY " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
				";
        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            $this->log->log($e, Zend_Log::INFO);
        }
        $messungEnde = strtok(microtime(), " ") + strtok(" ");
        $this->log->log('text/default Search took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

        $this->response('ok', array());
        return;

    }
}


$attribute = new CiAttribute();
$attribute->search($search, $sessionId);