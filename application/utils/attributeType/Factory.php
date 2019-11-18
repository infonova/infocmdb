<?php

class Util_AttributeType_Factory
{

    static private $objects = array();


    /**
     * retrieve a AttributeType Class by the given parameter
     *
     * @param String $attributeType
     * @param array  $params
     * @return Util_AttributeType_Type_Abstract
     *
     * @throws Exception_AttributeType_InvalidClassName
     */
    public static function get($attributeType, $params = null)
    {

        if (is_numeric($attributeType)) {
            $attributeDaoImpl = new Dao_Attribute();
            $type             = $attributeDaoImpl->getAttributeTypeId($attributeType);
            $attributeType    = $type[Db_AttributeType::NAME];
            unset($attributeDaoImpl);
            unset($type);
        }

        if (!is_string($attributeType) || !trim($attributeType)) {
            throw new Exception_AttributeType_InvalidClassName();
        }

        if (!isset(Util_AttributeType_Factory::$objects[$attributeType])) {
            $new_one                                             = self::create($attributeType, $params);
            Util_AttributeType_Factory::$objects[$attributeType] = &$new_one; //not sure if that works like the line below
            //Util_AttributeType_Factory::$objects[$attributeType] = & self::create( $attributeType, $params ); 
        }

        return Util_AttributeType_Factory::$objects[$attributeType];
    }

    public static function getByAttributeName($attributeName, $params = null)
    {
        $attributeDaoImpl = new Dao_Attribute();
        $attribute = $attributeDaoImpl->getAttributeByName($attributeName);

        if($attribute === false) {
            throw new Exception_Attribute_RetrieveNotFound();
        }

        return self::get($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID], $params);
    }


    /**
     *
     * @param unknown_type $attributeType
     * @param unknown_type $params
     */
    private static function create($attributeType, $params = null)
    {
        try {
            $className = 'Util_AttributeType_Type_' . ucfirst($attributeType);
            if (!class_exists($className)) {
                throw new Exception('Attributetype "' . $attributeType . '" does not exist!');
            }
            $obj = new $className($params);
        } catch (Exception $e) {
            throw new Exception_AttributeType_CreateClassFailed($e);
        }

        return $obj;
    }
}