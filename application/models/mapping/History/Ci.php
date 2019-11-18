<?php

/**
 * TABLE => 'CI'
 *
 */
class Db_History_Ci extends Zend_Db_Table_Abstract
{

    protected $_name    = 'h_ci';
    protected $_primary = 'id';

    const TABLE_NAME = 'h_ci';

    // define db attributes
    const ID         = 'id';
    const CI_TYPE_ID = 'ci_type_id';
    const ICON       = 'icon';

    const HISTORY_ID        = 'history_id';
    const HISTORY_ID_DELETE = 'history_id_delete';

    const VALID_FROM = 'valid_from';
    const VALID_TO   = 'valid_to';
}