<?php

include('config.php');
require_once(APPLICATION_PATH . '/models/mapping/Ci.php');
require_once(APPLICATION_PATH . '/models/mapping/CiAttribute.php');
require_once(APPLICATION_PATH . '/models/mapping/AttributeDefaultValues.php');
require_once(APPLICATION_PATH . '/models/mapping/Attribute.php');
require_once(APPLICATION_PATH . '/models/mapping/AttributeType.php');
require_once(APPLICATION_PATH . '/thread/ThreadInstance.php');

class CiAttributeCiType extends ThreadInstance
{

    public function __construct()
    {
        $this->setup();
    }


    function search($searchstring, $sessionId)
    {
        $messungStart = strtok(microtime(), " ") + strtok(" ");
        $searchstring = utf8_decode($searchstring);
        $sql          = "
		SELECT distinct
		" . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::VALUE . ", 
		" . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . " 
		FROM " . Db_AttributeDefaultValues::TABLE_NAME . " 
		INNER JOIN " . Db_Attribute::TABLE_NAME . " ON " . Db_AttributeDefaultValues::TABLE_NAME . "." . Db_AttributeDefaultValues::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
		INNER JOIN " . Db_AttributeType::TABLE_NAME . " ON " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . " = " . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::ID . "
		WHERE " . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::NAME . " LIKE 'ciType'  
		";

        $advArray = $this->db->fetchAll($sql);

        $resultArray = array();

        foreach ($advArray as $value) {

            $parts = explode(':', $value['value']);

            $ciTypeId = $parts[0];

            $attributes    = explode(',', $parts[1]);
            $attributeList = implode(',', $attributes);

            // suche pro cityp??
            $sql = "
				SELECT distinct
				" . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . "
				FROM " . Db_CiAttribute::TABLE_NAME . " 
				INNER JOIN " . Db_Attribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
				INNER JOIN " . Db_AttributeType::TABLE_NAME . " ON " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ATTRIBUTE_TYPE_ID . " = " . Db_AttributeType::TABLE_NAME . "." . Db_AttributeType::ID . "
				INNER JOIN " . Db_Ci::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
				
				
				Where " . Db_Ci::TABLE_NAME . "." . Db_Ci::CI_TYPE_ID . " = '" . $ciTypeId . "' 
				AND " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . " IN (" . $parts[1] . ")
				AND " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . " like '" . $searchstring . "'
				";


            $ciList = $this->db->fetchAll($sql);

            $ciArray = array();
            foreach ($ciList as $ci) {
                $ciArray[$ci['ci_id']] = $ci['ci_id'];
            }
            unset($ciList);

            if (count($ciArray) < 1) {
                $messungEnde = strtok(microtime(), " ") + strtok(" ");
                $this->log->log('ciattributecitype Search took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

                $this->response('ok', array());
                return;
            }

            $ciString = implode(',', $ciArray);

            $sql = "
				DELETE FROM search_result WHERE session = '$sessionId' AND ci_id IN (
				SELECT distinct " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " AS ci_id  
				FROM " . Db_CiAttribute::TABLE_NAME . " 
				INNER JOIN " . Db_Attribute::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::ATTRIBUTE_ID . " = " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . "
				INNER JOIN " . Db_Ci::TABLE_NAME . " ON " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::CI_ID . " = " . Db_Ci::TABLE_NAME . "." . Db_Ci::ID . "
				
				WHERE " . Db_Attribute::TABLE_NAME . "." . Db_Attribute::ID . " = '" . $value['id'] . "' 
				AND " . Db_CiAttribute::TABLE_NAME . "." . Db_CiAttribute::VALUE_TEXT . " IN (" . $ciString . ")
				)
				";
            try {
                $resList = $this->db->query($sql);
            } catch (Exception $e) {
                $this->log->log($e, Zend_Log::INFO);

            }
        }

        $messungEnde = strtok(microtime(), " ") + strtok(" ");
        $this->log->log('ciattributecitype Search took ' . number_format($messungEnde - $messungStart, 6) . ' seconds', Zend_Log::INFO);

        $this->response('ok', array());
        return;

    }
}


$attribute = new CiAttributeCiType();
$attribute->search($search, $sessionId);