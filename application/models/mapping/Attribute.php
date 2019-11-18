<?php

/**
 * TABLE => 'ATTRIBUTE'
 *
 *
 *
 */
class Db_Attribute extends Zend_Db_Table_Abstract
{

    protected $_name    = 'attribute';
    protected $_primary = 'id';

    const TABLE_NAME = 'attribute';

    // define db attributes
    const ID                    = 'id';
    const NAME                  = 'name';
    const DESCRIPTION           = 'description';
    const NOTE                  = 'note';
    const HINT                  = 'hint';
    const ATTRIBUTE_TYPE_ID     = 'attribute_type_id';
    const ATTRIBUTE_GROUP_ID    = 'attribute_group_id';
    const ORDER_NUMBER          = 'order_number';
    const COLUMN                = 'column';
    const IS_UNIQUE             = 'is_unique';
    const IS_NUMERIC            = 'is_numeric';
    const IS_BOLD               = 'is_bold';
    const IS_EVENT              = 'is_event';
    const IS_UNIQUE_CHECK       = 'is_unique_check';
    const IS_AUTOCOMPLETE       = 'is_autocomplete';
    const IS_MULTISELECT        = 'is_multiselect';
    const IS_PROJECT_RESTRICTED = 'is_project_restricted';
    const REGEX                 = 'regex';
    const WORKFLOW_ID           = 'workflow_id';
    const SCRIPT_NAME           = 'script_name';
    const TAG                   = 'tag';
    const INPUT_MAXLENGTH       = 'input_maxlength';
    const TEXTAREA_COLS         = 'textarea_cols';
    const TEXTAREA_ROWS         = 'textarea_rows';
    const IS_ACTIVE             = 'is_active';
    const USER_ID               = 'user_id';
    const VALID_FROM            = 'valid_from';
    const HISTORIZE             = 'historize';
    const DISPLAY_STYLE         = 'display_style';
}