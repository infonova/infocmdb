<?php

include('config.php');
require_once(APPLICATION_PATH . '/models/mapping/Ci.php');
require_once(APPLICATION_PATH . '/models/mapping/CiAttribute.php');
require_once(APPLICATION_PATH . '/models/mapping/Attribute.php');
require_once(APPLICATION_PATH . '/models/mapping/CiRelationType.php');
require_once(APPLICATION_PATH . '/models/mapping/CiRelation.php');
require_once(APPLICATION_PATH . '/thread/ThreadInstance.php');

class Relation extends ThreadInstance
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


        $name        = $config->search->db->relation->name->enabled;
        $description = $config->search->db->relation->description->enabled;
        $note        = $config->search->db->relation->note->enabled;

        $sql = "
		SELECT 
		" . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::ID . "
		FROM " . Db_CiRelationType::TABLE_NAME . " ";


        if ($name) {
            $sql .= " WHERE " . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::NAME . " LIKE '" . $searchstring . "' ";
        }

        if ($description) {
            if ($name) {
                $sql .= " OR " . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::DESCRIPTION . " LIKE '" . $searchstring . "' ";
            }
        }

        if ($note) {
            if ($name || $description) {
                $sql .= " OR " . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::NOTE . " LIKE '" . $searchstring . "' ";
            } else {
                $sql .= " WHERE " . Db_CiRelationType::TABLE_NAME . "." . Db_CiRelationType::NOTE . " LIKE '" . $searchstring . "' ";
            }
        }

        $rtArray = $this->db->fetchAll($sql);

        $implAray = array();
        foreach ($rtArray as $adv) {
            array_push($implAray, $adv['id']);
        }
        unset($rtArray);


        if (count($implAray) < 1) {
            $messungEnde = strtok(microtime(), " ") + strtok(" ");
            $this->log->log('relation Search took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

            $this->response('ok', array());
            return;
        }

        $advList = implode(',', $implAray);

        $sql = "
		SELECT distinct
		" . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::CI_ID_1 . " AS ci_id_a, 
		" . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::CI_ID_2 . " AS ci_id_b 
		FROM " . Db_CiRelation::TABLE_NAME . "
		
		WHERE " . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::CI_RELATION_TYPE_ID . " IN (" . $advList . ") 
		OR " . Db_CiRelation::TABLE_NAME . "." . Db_CiRelation::NOTE . " LIKE '" . $searchstring . "' ";

        $ciListArray = $this->db->fetchAll($sql);

        $advList = "0";
        foreach ($ciListArray as $adv) {
            $advList .= ', ' . $adv['ci_id_a'];
            $advList .= ', ' . $adv['ci_id_b'];
        }
        unset($ciListArray);


        $sql = "
		DELETE FROM search_result WHERE session = '$sessionId' AND ci_id IN (
		SELECT distinct " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " AS ci_id 
		FROM " . Db_Ci::TABLE_NAME . "
		WHERE " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . " IN (" . $advList . ") 
		 ) 
		";

        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            $this->log->log($e, Zend_Log::INFO);
        }
        $messungEnde = strtok(microtime(), " ") + strtok(" ");
        $this->log->log('relation Search took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

        $this->response('ok', array());
        return;

    }
}


$attribute = new Relation();
$attribute->search($search, $sessionId);