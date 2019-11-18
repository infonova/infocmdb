<?php

if (getenv('TRAVIS')) {
    /**
     * Zend_Db_Adapter_Pdo_Mysql and Zend_Db_Adapter_Mysqli
     *
     * There are separate properties to enable tests for the PDO_MYSQL adapter and
     * the native Mysqli adapters, but the other properties are shared between the
     * two MySQL-related Zend_Db adapters.
     */
    defined('TESTS_ZEND_DB_ADAPTER_PDO_MYSQL_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_PDO_MYSQL_ENABLED', true);
    defined('TESTS_ZEND_DB_ADAPTER_MYSQLI_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_MYSQLI_ENABLED', true);
    defined('TESTS_ZEND_DB_ADAPTER_MYSQL_USERNAME') || define('TESTS_ZEND_DB_ADAPTER_MYSQL_USERNAME', 'travis');
    defined('TESTS_ZEND_DB_ADAPTER_MYSQL_PASSWORD') || define('TESTS_ZEND_DB_ADAPTER_MYSQL_PASSWORD', '');
    defined('TESTS_ZEND_DB_ADAPTER_MYSQL_DATABASE') || define('TESTS_ZEND_DB_ADAPTER_MYSQL_DATABASE', 'zftest');

    /**
     * Zend_Db_Adapter_Pdo_Sqlite
     *
     * Username and password are irrelevant for SQLite.
     */
    defined('TESTS_ZEND_DB_ADAPTER_PDO_SQLITE_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_PDO_SQLITE_ENABLED', true);

    /**
     * Zend_Db_Adapter_Pdo_Pgsql
     */
    defined('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_ENABLED', true);
    defined('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_USERNAME') || define('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_USERNAME', 'postgres');
    defined('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_PASSWORD') || define('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_PASSWORD', '');
    defined('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_DATABASE') || define('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_DATABASE', 'zftest');
}

/**
 * Zend_Db_Adapter_Pdo_Mysql and Zend_Db_Adapter_Mysqli
 *
 * There are separate properties to enable tests for the PDO_MYSQL adapter and
 * the native Mysqli adapters, but the other properties are shared between the
 * two MySQL-related Zend_Db adapters.
 */
defined('TESTS_ZEND_DB_ADAPTER_PDO_MYSQL_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_PDO_MYSQL_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_MYSQLI_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_MYSQLI_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_MYSQL_HOSTNAME') || define('TESTS_ZEND_DB_ADAPTER_MYSQL_HOSTNAME', '127.0.0.1');
defined('TESTS_ZEND_DB_ADAPTER_MYSQL_USERNAME') || define('TESTS_ZEND_DB_ADAPTER_MYSQL_USERNAME', null);
defined('TESTS_ZEND_DB_ADAPTER_MYSQL_PASSWORD') || define('TESTS_ZEND_DB_ADAPTER_MYSQL_PASSWORD', null);
defined('TESTS_ZEND_DB_ADAPTER_MYSQL_DATABASE') || define('TESTS_ZEND_DB_ADAPTER_MYSQL_DATABASE', 'test');
defined('TESTS_ZEND_DB_ADAPTER_MYSQL_PORT') || define('TESTS_ZEND_DB_ADAPTER_MYSQL_PORT', 3306);

/**
 * Zend_Db_Adapter_Pdo_Sqlite
 *
 * Username and password are irrelevant for SQLite.
 */
defined('TESTS_ZEND_DB_ADAPTER_PDO_SQLITE_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_PDO_SQLITE_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_PDO_SQLITE_DATABASE') || define('TESTS_ZEND_DB_ADAPTER_PDO_SQLITE_DATABASE', ':memory:');

/**
 * Zend_Db_Adapter_Pdo_Mssql
 *
 * Note that you need to patch your ntwdblib.dll, the one that
 * comes with PHP does not work.  See user comments at
 * http://us2.php.net/manual/en/ref.mssql.php
 */
defined('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_HOSTNAME') || define('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_HOSTNAME', '127.0.0.1');
defined('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_USERNAME') || define('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_USERNAME', null);
defined('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_PASSWORD') || define('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_PASSWORD', null);
defined('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_DATABASE') || define('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_DATABASE', 'test');
defined('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_PDOTYPE') || define('TESTS_ZEND_DB_ADAPTER_PDO_MSSQL_PDOTYPE', 'dblib');

/**
 * Zend_Db_Adapter_Pdo_Pgsql
 */
defined('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_HOSTNAME') || define('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_HOSTNAME', '127.0.0.1');
defined('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_USERNAME') || define('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_USERNAME', null);
defined('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_PASSWORD') || define('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_PASSWORD', null);
defined('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_DATABASE') || define('TESTS_ZEND_DB_ADAPTER_PDO_PGSQL_DATABASE', 'postgres');

/**
 * Zend_Db_Adapter_Oracle and Zend_Db_Adapter_Pdo_Oci
 *
 * There are separate properties to enable tests for the PDO_OCI adapter and
 * the native Oracle adapter, but the other properties are shared between the
 * two Oracle-related Zend_Db adapters.
 */
defined('TESTS_ZEND_DB_ADAPTER_PDO_OCI_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_PDO_OCI_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_ORACLE_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_ORACLE_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_ORACLE_HOSTNAME') || define('TESTS_ZEND_DB_ADAPTER_ORACLE_HOSTNAME', '127.0.0.1');
defined('TESTS_ZEND_DB_ADAPTER_ORACLE_USERNAME') || define('TESTS_ZEND_DB_ADAPTER_ORACLE_USERNAME', null);
defined('TESTS_ZEND_DB_ADAPTER_ORACLE_PASSWORD') || define('TESTS_ZEND_DB_ADAPTER_ORACLE_PASSWORD', null);
defined('TESTS_ZEND_DB_ADAPTER_ORACLE_SID') || define('TESTS_ZEND_DB_ADAPTER_ORACLE_SID', 'xe');

/**
 * Zend_Db_Adapter_Db2 and Zend_Db_Adapter_Pdo_Ibm
 * There are separate properties to enable tests for the PDO_IBM adapter and
 * the native DB2 adapter, but the other properties are shared between the
 * two related Zend_Db adapters.
 */
defined('TESTS_ZEND_DB_ADAPTER_PDO_IBM_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_PDO_IBM_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_DB2_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_DB2_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_DB2_HOSTNAME') || define('TESTS_ZEND_DB_ADAPTER_DB2_HOSTNAME', '127.0.0.1');
defined('TESTS_ZEND_DB_ADAPTER_DB2_PORT') || define('TESTS_ZEND_DB_ADAPTER_DB2_PORT', 50000);
defined('TESTS_ZEND_DB_ADAPTER_DB2_USERNAME') || define('TESTS_ZEND_DB_ADAPTER_DB2_USERNAME', null);
defined('TESTS_ZEND_DB_ADAPTER_DB2_PASSWORD') || define('TESTS_ZEND_DB_ADAPTER_DB2_PASSWORD', null);
defined('TESTS_ZEND_DB_ADAPTER_DB2_DATABASE') || define('TESTS_ZEND_DB_ADAPTER_DB2_DATABASE', 'sample');

/**
 * Zend_Db_Adapter_Sqlsrv
 * Note: Make sure that you create the "test" database and set a
 * username and password
 *
 */
defined('TESTS_ZEND_DB_ADAPTER_SQLSRV_ENABLED') || define('TESTS_ZEND_DB_ADAPTER_SQLSRV_ENABLED', false);
defined('TESTS_ZEND_DB_ADAPTER_SQLSRV_HOSTNAME') || define('TESTS_ZEND_DB_ADAPTER_SQLSRV_HOSTNAME', 'localhost\SQLEXPRESS');
defined('TESTS_ZEND_DB_ADAPTER_SQLSRV_USERNAME') || define('TESTS_ZEND_DB_ADAPTER_SQLSRV_USERNAME', null);
defined('TESTS_ZEND_DB_ADAPTER_SQLSRV_PASSWORD') || define('TESTS_ZEND_DB_ADAPTER_SQLSRV_PASSWORD', null);
defined('TESTS_ZEND_DB_ADAPTER_SQLSRV_DATABASE') || define('TESTS_ZEND_DB_ADAPTER_SQLSRV_DATABASE', 'test');

// This should only be enabled in diablomedia/zf1
defined('TESTS_ZEND_DB_ZF1_FULL_SUITE') || define('TESTS_ZEND_DB_ZF1_FULL_SUITE', false);
