<?php


class Import_File_Code
{
    // identifier
    const CI_ID_KEY   = 'ciid';
    const PROJECT_KEY = 'project';
    const CI_TYPE_ID  = 'citype';


    // error codes
    const ERROR_INSERT_MISSING_CITYPE  = '1';
    const ERROR_INSERT_MISSING_PROJECT = '2';

    const ERROR_INSERT_DUMMY_MARK_RED = 'x';

    const ERROR_UPDATE_MISSING_ATTRIBUTE_ID = '50';
    const ERROR_UPDATE_ATTRIBUTE_NOT_UNIQUE = '51';

    const ERROR_UPDATE_MISSING_MANDATORY_ATTRIBUTE = '52';
    const ERROR_UPDATE_INVALID_ATTRIBUTE_VALUE     = '53';

    const ERROR_UPDATE_FAILED = '54';
    const ERROR_INSERT_FAILED = '55';

    const ERROR_INSERT_INVALID_ATTRIBUTE = '56';
    const ERROR_INSERT_INVALID_CI_TYPE   = '57';
    const ERROR_INSERT_INVALID_PROJECT   = '58';

    const ERROR_INSERT_ATTRIBUTE_ISNULL = '59';

    const ERROR_UNEXPECTED              = '66';
    const ERROR_ATTRIBUTE_SINGLE_FAILED = '676';
    const ERROR_ATTRIBUTE_ALL_FAILED    = '677';

    const ERROR_ATTRIBUTE_INSERT_FAILED = '80';

    const ERROR_IMPORT_PROJECT_OR_CITYPE_NOT_IN_DB = '90';
    const ERROR_IMPORT_CIID_NOT_IN_DB              = '95';

    const ERROR_HEADER_VALUE_ATTRIBUTE = '666';
    const ERROR_DEFAULT_VALUE          = '667';


    const ERROR_RELATION_INVALID_RELATIONTYPE = '100';
    const ERROR_RELATION_INVALID_CI_ID_1      = '101';
    const ERROR_RELATION_INVALID_CI_ID_2      = '102';

    const ERROR_RELATION_RELATION_CREATE_FAILED = '105';
    const ERROR_RELATION_RELATION_DELETE_FAILED = '106';

    // SUCCESS COE
    const SUCCESS_FIELD_MARK_GREEN = '999';
    const SUCCESS_FIELD_NO_UPDATE  = '998';
    const SUCCESS_FIELD_UPDATED    = '997';
    const SUCCESS_FIELD_INSERTED   = '996';
}