<?php

/**
 * TABLE => 'CI_TICKET'
 *
 *
 *
 */
class Db_CiTicket extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_ticket';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci_ticket';

    // define db attributes
    const ID          = 'id';
    const CI_ID       = 'ci_id';
    const TICKET_ID   = 'ticket_id';
    const TICKET_NAME = 'ticket_name';
    const VALID_FROM  = 'valid_from';
    const VALID_TO    = 'valid_to';
}