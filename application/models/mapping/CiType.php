<?php

/**
 * TABLE => 'CI_TYPE'
 *
 *
 *
 */
class Db_CiType extends Zend_Db_Table_Abstract
{

    protected $_name    = 'ci_type';
    protected $_primary = 'id';

    const TABLE_NAME = 'ci_type';

    // define db attributes
    const ID                        = 'id';
    const NAME                      = 'name';
    const DESCRIPTION               = 'description';
    const NOTE                      = 'note';
    const PARENT_CI_TYPE_ID         = 'parent_ci_type_id';
    const ORDER_NUMBER              = 'order_number';
    const CREATE_BUTTON_DESCRIPTION = 'create_button_description';
    const ICON                      = 'icon';
    const QUERY                     = 'query';
    const DEFAULT_PROJECT_ID        = 'default_project_id';
    const DEFAULT_ATTRIBUTE_ID      = 'default_attribute_id';
    const DEFAULT_SORT_ATTRIBUTE_ID = 'default_sort_attribute_id';
    const IS_DEFAULT_SORT_ASC       = 'is_default_sort_asc';
    const IS_CI_ATTACH              = 'is_ci_attach';
    const IS_ATTRIBUTE_ATTACH       = 'is_attribute_attach';
    const TAG                       = 'tag';
    const IS_TAB_ENABLED            = 'is_tab_enabled';
    const IS_EVENT_ENABLED          = 'is_event_enabled';
    const IS_ACTIVE                 = 'is_active';

    const USER_ID    = 'user_id';
    const VALID_FROM = 'valid_from';
}