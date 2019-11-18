<?php

/**
 * TABLE => 'CI'
 *
 *
 *
 */
class Db_CiHighlight extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_highlight';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci_highlight';

    // define db attributes
    const ID      = 'id';
    const USER_ID = 'user_id';
    const CI_ID   = 'ci_id';
    const COLOR   = 'color';

    public static function getHexColor($colorEnum)
    {
        switch ($colorEnum) {
            case 'r':
                return '#EBCECE';
                break;
            case 'b':
                return '#C4E0FF';
                break;
            case 'g':
                return '#D4FF91';
                break;
            case 'v':
                return '#D6C2FC';
                break;
            case 'o':
                return '#FFA500';
                break;
            default:
                return $colorEnum;
        }
    }
}