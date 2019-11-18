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

        try {
            // MYSQL BUG - sucks ass but avoids subquery performance issues (should be fixed in 6.0)
            $uniqueId = uniqid();

            $sql = "
			INSERT INTO search_temp(session, uniqueId, ci_id)
				SELECT 
					'$sessionId' as session,
					'$uniqueId' as uniqueId,
					" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " AS ci_id 
					FROM " . Db_CiAttribute::TABLE_NAME . "
					WHERE (" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . " LIKE '" . $searchstring . "' 
							OR " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_DEFAULT . " LIKE '" . $searchstring . "'
							)
						  );
				";
            $this->db->query($sql);

            $sql = "
			DELETE FROM search_result WHERE session = '$sessionId' AND ci_id NOT IN (
				SELECT ci_id 
					FROM search_temp
					WHERE session = '$sessionId'
					AND uniqueId = '$uniqueId'
			);
					";

            $this->db->query($sql);
        } catch (Exception $e) {
            $this->log->log($e, Zend_Log::INFO);
        }
        $messungEnde = strtok(microtime(), " ") + strtok(" ");
        $this->log->log('text restrict took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

        $this->response('ok', array());
        return;

    }
}


$attribute = new CiAttribute();
$attribute->search($search, $sessionId);