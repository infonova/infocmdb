<?php

include('config.php');
require_once(APPLICATION_PATH . '/models/mapping/Ci.php');
require_once(APPLICATION_PATH . '/models/mapping/CiType.php');
require_once(APPLICATION_PATH . '/thread/ThreadInstance.php');

class CiType extends ThreadInstance
{

    public function __construct()
    {
        $this->setup();
    }


    function search($searchstring, $sessionId)
    {
        $messungStart = strtok(microtime(), " ") + strtok(" ");
        $searchstring = utf8_decode($searchstring);
        $config       = new Zend_Config_Ini(APPLICATION_PATH . '/configs/search.ini', APPLICATION_ENV);


        $name        = $config->search->db->citype->name->enabled;
        $description = $config->search->db->citype->description->enabled;
        $note        = $config->search->db->citype->note->enabled;


        $sql = "
		SELECT 
		" . Db_CiType::TABLE_NAME . "." . Db_CiType::ID . "
		FROM " . Db_CiType::TABLE_NAME . " ";

        if ($name) {
            $sql .= " WHERE " . Db_CiType::TABLE_NAME . "." . Db_CiType::NAME . " LIKE '" . $searchstring . "' ";
        }

        if ($description) {
            if ($name) {
                $sql .= " OR " . Db_CiType::TABLE_NAME . "." . Db_CiType::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_CiType::TABLE_NAME . "." . Db_CiType::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            }
        }

        if ($note) {
            if ($name || $description) {
                $sql .= " OR " . Db_CiType::TABLE_NAME . "." . Db_CiType::NOTE . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_CiType::TABLE_NAME . "." . Db_CiType::NOTE . " LIKE '" . $searchstring . "' ";
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
            $this->log->log('citype Search took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

            $this->response('ok', array());
            return;
        }

        $advList = implode(',', $implAray);

        $sql = "
		INSERT INTO search_result(session, ci_id, citype_id)
		SELECT distinct '$sessionId',
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id, 
		" . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " AS citype_id 
		FROM " . Db_Ci::TABLE_NAME . "
		WHERE " . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " IN (" . $advList . ") 
		";


        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            $this->log->log($e, Zend_Log::INFO);
        }
        $messungEnde = strtok(microtime(), " ") + strtok(" ");
        $this->log->log('citype Search took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

        $this->response('ok', array());
        return;

    }
}


$attribute = new CiType();
$attribute->search($search, $sessionId);