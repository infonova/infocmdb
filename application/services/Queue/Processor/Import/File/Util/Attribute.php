<?php

// holt und überprüft alle Attribute der übergebenen liste
class Import_File_Util_Attribute
{

    const CI_ID_KEY   = 'ciid';
    const CI_ID_KEY_2 = 'ci id';
    const CI_ID_KEY_3 = 'ci_id';
    const CI_ID_KEY_4 = 'ci-id';

    const PROJECT_KEY   = 'project';
    const PROJECT_KEY_2 = 'projekt';

    const CI_TYPE_ID   = 'citype';
    const CI_TYPE_ID_2 = 'ci type';
    const CI_TYPE_ID_3 = 'ci-type';
    const CI_TYPE_ID_4 = 'ci_type';


    public static function getAttributes(&$attributes, &$logger)
    {
        $result               = array();
        $result['status']     = true;
        $result['errors']     = array();
        $result['attributes'] = null;

        $importDaoImpl = new Dao_Import(false);
        $newAtt        = array();

        foreach ($attributes as $key => $attribute) {
            $attribute = trim($attribute);

            // if column header does not contain #ERROR -> do stuff     
            $isErrorColumn = stripos($attribute, '#ERROR');
            if ($isErrorColumn === false) {
                if (!empty($attribute)) {
                    //handle attributes
                    if (!self::checkIdentifier($attribute)) {
                        $attribute                    = utf8_encode($attribute);
                        $res                          = $importDaoImpl->getAttributeIdByName($attribute, $allowInactive);
                        $newAtt[$key]['value']        = $res[Db_Attribute::ID];
                        $newAtt[$key]['name']         = $attribute;
                        $newAtt[$key]['is_mandatory'] = $res[Db_Attribute::IS_UNIQUE];
                        $newAtt[$key]['type']         = $res['type'];
                        if (!$res[Db_Attribute::ID]) {
                            $logger->log('line: 1 => given "' . $attribute . '" in header is invalid and cannot be processed', Zend_Log::ERR);
                            $result['status']           = false;
                            $result['errors'][$key + 1] = Import_File_Code::ERROR_HEADER_VALUE_ATTRIBUTE;
                        }
                    } else { //handle other columns
                        $attribute = str_replace(self::CI_TYPE_ID_2, self::CI_TYPE_ID, $attribute);
                        $attribute = str_replace(self::CI_TYPE_ID_3, self::CI_TYPE_ID, $attribute);
                        $attribute = str_replace(self::CI_TYPE_ID_4, self::CI_TYPE_ID, $attribute);

                        $attribute = str_replace(self::PROJECT_KEY_2, self::PROJECT_KEY, $attribute);

                        $attribute    = str_replace(self::CI_ID_KEY_2, self::CI_ID_KEY, $attribute);
                        $attribute    = str_replace(self::CI_ID_KEY_3, self::CI_ID_KEY, $attribute);
                        $attribute    = str_replace(self::CI_ID_KEY_4, self::CI_ID_KEY, $attribute);
                        $newAtt[$key] = $attribute;
                    }
                }
            } else {
                // column header contains #ERROR -> ignore column
                $logger->log('ignoring error column', Zend_Log::INFO);
            }
        }

        //return without attributes if there is an error
        if (count($result['errors']) > 0) {
            $result['attributes'] = array();
            return $result;
        }

        $result['attributes'] = $newAtt;

        $importDaoImpl = null;
        $attributes    = null;

        return $result;
    }

    private static function checkIdentifier($attribute)
    {
        if ($attribute == self::CI_TYPE_ID
            || $attribute == self::CI_TYPE_ID_2
            || $attribute == self::CI_TYPE_ID_3
            || $attribute == self::CI_TYPE_ID_4
        ) {
            return true;
        } else if ($attribute == self::CI_ID_KEY
            || $attribute == self::CI_ID_KEY_2
            || $attribute == self::CI_ID_KEY_3
            || $attribute == self::CI_ID_KEY_4
        ) {
            return true;
        } else if ($attribute == self::PROJECT_KEY
            || $attribute == self::PROJECT_KEY_2) {
            return true;
        } else {
            return false;
        }
        return false;
    }

}