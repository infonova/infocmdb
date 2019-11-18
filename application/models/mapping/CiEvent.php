<?php

/**
 * TABLE => 'CI_EVENT'
 *
 *
 *
 */
class Db_CiEvent extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_event';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci_event';

    // define db attributes
    const ID         = 'id';
    const CI_ID      = 'ci_id';
    const EVENT_NAME = 'event_name';
    const EVENT_LINK = 'event_link';
    const VALID_FROM = 'valid_from';
    const VALID_TO   = 'valid_to';
}