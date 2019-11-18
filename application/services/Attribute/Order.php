<?php

/**
 *
 *
 */
class Service_Attribute_Order extends Service_Abstract
{


    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 111, $themeId);
    }


    public function getCiTypeAttributeOrderForm($attributeId)
    {
        $attributeDao           = new Dao_Attribute();
        $attributeDefaultValues = $attributeDao->getAttributeCiTypeValues($attributeId);

        if (!$attributeDefaultValues || count($attributeDefaultValues) < 1) {
            // TODO: throw exception
        }

        $attributeIds = array();
        foreach ($attributeDefaultValues as $key => $att) {
            $attribute          = $attributeDao->getAttribute($att[Db_AttributeDefaultCitypeAttributes::ATTRIBUTE_ID]);
            $attributeIds[$key] = $attribute;
        }

        return $attributeIds;


    }


    public function orderCiTypeAttribute($attributeId, $orderString)
    {

        if (!$orderString || $orderString == 'test')
            return false;

        $attributeDao           = new Dao_Attribute();
        $attributeDefaultValues = $attributeDao->getAttributeCiTypeValues($attributeId);

        $order    = explode(';', $orderString);
        $newOrder = array();

        foreach ($order as $key => $o) {
            if ($o) {
                array_push($newOrder, $o);
            }
        }


        $finalSortedAdv = array();
        foreach ($attributeDefaultValues as $key => $val) {
            $newKey                  = $newOrder[$key] - 1;
            $finalSortedAdv[$newKey] = $val;
        }

        ksort($finalSortedAdv);

        $attributeDao->updateCiTypeAttributesOrderNumber($finalSortedAdv);

        return true;
    }
}