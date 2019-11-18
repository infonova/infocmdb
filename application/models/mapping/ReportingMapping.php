<?php

/**
 * TABLE => 'CI'
 *
 *
 *
 */
class Db_ReportingMapping extends Zend_Db_Table_Abstract
{

    protected $_name    = 'reporting_mapping';
    protected $_primary = 'id';

    const TABLE_NAME = 'reporting_mapping';

    // define db attributes
    const ID           = 'id';
    const REPORTING_ID = 'reporting_id';
    const MAPPING_ID   = 'mapping_id';
    const TYPE         = 'type';
}