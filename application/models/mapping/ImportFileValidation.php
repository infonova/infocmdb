<?php

/**
 * TABLE => 'IMPORT_FILE_VALIDATION'
 *
 *
 *
 */
class Db_ImportFileValidation extends Zend_Db_Table_Abstract
{

    protected $_name    = 'import_file_validation';
    protected $_primary = 'id';

    const TABLE_NAME = 'import_file_validation';

    // define db attributes
    const ID         = 'id';
    const NAME       = 'name';
    const TYPE       = 'type';
    const CREATED    = 'created';
    const FINALIZED  = 'finalized';
    const STATUS     = 'status';
    const CI_TYPE_ID = 'ci_type_id';
    const PROJECT_ID = 'project_id';
}