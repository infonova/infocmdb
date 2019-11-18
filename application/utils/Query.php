<?php

class Util_Query
{


    public static function convertResult($obj, $method = null)
    {
        if (!$method)
            $method = 'xml';

        switch ($method) {
            case 'xml':
                $result = self::getXML($obj);
                break;
            case 'json':
                $result = self::getJSON($obj);
                break;
            case 'plain':
                $result = self::getPLAIN($obj);
                break;
            default:
                $result = self::getXML($obj);
                break;
        }


        return $result;
    }

    public static function getXML($obj)
    {


        $ciTypeDao    = new Dao_CiType();
        $attributeDao = new Dao_Attribute();


        $doc               = new DOMDocument();
        $doc->formatOutput = true;

        $result = $obj['data'];

        $cis = $result[0];
        if ($cis['ci_id']) {
            $hierachy = $ciTypeDao->retrieveCiTypeHierarchyByCiId($cis['ci_id']);

            $parent = array();

            $parent[0] = $doc;

            $j = 0;

            for ($i = sizeof($hierachy) - 1; $i >= 1; $i--) {

                $h = $hierachy[$i];

                $citype = $ciTypeDao->getCiType($h[0]);

                $xmltag = $citype[Db_CiType::TAG];
                if (!$xmltag || is_numeric($xmltag) || $xmltag == '')
                    $xmltag = $citype[Db_CiType::NAME];

                //$doc->createAttribute($xmltag);


                $parent[$j + 1] = $doc->createElement($xmltag);
                $parent[$j]->appendChild($parent[$j + 1]);

                $j++;

            }


            $parent = end($parent);
        } else {

            $parent = $doc;


        }
        if (is_array($result)) {
            foreach ($result as $value) {

                $ciid = $value['ci_id'];
                if ($ciid) {
                    $citype = $ciTypeDao->getCiTypeByCiId($ciid);


                    $xmltag = $citype[Db_CiType::TAG];

                    if (!$xmltag || is_numeric($xmltag) || $xmltag == '')
                        $xmltag = $citype[Db_CiType::NAME];

                } else {

                    $xmltag = 'record';

                }

                $element = $doc->createElement($xmltag);
                $parent->appendChild($element);


                foreach ($value as $key => $v) {


                    $xmltag_attribute = $attributeDao->getXMLTagbyAttributeName($key);
                    if (isset($xmltag_attribute) && $xmltag_attribute != '')
                        $key = $xmltag_attribute;

                    $key = $doc->createElement($key, $v);
                    $element->appendChild($key);


                }

            }
        }
        return $doc->saveXML();
    }

    public static function getJSON($obj)
    {
        if(!isset($obj) || !is_array($obj)) {
            return "";
        }

        if (array_key_exists('data', $obj)){
            foreach ($obj['data'] as $key => $val) {
                if ($val && is_array($val))
                    foreach ($val as $id => $idVal) {
                        $obj['data'][$key][$id] = $idVal;
                    }
            }
        }

        return json_encode($obj);
    }


    public static function getPlain($obj)
    {
        $ret  = null;

        if(!is_array($obj) || !array_key_exists('data', $obj)) {
            return $ret;
        }

        $data = $obj['data'];

        if ($data)
            foreach ($data as $result) {
                if ($ret)
                    $ret .= ' | ';

                foreach ($result as $key => $attribute) {
                    if (!isset($ret)) {
                        $ret .= $attribute;
                    } else {
                        $ret .= ';' . $attribute;
                    }
                }
            }

        return $ret;
    }
}