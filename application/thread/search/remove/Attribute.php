<?php

include('config.php');
require_once(APPLICATION_PATH . '/models/mapping/Ci.php');
require_once(APPLICATION_PATH . '/models/mapping/CiAttribute.php');
require_once(APPLICATION_PATH . '/models/mapping/Attribute.php');
require_once(APPLICATION_PATH . '/thread/ThreadInstance.php');

class Attribute extends ThreadInstance
{

    private $result;

    public function __construct($search, $sessionId)
    {
        $this->setup();
        $this->search($search, $sessionId);
    }


    function search($searchstring, $sessionId)
    {
        $searchstring = utf8_decode($searchstring);
        $config       = new Zend_Config_Ini(APPLICATION_PATH . '/configs/search.ini', APPLICATION_ENV);

        $messungStart = strtok(microtime(), " ") + strtok(" ");


        $name        = $config->search->db->attribute->name->enabled;
        $description = $config->search->db->attribute->description->enabled;
        $note        = $config->search->db->attribute->note->enabled;

        $sql = "
		SELECT 
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
		FROM " . Db_Attribute::TABLE_NAME . " ";


        if ($name) {
            $sql .= " WHERE " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NAME . " LIKE '" . $searchstring . "' ";
        }

        if ($description) {
            if ($name) {
                $sql .= " OR " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            }
        }

        if ($note) {
            if ($name || $description) {
                $sql .= " OR " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NOTE . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::NOTE . " LIKE '" . $searchstring . "' ";
            }
        }

        $advArray = $this->db->fetchAll($sql);

        $implAray = array();
        foreach ($advArray as $adv) {
            array_push($implAray, $adv['id']);
        }
        unset($advArray);

        if (count($implAray) < 1) {
            $messungEnde = strtok(microtime(), " ") + strtok(" ");
            $this->log->log('Attribute Search took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

            $this->result = array();
            return;
        }

        $advList = implode(',', $implAray);


        $sql = "
		DELETE FROM search_result WHERE session = '$sessionId' AND ci_id IN (
		SELECT distinct " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id  
		FROM " . Db_Ci::TABLE_NAME . "
		INNER JOIN " . Db_CiAttribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
		
		WHERE " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . " IN (" . $advList . ") 
		  )
		";

        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            $this->log->log($e, Zend_Log::INFO);
        }
        $messungEnde = strtok(microtime(), " ") + strtok(" ");
        $this->log->log('Attribute Search took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

        $this->result = array();
    }

    function getResult($command)
    {
        //$this->log->log('command: '.$command, Zend_Log::ERR);
        switch ($command) {
            case "get":
                $this->response("ok", $this->result);
                return;
                break;
            case "quit":
                exit;
            case "ping":
                $this->log->log('ping around the world??', Zend_Log::INFO);
                break;
            default:
                $this->response("err", "bad request - $command");
                return;
        }
    }
}

$attribute = new Attribute($search, $sessionId);
$attribute->getResult('get');
return;