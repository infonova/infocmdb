<?php

/**
 * TABLE => 'IMPORT_FILE_VALIDATION_ATTRIBUTES'
 *
 *
 *
 */
class Db_ImportFileValidationAttributes extends Zend_Db_Table_Abstract
{

    protected $_name    = 'import_file_validation_attributes';
    protected $_primary = 'id';

    const TABLE_NAME = 'import_file_validation_attributes';

    // define db attributes
    const ID            = 'id';
    const VALIDATION_ID = 'validation_id';
    const UNIQUE_ID     = 'unique_id';
    const CI_ID         = 'ci_id';
    const ATTRIBUTE_ID  = 'attribute_id';
    const VALUE         = 'value';
    const NOTE          = 'note';
    const CREATED       = 'created';
    const USER_ID       = 'user_id';
    const FINALIZED     = 'finalized';
    const STATUS        = 'status';
    const PROJECT_ID    = 'project_id';
    const CI_TYPE_ID    = 'ci_type_id';
}