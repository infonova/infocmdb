<?php

use PhinxExtend\AbstractPhinxMigration;

class CreateTables extends AbstractPhinxMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $createCheck = $this->fetchRow("SHOW DATABASES LIKE '" . $this->dbName . "_tables'");

        //if we have fresh database --> add tables and constraints otherwise do nothing in this migration
        if ($this->getCurrentMigrateVersion() == 0 &&  $createCheck === false) {

            /*
             * CREATE DATABASES
             */
            //$this->execute("CREATE DATABASE IF NOT EXISTS " . $this->dbName);      // MUST EXIST!!
            $this->execute("CREATE DATABASE IF NOT EXISTS " . $this->dbName . "_tables DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
            $this->execute("CREATE DATABASE IF NOT EXISTS " . $this->dbName . "_history DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");


            /*
             * HISTORY
             */

            echo "HISTORY - attribute\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`attribute` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(100) NOT NULL COMMENT 'attribute name',
                      `description` varchar(100) NOT NULL DEFAULT '' COMMENT 'description for frontend view',
                      `note` varchar(300) DEFAULT NULL COMMENT 'short description note',
                      `hint` varchar(200) DEFAULT NULL,
                      `attribute_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'kind of attribute',
                      `attribute_group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'visibility category',
                      `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'free number for order by',
                      `column` enum('1','2') NOT NULL DEFAULT '1',
                      `is_unique` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'attribute must be unique',
                      `is_numeric` enum('0','1') NOT NULL DEFAULT '0',
                      `is_bold` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'print attribute in bold',
                      `is_event` enum('0','1') NOT NULL DEFAULT '0',
                      `is_unique_check` enum('0','1') NOT NULL DEFAULT '0',
                      `is_autocomplete` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'for citype attribute (1... use autocomplete function, 0... standard operation',
                      `is_multiselect` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0:dropdown,1:multiselect',
                      `regex` varchar(400) DEFAULT NULL,
                      `script_name` varchar(200) DEFAULT NULL COMMENT 'name of the script linked to this attribute',
                      `tag` varchar(100) DEFAULT NULL,
                      `input_maxlength` tinyint(5) unsigned DEFAULT NULL COMMENT 'maxlength for input fields',
                      `textarea_cols` tinyint(5) unsigned DEFAULT NULL COMMENT 'cols for textarea',
                      `textarea_rows` tinyint(5) unsigned DEFAULT NULL COMMENT 'rows for textarea',
                      `is_active` enum('0','1') NOT NULL DEFAULT '0',
                      `user_id` int(10) unsigned NOT NULL COMMENT 'created this entry',
                      `valid_from` datetime NOT NULL COMMENT 'last create/update datetime',
                      `user_id_delete` int(10) unsigned NOT NULL COMMENT 'created this entry',
                      `valid_to` datetime NOT NULL COMMENT 'last create/update datetime',
                      `historicize` ENUM( '1', '0' ) NOT NULL DEFAULT '1'
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='attribute definition' AUTO_INCREMENT=1
                ");

            echo "HISTORY - attribute_default_citype\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`attribute_default_citype` (
                      `id` int(10) unsigned NOT NULL,
                      `attribute_id` int(10) unsigned NOT NULL,
                      `ci_type_id` int(10) unsigned NOT NULL,
                      `join_attribute_id_from` int(10) unsigned DEFAULT NULL COMMENT 'define attribute to join with parent',
                      `join_attribute_id_to` int(10) unsigned DEFAULT NULL COMMENT 'define attribute to join with child',
                      `join_order` int(10) unsigned DEFAULT NULL,
                      `valid_from` datetime NOT NULL,
                      `valid_to` datetime DEFAULT NULL
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
                ");

            echo "HISTORY - attribute_default_citype_attributes\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`attribute_default_citype_attributes` (
                      `id` int(10) unsigned NOT NULL,
                      `attribute_default_citype_id` int(10) unsigned NOT NULL,
                      `attribute_id` int(10) unsigned NOT NULL COMMENT 'attribute to select',
                      `condition` varchar(50) DEFAULT NULL COMMENT 'eg: \"Active\" to select all Active values',
                      `order_number` tinyint(5) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `valid_to` datetime DEFAULT NULL
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
                ");

            echo "HISTORY - attribute_default_queries\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`attribute_default_queries` (
                      `id` int(10) unsigned NOT NULL,
                      `attribute_id` int(10) unsigned NOT NULL,
                      `query` text NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `valid_to` datetime DEFAULT NULL
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
                ");

            echo "HISTORY - attribute_default_queries_parameter\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`attribute_default_queries_parameter` (
                      `id` int(10) unsigned NOT NULL,
                      `queries_id` int(10) unsigned NOT NULL,
                      `parameter` varchar(150) NOT NULL,
                      `order_number` tinyint(5) unsigned DEFAULT NULL,
                      `valid_from` datetime NOT NULL,
                      `valid_to` datetime DEFAULT NULL
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
                ");

            echo "HISTORY - attribute_default_values\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`attribute_default_values` (
                      `id` int(10) unsigned NOT NULL,
                      `attribute_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `value` longtext, 
                      `order_number` tinyint(5) unsigned DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `valid_from` datetime NOT NULL,
                      `valid_to` datetime DEFAULT NULL
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='default values for selection fields'
                ");

            echo "HISTORY - attribute_group\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`attribute_group` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(100) NOT NULL DEFAULT '',
                      `description` varchar(100) NOT NULL DEFAULT '',
                      `note` varchar(300) DEFAULT NULL,
                      `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0',
                      `parent_attribute_group_id` int(10) unsigned DEFAULT NULL,
                      `is_duplicate_allow` enum('0','1') NOT NULL DEFAULT '0',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='frontend visibility group' AUTO_INCREMENT=1
                ");

            echo "HISTORY - ci\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`ci` (
                      `id` int(10) unsigned NOT NULL,
                      `ci_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ci is member of which ci class',
                      `icon` varchar(45) DEFAULT NULL,
                      `history_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `valid_from` datetime NOT NULL,
                      `history_id_delete` int(10) unsigned NOT NULL DEFAULT '0',INDEX(history_id,history_id_delete),
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='configuration items' AUTO_INCREMENT=1 ;
                ");

            echo "HISTORY - ci_attribute\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`ci_attribute` (
                      `id` int(10) unsigned NOT NULL,
                      `ci_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'configuration item',INDEX(ci_id),
                      `attribute_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'attribute ',
                      `value_text` longtext COMMENT 'datatype for saving text',
                      `value_date` datetime DEFAULT NULL COMMENT 'datatype for staving dates',
                      `value_default` int(10) unsigned DEFAULT NULL COMMENT 'attribute_default_values mapping',
                      `value_ci` longtext DEFAULT NULL COMMENT 'ci mapping',
                      `note` varchar(300) DEFAULT NULL COMMENT 'notes',
                      `is_initial` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'initial or added',
                      `history_id` int(10) NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `history_id_delete` int(10) NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='connects attributes to ci' AUTO_INCREMENT=1
                ");

            echo "HISTORY - ci_event\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`ci_event` (
                      `id` int(10) unsigned NOT NULL,
                      `ci_id` int(10) NOT NULL,
                      `event_name` varchar(100) NOT NULL,
                      `event_link` varchar(300) NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='connects cmdb and monitoring system (eg: nagios)' AUTO_INCREMENT=1
                ");

            echo "HISTORY - ci_project\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`ci_project` (
                      `id` int(10) unsigned NOT NULL,
                      `ci_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'configuration item',
                      `project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'project or system group',
                      `history_id` int(10) NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `history_id_delete` int(10) NOT NULL,INDEX(history_id_delete),
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='connect a ci to one or more projects' AUTO_INCREMENT=1
                ");

            echo "HISTORY - ci_relation\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`ci_relation` (
                      `id` int(10) unsigned NOT NULL,
                      `ci_relation_type_id` int(10) unsigned NOT NULL COMMENT 'ci relation type',
                      `ci_id_1` int(10) unsigned NOT NULL COMMENT 'ci who is connected with',
                      `ci_id_2` int(10) unsigned NOT NULL COMMENT 'related to this ci',
                      `attribute_id_1` int(10) unsigned DEFAULT NULL COMMENT 'linked to this attribute_id',
                      `attribute_id_2` int(10) unsigned DEFAULT NULL COMMENT 'source attribute linked to attribute',
                      `direction` int(10) unsigned NOT NULL COMMENT 'FK',
                      `weighting` enum('0','1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '0',
                      `color` varchar(10) DEFAULT NULL,
                      `note` varchar(300) DEFAULT NULL,
                      `history_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `history_id_delete` int(10) unsigned NOT NULL,INDEX(history_id_delete),
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='defines relationships between ci' AUTO_INCREMENT=1
                ");

            echo "HISTORY - ci_relation_type\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`ci_relation_type` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(100) NOT NULL DEFAULT '' COMMENT 'relation name',
                      `description` varchar(100) NOT NULL DEFAULT '' COMMENT 'relation description',
                      `description_optional` varchar(100) DEFAULT NULL,
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `color` varchar(10) DEFAULT NULL,
                      `visualize` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'if relation displayed in visualization',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'active or not',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='types of ci relations' AUTO_INCREMENT=1
                ");

            echo "HISTORY - ci_ticket\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`ci_ticket` (
                      `id` int(10) unsigned NOT NULL,
                      `ci_id` int(10) unsigned NOT NULL,
                      `ticket_id` varchar(20) NOT NULL,
                      `ticket_name` varchar(100) NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='connects cmdb and ticket system (eg: otrs)' AUTO_INCREMENT=1
                ");

            echo "HISTORY - ci_type\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`ci_type` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(100) NOT NULL COMMENT 'name of ci type',
                      `description` varchar(100) NOT NULL COMMENT 'description',
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `parent_ci_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'parent class',
                      `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0',
                      `create_button_description` varchar(45) DEFAULT NULL,
                      `icon` varchar(45) DEFAULT NULL,
                      `query` text DEFAULT NULL,
                      `default_project_id` int(10) unsigned DEFAULT NULL,
                      `default_attribute_id` int(10) unsigned DEFAULT NULL,
                      `default_sort_attribute_id` int(10) unsigned DEFAULT NULL COMMENT 'ci list',
                      `is_default_sort_asc` ENUM('0','1') NOT NULL DEFAULT 1,
                      `is_ci_attach` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'allow direct ci attachments',
                      `is_attribute_attach` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'allow additional attributes to be added',
                      `tag` varchar(100) DEFAULT NULL COMMENT 'tag',
                      `is_tab_enabled` enum('0','1') NOT NULL DEFAULT '0',
                      `is_event_enabled` enum('0','1') NOT NULL DEFAULT '0',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='types and classes of ci' AUTO_INCREMENT=1
                ");

            echo "HISTORY - history\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`history` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ci was changed by',
                      `datestamp` datetime NOT NULL COMMENT 'ci was changed on this time',INDEX(datestamp,user_id),
                      `note` varchar(100) DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `FK_ci_history_1` (`user_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='history table for attribute changes' AUTO_INCREMENT=1
                ");

            echo "HISTORY - import_mail\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`import_mail` (
                      `id` int(10) unsigned NOT NULL,
                      `host` varchar(100) NOT NULL,
                      `user` varchar(45) NOT NULL,
                      `password` varchar(45) NOT NULL,
                      `ssl` varchar(10) NOT NULL DEFAULT 'SSL',
                      `is_extended` ENUM( '0', '1' ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL DEFAULT '0' COMMENT '0.. standard import -> add body as attachment; 1.. extended import -> create new ci, parse body and add values to ci attributes',
                      `ci_type_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'fill if extended',
                      `ci_field` varchar(15) NOT NULL DEFAULT '[CIID#:',
                      `is_attach_body` enum('0','1') NOT NULL DEFAULT '1',
                      `body_attribute_id` int(10) unsigned DEFAULT NULL,
                      `attachment_attribute_id` int(10) unsigned NOT NULL,
                      `is_ci_mail_enabled` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'ci can be identified via sender mail',
                      `note` varchar(300) DEFAULT NULL,
                      `execution_time` varchar(50) DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "HISTORY - mail\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`mail` (
                      `id` int(10) NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `description` varchar(100) NOT NULL,
                      `note` varchar(150) DEFAULT NULL,
                      `subject` varchar(150) NOT NULL,
                      `body` text,
                      `template` int(10) unsigned DEFAULT NULL,
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "HISTORY - project\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`project` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(100) NOT NULL DEFAULT '' COMMENT 'project name',
                      `description` varchar(100) NOT NULL COMMENT 'project or contract description',
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `order_number` tinyint(5) unsigned NOT NULL COMMENT 'order number for sorting',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'project is valid or not',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='system groups or client contracts' AUTO_INCREMENT=1
                ");

            echo "HISTORY - reporting\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`reporting` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `description` text NOT NULL,
                      `input` enum('sql','cql','script','scriptold','gui','extended') NOT NULL,
                      `output` enum('xls','csv','mailbody','none') NOT NULL,
                      `transport` enum('mail','ftp','none') NOT NULL,
                      `trigger` enum('time','manual','none') NOT NULL,
                      `note` varchar(300) DEFAULT NULL,
                      `statement` text COMMENT 'sql, cql',
                      `script` varchar(100) DEFAULT NULL,
                      `execution_time` varchar(50) DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "HISTORY - role\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`role` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(100) NOT NULL DEFAULT '' COMMENT 'role name',
                      `description` varchar(100) NOT NULL DEFAULT '' COMMENT 'role description',
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'role is valid or not',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='role definition' AUTO_INCREMENT=1
                ");

            echo "HISTORY - stored_query\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`stored_query` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `note` text,
                      `query` text NOT NULL,
                      `status` enum('0','1') NULL,
                      `status_message` text,
                      `is_default` enum('0','1') NOT NULL DEFAULT '0',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "HISTORY - templates\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`templates` (
                      `id` int(10) NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `description` varchar(100) NOT NULL,
                      `note` varchar(300) DEFAULT NULL,
                      `file` varchar(100) NOT NULL,
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "HISTORY - theme\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`theme` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(100) NOT NULL DEFAULT '',
                      `description` varchar(100) NOT NULL DEFAULT '',
                      `note` varchar(300) NOT NULL,
                      `menu_id` int(10) unsigned DEFAULT NULL COMMENT 'defines the startpage',
                      `is_wildcard_enabled` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'enables wildcard search',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned NOT NULL,
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='themes definitions' AUTO_INCREMENT=1
                ");

            echo "HISTORY - user\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`user` (
                      `id` int(10) unsigned NOT NULL,
                      `username` varchar(45) NOT NULL DEFAULT '' COMMENT 'username for login',
                      `password` varchar(45) NOT NULL DEFAULT '' COMMENT 'password for login',
                      `email` varchar(100) DEFAULT NULL COMMENT 'FK mail_addresses',
                      `firstname` varchar(45) NOT NULL DEFAULT '',
                      `lastname` varchar(45) NOT NULL DEFAULT '',
                      `description` varchar(100) DEFAULT NULL COMMENT 'user description',
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `theme_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `language` varchar(5) NOT NULL DEFAULT 'de',
                      `layout` varchar(15) NOT NULL DEFAULT 'default',
                      `is_root` enum('0','1') NOT NULL DEFAULT '0',
                      `is_ci_delete_enabled` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'grant for deleting a ci',
                      `is_relation_edit_enabled` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'grant for create and delete relations',
                      `is_ldap_auth` enum('0','1') DEFAULT '0',
                      `last_access` datetime DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'user is valid or not',
                      `user_id` int(10) unsigned DEFAULT NULL COMMENT 'nullable',
                      `valid_from` datetime NOT NULL,
                      `user_id_delete` int(10) unsigned DEFAULT NULL COMMENT 'nullable',
                      `valid_to` datetime NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='user definition' AUTO_INCREMENT=1
                ");

            echo "HISTORY - workflow\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`workflow` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(45) NOT NULL,
                      `description` varchar(100) NOT NULL,
                      `note` text,
                      `execute_user_id` int(10) unsigned NOT NULL,
                      `is_async` enum('0','1') NOT NULL DEFAULT '1',
                      `trigger_ci` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_ci_type_change` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_attribute` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_project` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_relation` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_time` enum('0','1') NOT NULL DEFAULT '0',
                      `execution_time` varchar(20) DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned DEFAULT NULL,
                      `valid_from` datetime DEFAULT NULL,
                      `user_id_delete` int(10) unsigned DEFAULT NULL,
                      `valid_to` datetime DEFAULT NULL
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");


            /*
             * TABLES
             */


            echo "TABLES - temp_history\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `temp_history` (
                      `history_id` INTEGER UNSIGNED NOT NULL,
                      `ci_id` INTEGER UNSIGNED NOT NULL,
                      `datestamp` DATETIME NOT NULL,
                      PRIMARY KEY (`history_id`)
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8
                ");

            echo "TABLES - attribute\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `attribute` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL COMMENT 'attribute name',
                      `description` varchar(100) NOT NULL DEFAULT '' COMMENT 'description for frontend view',
                      `note` varchar(300) DEFAULT NULL COMMENT 'short description note',
                      `hint` varchar(200) DEFAULT NULL,
                      `attribute_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'kind of attribute',
                      `attribute_group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'visibility category',
                      `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'free number for order by',
                      `column` enum('1','2') NOT NULL DEFAULT '1',
                      `is_unique` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'attribute must be unique',
                      `is_numeric` enum('0','1') NOT NULL DEFAULT '0',
                      `is_bold` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'print attribute in bold',
                      `is_event` enum('0','1') NOT NULL DEFAULT '0',
                      `is_unique_check` enum('0','1') NOT NULL DEFAULT '0',
                      `is_autocomplete` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'for citype attribute (1... use autocomplete function, 0... standard operation',
                      `is_multiselect` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0:dropdown,1:multiselect',
                      `regex` varchar(400) DEFAULT NULL,
                      `script_name` varchar(200) DEFAULT NULL COMMENT 'name of the script linked to this attribute',
                      `tag` varchar(100) DEFAULT NULL,
                      `input_maxlength` int(10) unsigned DEFAULT NULL COMMENT 'maxlength for input fields',
                      `textarea_cols` int(10) unsigned DEFAULT NULL COMMENT 'cols for textarea',
                      `textarea_rows` int(10) unsigned DEFAULT NULL COMMENT 'rows for textarea',
                      `is_active` enum('0','1') NOT NULL DEFAULT '0',
                      `user_id` int(10) unsigned NOT NULL COMMENT 'created this entry',
                      `valid_from` datetime NOT NULL COMMENT 'last create/update datetime',
                      `historicize` ENUM( '1', '0' ) NOT NULL DEFAULT '1',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='attribute definition' AUTO_INCREMENT=1
                ");

            echo "TABLES - attribute_default_citype\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `attribute_default_citype` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `attribute_id` int(10) unsigned NOT NULL,
                      `ci_type_id` int(10) unsigned NOT NULL,
                      `join_attribute_id_from` int(10) unsigned DEFAULT NULL COMMENT 'define attribute to join with parent',
                      `join_attribute_id_to` int(10) unsigned DEFAULT NULL COMMENT 'define attribute to join with child',
                      `join_order` int(10) unsigned DEFAULT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
                ");

            echo "TABLES - attribute_default_citype_attributes\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `attribute_default_citype_attributes` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `attribute_default_citype_id` int(10) unsigned NOT NULL,
                      `attribute_id` int(10) unsigned NOT NULL COMMENT 'attribute to select',
                      `condition` varchar(50) DEFAULT NULL COMMENT 'eg: \"Active\" to select all Active values',
                      `order_number` tinyint(5) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
                ");

            echo "TABLES - attribute_default_queries\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `attribute_default_queries` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `attribute_id` int(10) unsigned NOT NULL,
                      `query` text NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
                ");

            echo "TABLES - attribute_default_queries_parameter\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `attribute_default_queries_parameter` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `queries_id` int(10) unsigned NOT NULL,
                      `parameter` varchar(150) NOT NULL,
                      `order_number` tinyint(5) unsigned DEFAULT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
                ");

            echo "TABLES - attribute_default_values\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `attribute_default_values` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `attribute_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `value` longtext,
                      `order_number` tinyint(5) unsigned DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='default values for selection fields'
                ");

            echo "TABLES - attribute_group\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `attribute_group` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL DEFAULT '',
                      `description` varchar(100) NOT NULL DEFAULT '',
                      `note` varchar(300) DEFAULT NULL,
                      `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0',
                      `parent_attribute_group_id` int(10) unsigned DEFAULT NULL,
                      `is_duplicate_allow` enum('0','1') NOT NULL DEFAULT '0',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='frontend visibility group' AUTO_INCREMENT=1
                ");

            echo "TABLES - attribute_role\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `attribute_role` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `attribute_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `role_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `permission_read` enum('0','1') NOT NULL DEFAULT '0',
                      `permission_write` enum('0','1') NOT NULL DEFAULT '0',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Rollenzugriff auf Attribute'
                ");

            echo "TABLES - attribute_type\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `attribute_type` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(45) NOT NULL DEFAULT '',
                      `description` varchar(45) NOT NULL DEFAULT '',
                      `note` varchar(250) DEFAULT NULL,
                      `customization_level` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0:no fields; 1:feldbreite; 2:feldbreite+höhe; 3:fileupload; 4:ciType hierarchy; 5 info Attribute; 6 date; 7 dropdown; 8 popup-select',
                      `is_regex_enabled` enum('0','1') NOT NULL DEFAULT '0',
                      `order_number` INT NOT NULL DEFAULT '0',
                      `is_active` ENUM( '0', '1' ) NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='kind of attribute (text, checkbox, etc.)'
                ");

            echo "TABLES - ci\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ci is member of which ci class',INDEX(ci_type_id),
                      `icon` varchar(45) DEFAULT NULL,
                      `history_id` int(10) unsigned NOT NULL DEFAULT '0',INDEX(history_id),
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='configuration items' AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_attribute\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_attribute` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'configuration item',INDEX(ci_id),
                      `attribute_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'attribute ',
                      `value_text` longtext COMMENT 'datatype for saving text',INDEX(value_text(10)),
                      `value_date` datetime DEFAULT NULL COMMENT 'datatype for saving dates',
                      `value_default` int(10) unsigned DEFAULT NULL COMMENT 'attribute_default_values mapping',
                      `value_ci` longtext DEFAULT NULL COMMENT 'ci mapping',
                      `note` varchar(300) DEFAULT NULL COMMENT 'notes',
                      `is_initial` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'initial or added',
                      `history_id` int(10) NOT NULL,INDEX(history_id),
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='connects attributes to ci' AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_event\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_event` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_id` int(10) unsigned NOT NULL,
                      `event_name` varchar(100) NOT NULL,
                      `event_link` varchar(300) NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='connects cmdb and monitoring system (eg: nagios)' AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_favourites\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_favourites` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` int(10) unsigned NOT NULL,
                      `ci_id` int(10) unsigned NOT NULL,
                      `group` varchar(100) NOT NULL DEFAULT 'default',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_highlight\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_highlight` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` int(10) unsigned NOT NULL,
                      `ci_id` int(10) unsigned NOT NULL,
                      `color` varchar(10) NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_permission\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_permission` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_id` int(10) unsigned NOT NULL,
                      `user_id` int(10) unsigned NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_project\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_project` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'configuration item',
                      `project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'project or system group',
                      `history_id` int(10) NOT NULL,INDEX(history_id),
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='connect a ci to one or more projects' AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_relation\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_relation` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_relation_type_id` int(10) unsigned NOT NULL COMMENT 'ci relation type',
                      `ci_id_1` int(10) unsigned NOT NULL COMMENT 'ci who is connected with',
                      `ci_id_2` int(10) unsigned NOT NULL COMMENT 'related to this ci',INDEX(ci_id_1,ci_id_2),
                      `attribute_id_1` int(10) unsigned DEFAULT NULL COMMENT 'linked to this attribute_id',
                      `attribute_id_2` int(10) unsigned DEFAULT NULL COMMENT 'source attribute linked to attribute',
                      `direction` int(10) unsigned NOT NULL COMMENT 'FK',
                      `weighting` enum('0','1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '0',
                      `color` varchar(10) DEFAULT NULL,
                      `note` varchar(300) DEFAULT NULL,
                      `history_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='defines relationships between ci' AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_relation_direction\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_relation_direction` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(20) NOT NULL,
                      `description` varchar(45) NOT NULL,
                      `note` varchar(100) DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_relation_type\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_relation_type` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL DEFAULT '' COMMENT 'relation name',
                      `description` varchar(100) NOT NULL DEFAULT '' COMMENT 'relation description',
                      `description_optional` varchar(100) DEFAULT NULL,
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `color` varchar(10) DEFAULT NULL,
                      `visualize` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'if relation displayed in visualization',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'active or not',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='types of ci relations' AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_ticket\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_ticket` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_id` int(10) unsigned NOT NULL,
                      `ticket_id` varchar(20) NOT NULL,
                      `ticket_name` varchar(100) NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='connects cmdb and ticket system (eg: otrs)' AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_type\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_type` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL COMMENT 'name of ci type',
                      `description` varchar(100) NOT NULL COMMENT 'description',
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `parent_ci_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'parent class',
                      `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0',
                      `create_button_description` varchar(45) DEFAULT NULL,
                      `icon` varchar(45) DEFAULT NULL,
                      `query` text DEFAULT NULL,
                      `default_project_id` int(10) unsigned DEFAULT NULL,
                      `default_attribute_id` int(10) unsigned DEFAULT NULL,
                      `default_sort_attribute_id` int(10) unsigned DEFAULT NULL COMMENT 'ci list',
                      `is_default_sort_asc` ENUM('0','1') NOT NULL DEFAULT 1,
                      `is_ci_attach` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'allow direct ci attachments',
                      `is_attribute_attach` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'allow additional attributes to be added',
                      `tag` varchar(100) DEFAULT NULL COMMENT 'tag',
                      `is_tab_enabled` enum('0','1') NOT NULL DEFAULT '0',
                      `is_event_enabled` enum('0','1') NOT NULL DEFAULT '0',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='types and classes of ci' AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_type_attribute\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_type_attribute` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ci type',
                      `attribute_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'default attribute',
                      `is_mandatory` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'attribute is mandatory for this ci type or not',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='connects default attributes to ci types' AUTO_INCREMENT=1
                ");

            echo "TABLES - ci_type_relation_type\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `ci_type_relation_type` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_type_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `ci_relation_type_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'free number for order by',
                      `max_amount` tinyint(10) unsigned DEFAULT NULL COMMENT 'null = unlimited',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='assigns ci_relation_types to ci_types' AUTO_INCREMENT=1
                ");

            echo "TABLES - cron\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `cron` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `type` enum('mailimport','reporting','customization','workflow') CHARACTER SET utf8 NOT NULL,
                      `mapping_id` int(10) unsigned NOT NULL,
                      `last_execution` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'unix timestamp',
                      `var_dump` varchar(10) CHARACTER SET utf8 DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - import_file_ftp\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `import_file_ftp` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(45) NOT NULL,
                      `description` varchar(45) NOT NULL,
                      `note` varchar(100) DEFAULT NULL,
                      `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
                      `host` varchar(50) NOT NULL,
                      `port` tinyint(10) unsigned NOT NULL DEFAULT '21',
                      `username` varchar(50) NOT NULL,
                      `password` varchar(50) NOT NULL,
                      `subfolder` varchar(50) NOT NULL,
                      `execution_time` varchar(15) NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='configuration for file imports' AUTO_INCREMENT=1
                ");

            echo "TABLES - import_file_history\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `import_file_history` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` int(10) unsigned NOT NULL,
                      `filename` varchar(200) NOT NULL,
                      `validation` enum('auto','manual') NOT NULL DEFAULT 'auto',
                      `queue` ENUM( 'idle', 'attribute', 'insert', 'update', 'import', 'relation' ) NOT NULL,
                      `status` enum('idle','in_progress','success','failed') NOT NULL,
                      `lines_processed` int(10) unsigned DEFAULT NULL,
                      `lines_total` int(10) unsigned DEFAULT NULL,
                      `note` varchar(200) DEFAULT NULL,
                      `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - import_file_history_detail\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `import_file_history_detail` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `import_file_history_id` int(10) unsigned NOT NULL,
                      `line` tinyint(10) unsigned NOT NULL,
                      `column` tinyint(10) unsigned DEFAULT NULL,
                      `message` varchar(200) NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - import_file_validation\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `import_file_validation` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `type` enum('update','insert') NOT NULL,
                      `name` varchar(200) NOT NULL COMMENT 'groupename. usually filename',
                      `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `finalized` datetime DEFAULT NULL,
                      `status` enum('in_progress','completed') NOT NULL,
                      `ci_type_id` int(10) unsigned DEFAULT NULL,
                      `project_id` int(10) unsigned DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - import_file_validation_attributes\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `import_file_validation_attributes` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `validation_id` int(10) unsigned NOT NULL COMMENT 'import_file_validation',
                      `unique_id` char(35) DEFAULT NULL,
                      `ci_id` int(10) unsigned NOT NULL,
                      `attribute_id` int(10) unsigned NOT NULL,
                      `value` text NOT NULL,
                      `note` varchar(100) DEFAULT NULL,
                      `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `user_id` int(10) unsigned DEFAULT NULL COMMENT 'who finalized?',
                      `finalized` datetime DEFAULT NULL,
                      `status` enum('idle','matched','overwritten','deleted') NOT NULL DEFAULT 'idle',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - import_mail\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `import_mail` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `host` varchar(100) NOT NULL,
                      `protocol` varchar(10) NOT NULL DEFAULT 'POP3',
                      `user` varchar(45) NOT NULL,
                      `password` varchar(45) NOT NULL,
                      `ssl` varchar(10) NOT NULL DEFAULT 'SSL',
                      `move_folder` varchar(30) DEFAULT NULL,
                      `is_extended` ENUM( '0', '1' ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL DEFAULT '0' COMMENT '0.. standard import -> add body as attachment; 1.. extended import -> create new ci, parse body and add values to ci attributes',
                      `ci_type_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'fill if extended',
                      `project_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'fill if extended',
                      `ci_field` varchar(15) NOT NULL DEFAULT '[CIID#:',
                      `is_attach_body` enum('0','1') NOT NULL DEFAULT '1',
                      `body_attribute_id` int(10) unsigned DEFAULT NULL,
                      `attachment_attribute_id` int(10) unsigned NOT NULL,
                      `is_ci_mail_enabled` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'ci can be identified via sender mail',
                      `note` varchar(300) DEFAULT NULL,
                      `execution_time` varchar(50) DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - mail\n";
            $this->execute("
                  CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `mail` (
                      `id` int(10) NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `description` varchar(100) NOT NULL,
                      `note` varchar(150) DEFAULT NULL,
                      `subject` varchar(150) NOT NULL,
                      `body` text,
                      `template` int(10) unsigned DEFAULT NULL,
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - menu\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `menu` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(45) NOT NULL DEFAULT '',
                      `description` varchar(45) NOT NULL DEFAULT '',
                      `note` varchar(60) DEFAULT NULL,
                      `function` varchar(45) NOT NULL DEFAULT '' COMMENT 'function call',
                      `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='defines all possible menu items' AUTO_INCREMENT=1
                ");

            echo "TABLES - notification\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `notification` (
                      `id` int(10) NOT NULL AUTO_INCREMENT,
                      `notification_id` int(10) unsigned NOT NULL,
                      `notification_type` enum('mail','import_mail','import_file','reporting') NOT NULL,
                      `type` enum('pm','mail') NOT NULL,
                      `address` varchar(100) DEFAULT NULL,
                      `user_id` int(10) unsigned DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - private_message\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `private_message` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `from_user_id` int(10) unsigned NOT NULL,
                      `to_user_id` int(10) unsigned NOT NULL,
                      `subject` varchar(100) NOT NULL,
                      `message` text NOT NULL,
                      `sent` datetime NOT NULL,
                      `read` datetime DEFAULT NULL,
                      `is_deleted` enum('0','1') NOT NULL DEFAULT '0',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - project\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `project` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL DEFAULT '' COMMENT 'project name',
                      `description` varchar(100) NOT NULL COMMENT 'project or contract description',
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `order_number` tinyint(5) unsigned NOT NULL COMMENT 'order number for sorting',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'project is valid or not',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='system groups or client contracts' AUTO_INCREMENT=1
                ");

            echo "TABLES - queue\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `queue` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(45) NOT NULL,
                      `note` varchar(200) DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - queue_message\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `queue_message` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `queue_id` int(10) unsigned NOT NULL,
                      `args` text NOT NULL COMMENT 'xml property structure',
                      `execution_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `creation_time` datetime DEFAULT NULL COMMENT 'updated by trigger',
                      `user_id` int(10) unsigned DEFAULT NULL COMMENT '0/null: system',
                      `priority` smallint(10) NOT NULL DEFAULT '1000',
                      `timeout` smallint(10) DEFAULT NULL COMMENT 'timeout for in_progress status',
                      `status` enum('idle','in_progress','completed','failed') NOT NULL COMMENT '0=idle, 1=in progress, 2=done',
                      PRIMARY KEY (`id`),
                      KEY `queue_id` (`queue_id`,`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - reporting\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `reporting` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `description` text NOT NULL,
                      `input` enum('sql','cql','script','scriptold','gui','extended') NOT NULL,
                      `output` enum('xls','csv','mailbody','none') NOT NULL,
                      `transport` enum('mail','ftp','none') NOT NULL,
                      `trigger` enum('time','manual','none') NOT NULL,
                      `note` varchar(300) DEFAULT NULL,
                      `statement` text COMMENT 'sql, cql',
                      `script` varchar(100) DEFAULT NULL,
                      `execution_time` varchar(50) DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - reporting_history\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `reporting_history` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` int(10) unsigned NOT NULL,
                      `reporting_id` int(10) unsigned NOT NULL,
                      `filename` varchar(100) NOT NULL,
                      `note` varchar(100) DEFAULT NULL,
                      `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - reporting_mapping\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `reporting_mapping` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `reporting_id` int(10) unsigned NOT NULL,
                      `mapping_id` int(10) unsigned NOT NULL,
                      `type` enum('ci_type','attribute') NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - role\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `role` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL DEFAULT '' COMMENT 'role name',
                      `description` varchar(100) NOT NULL DEFAULT '' COMMENT 'role description',
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'role is valid or not',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='role definition' AUTO_INCREMENT=1
                ");

            echo "TABLES - search_list\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `search_list` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `ci_type_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `is_scrollable` enum('0','1') NOT NULL DEFAULT '0',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - search_list_attribute\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `search_list_attribute` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0',
                      `search_list_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `attribute_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `column_width` int(10) unsigned DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - stored_query\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `stored_query` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `note` text,
                      `query` text NOT NULL,
                      `status` enum('0','1') NULL,
                      `status_message` text,
                      `is_default` enum('0','1') NOT NULL DEFAULT '0',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - templates\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `templates` (
                      `id` int(10) NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `description` varchar(100) NOT NULL,
                      `note` varchar(300) DEFAULT NULL,
                      `file` varchar(100) NOT NULL,
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - theme\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `theme` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL DEFAULT '',
                      `description` varchar(100) NOT NULL DEFAULT '',
                      `note` varchar(300) NOT NULL,
                      `menu_id` int(10) unsigned DEFAULT NULL COMMENT 'defines the startpage',
                      `is_wildcard_enabled` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'enables wildcard search',
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned NOT NULL,
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='themes definitions' AUTO_INCREMENT=1
                ");

            echo "TABLES - theme_menu\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `theme_menu` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `theme_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `menue_id` int(10) unsigned NOT NULL DEFAULT '0',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - theme_privilege\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `theme_privilege` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `resource_id` int(10) unsigned NOT NULL,
                      `theme_id` int(10) unsigned NOT NULL COMMENT 'FK',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - todo_items\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `todo_items` (
                      `id` int(10) unsigned NOT NULL COMMENT 'ci_attribute_id',
                      `user_id` int(10) unsigned NOT NULL,
                      `priority` enum('0','1','2') NOT NULL DEFAULT '1' COMMENT 'low, normal, high',
                      `status` enum('todo','deleted','done') NOT NULL DEFAULT 'todo',
                      `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `completed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
                ");

            echo "TABLES - user\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `user` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `username` varchar(45) NOT NULL DEFAULT '' COMMENT 'username for login',
                      `password` varchar(110) NOT NULL DEFAULT '' COMMENT 'password for login',
                      `email` varchar(100) DEFAULT NULL COMMENT 'FK mail_addresses',
                      `firstname` varchar(45) NOT NULL DEFAULT '',
                      `lastname` varchar(45) NOT NULL DEFAULT '',
                      `description` varchar(100) DEFAULT NULL COMMENT 'user description',
                      `note` varchar(300) DEFAULT NULL COMMENT 'individual note',
                      `theme_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `language` varchar(5) NOT NULL DEFAULT 'de',
                      `layout` varchar(15) NOT NULL DEFAULT 'default',
                      `is_root` enum('0','1') NOT NULL DEFAULT '0',
                      `is_ci_delete_enabled` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'grant for deleting a ci',
                      `is_relation_edit_enabled` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'grant for create and delete relations',
                      `is_ldap_auth` enum('0','1') DEFAULT '0',
                      `last_access` datetime DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'user is valid or not',
                      `user_id` int(10) unsigned DEFAULT NULL COMMENT 'nullable',
                      `valid_from` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='user definition' AUTO_INCREMENT=1
                ");

            echo "TABLES - user_history\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `user_history` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` int(10) unsigned NOT NULL,
                      `access` datetime NOT NULL,
                      `ip_address` varchar(45) NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - user_history_action\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `user_history_action` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_history_id` int(10) unsigned NOT NULL,
                      `action` varchar(150) NOT NULL,
                      `access` datetime NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - user_project\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `user_project` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'user',
                      `project_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'project',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='assigns a user to a project' AUTO_INCREMENT=1
                ");

            echo "TABLES - user_role\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `user_role` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` int(10) unsigned NOT NULL DEFAULT '0',
                      `role_id` int(10) unsigned NOT NULL DEFAULT '0',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Assigns users to roles' AUTO_INCREMENT=1
                ");

            echo "TABLES - workflow\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `description` varchar(100) NOT NULL,
                      `note` text,
                      `execute_user_id` int(10) unsigned NOT NULL,
                      `is_async` enum('0','1') NOT NULL DEFAULT '1',
                      `trigger_ci` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_ci_type_change` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_attribute` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_project` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_relation` enum('0','1') NOT NULL DEFAULT '0',
                      `trigger_time` enum('0','1') NOT NULL DEFAULT '0',
                      `execution_time` varchar(20) DEFAULT NULL,
                      `is_active` enum('0','1') NOT NULL DEFAULT '1',
                      `user_id` int(10) unsigned DEFAULT NULL,
                      `valid_from` datetime DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - workflow_arc\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow_arc` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `workflow_id` int(10) unsigned NOT NULL,
                      `workflow_transition_id` int(10) unsigned NOT NULL,
                      `workflow_place_id` int(10) unsigned NOT NULL,
                      `direction` enum('IN','OUT') NOT NULL,
                      `type` enum('SEQ','E_OR_SPLIT','I_OR_SPLIT','OR_JOIN','AND_SPLIT','AND_JOIN') NOT NULL DEFAULT 'SEQ',
                      `condition` text COMMENT 'only relevant if arc_type is set to E_OR_SPLIT',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - workflow_case\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow_case` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `workflow_id` int(10) unsigned NOT NULL,
                      `context` text,
                      `status` enum('OPEN','CLOSED','SUSPENDED','CANCELLED','FAILED') NOT NULL DEFAULT 'OPEN',
                      `created` datetime DEFAULT NULL,
                      `finished` datetime DEFAULT NULL,
                      `user_id` int(10) unsigned DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - workflow_item\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow_item` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `workflow_id` int(10) unsigned NOT NULL,
                      `workflow_case_id` int(10) unsigned NOT NULL,
                      `workflow_transition_id` int(10) unsigned NOT NULL,
                      `context` text,
                      `status` enum('ENABLED','IN_PROGRESS','CANCELLED','FINISHED','FAILED') NOT NULL,
                      `created` datetime DEFAULT NULL,
                      `finished` datetime DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - attribute\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow_log` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `workflow_case_id` int(10) unsigned NOT NULL,
                      `workflow_item_id` int(10) unsigned NOT NULL,
                      `message` text NOT NULL,
                      `created` datetime DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - workflow_place\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow_place` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `workflow_id` int(10) unsigned NOT NULL,
                      `type` enum('1','5','9') NOT NULL COMMENT '     * 1 = start place (there can be only one).\r\n    * 5 = intermediate place (there can be any number).\r\n    * 9 = end place (there can be only one).\r\n',
                      `name` varchar(45) NOT NULL,
                      `description` varchar(100) NOT NULL,
                      `note` text,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - workflow_task\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow_task` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(150) NOT NULL,
                      `note` text,
                      `script` varchar(150) NOT NULL,
                      `scriptname` varchar(150) NOT NULL,
                      `is_async` enum('0','1') NOT NULL DEFAULT '0',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - workflow_token\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow_token` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `workflow_id` int(10) unsigned NOT NULL,
                      `workflow_case_id` int(10) unsigned NOT NULL,
                      `workflow_place_id` int(10) unsigned NOT NULL,
                      `context` text NOT NULL,
                      `status` enum('FREE','LOCKED','CONSUMED','CANCELLED') NOT NULL DEFAULT 'FREE',
                      `created` datetime DEFAULT NULL,
                      `finished` datetime DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - workflow_transition\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow_transition` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `workflow_id` int(10) unsigned NOT NULL,
                      `name` varchar(150) NOT NULL,
                      `description` varchar(150) NOT NULL,
                      `note` text,
                      `trigger` enum('AUTO','USER','MSG','TIME') NOT NULL DEFAULT 'AUTO',
                      `trigger_time` int(10) unsigned DEFAULT NULL COMMENT 'seconds to wait',
                      `workflow_task_id` int(10) unsigned NOT NULL,
                      `role_id` int(10) unsigned DEFAULT NULL COMMENT 'TODO: use me?',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");

            echo "TABLES - workflow_trigger\n";
            $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `workflow_trigger` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `workflow_id` int(10) unsigned NOT NULL,
                      `mapping_id` int(10) unsigned NOT NULL,
                      `type` enum('ci','relation','project','attribute','ci_type_change') NOT NULL,
                      `method` enum('create','update','delete') NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ");


            /*
             * CONSTRAINTS
             */

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`attribute` ADD CONSTRAINT `FK_attribute_1` FOREIGN KEY `FK_attribute_1` (`attribute_type_id`)
                    REFERENCES `attribute_type` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_attribute_2` FOREIGN KEY `FK_attribute_2` (`attribute_group_id`)
                    REFERENCES `attribute_group` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`attribute_default_citype` ADD CONSTRAINT `FK_attribute_default_citype_1` FOREIGN KEY `FK_attribute_default_citype_1` (`attribute_id`)
                    REFERENCES `attribute` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_attribute_default_citype_2` FOREIGN KEY `FK_attribute_default_citype_2` (`ci_type_id`)
                    REFERENCES `ci_type` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`attribute_default_citype_attributes` ADD CONSTRAINT `FK_attribute_default_citype_attributes_1` FOREIGN KEY `FK_attribute_default_citype_attributes_1` (`attribute_default_citype_id`)
                    REFERENCES `attribute_default_citype` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_attribute_default_citype_attributes_2` FOREIGN KEY `FK_attribute_default_citype_attributes_2` (`attribute_id`)
                    REFERENCES `attribute` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`attribute_default_queries` ADD CONSTRAINT `FK_attribute_default_queries_1` FOREIGN KEY `FK_attribute_default_queries_1` (`attribute_id`)
                    REFERENCES `attribute` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`attribute_default_queries_parameter` ADD CONSTRAINT `FK_attribute_default_queries_parameter_1` FOREIGN KEY `FK_attribute_default_queries_parameter_1` (`queries_id`)
                    REFERENCES `attribute_default_queries` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`attribute_default_values` ADD CONSTRAINT `FK_attribute_default_values_1` FOREIGN KEY `FK_attribute_default_values_1` (`attribute_id`)
                    REFERENCES `attribute` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`attribute_role` ADD CONSTRAINT `FK_attribute_role_1` FOREIGN KEY `FK_attribute_role_1` (`role_id`)
                    REFERENCES `role` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_attribute_role_2` FOREIGN KEY `FK_attribute_role_2` (`attribute_id`)
                    REFERENCES `attribute` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci` ADD CONSTRAINT `FK_ci_1` FOREIGN KEY `FK_ci_1` (`ci_type_id`)
                    REFERENCES `ci_type` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_attribute` ADD CONSTRAINT `FK_ci_attribute_1` FOREIGN KEY `FK_ci_attribute_1` (`ci_id`)
                    REFERENCES `ci` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_ci_attribute_2` FOREIGN KEY `FK_ci_attribute_2` (`attribute_id`)
                    REFERENCES `attribute` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_event` ADD CONSTRAINT `FK_ci_event_1` FOREIGN KEY `FK_ci_event_1` (`ci_id`)
                    REFERENCES `ci` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_favourites` ADD CONSTRAINT `FK_ci_favourites_1` FOREIGN KEY `FK_ci_favourites_1` (`user_id`)
                    REFERENCES `user` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_ci_favourites_2` FOREIGN KEY `FK_ci_favourites_2` (`ci_id`)
                    REFERENCES `ci` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_highlight` ADD CONSTRAINT `FK_ci_highlight_1` FOREIGN KEY `FK_ci_highlight_1` (`user_id`)
                    REFERENCES `user` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_ci_highlight_2` FOREIGN KEY `FK_ci_highlight_2` (`ci_id`)
                    REFERENCES `ci` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_permission` ADD CONSTRAINT `FK_ci_permission_1` FOREIGN KEY `FK_ci_permission_1` (`ci_id`)
                    REFERENCES `ci` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_ci_permission_2` FOREIGN KEY `FK_ci_permission_2` (`user_id`)
                    REFERENCES `user` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_project` ADD CONSTRAINT `FK_ci_project_1` FOREIGN KEY `FK_ci_project_1` (`ci_id`)
                    REFERENCES `ci` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_ci_project_2` FOREIGN KEY `FK_ci_project_2` (`project_id`)
                    REFERENCES `project` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_relation` ADD CONSTRAINT `FK_ci_relation_1` FOREIGN KEY `FK_ci_relation_1` (`ci_relation_type_id`)
                    REFERENCES `ci_relation_type` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_ci_relation_2` FOREIGN KEY `FK_ci_relation_2` (`ci_id_1`)
                    REFERENCES `ci` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_ci_relation_3` FOREIGN KEY `FK_ci_relation_3` (`ci_id_2`)
                    REFERENCES `ci` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_ticket` ADD CONSTRAINT `FK_ci_ticket_1` FOREIGN KEY `FK_ci_ticket_1` (`ci_id`)
                    REFERENCES `ci` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_type_attribute` ADD CONSTRAINT `FK_ci_type_attribute_1` FOREIGN KEY `FK_ci_type_attribute_1` (`ci_type_id`)
                    REFERENCES `ci_type` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_ci_type_attribute_2` FOREIGN KEY `FK_ci_type_attribute_2` (`attribute_id`)
                    REFERENCES `attribute` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`ci_type_relation_type` ADD CONSTRAINT `FK_ci_type_relation_type_1` FOREIGN KEY `FK_ci_type_relation_type_1` (`ci_type_id`)
                    REFERENCES `ci_type` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_ci_type_relation_type_2` FOREIGN KEY `FK_ci_type_relation_type_2` (`ci_relation_type_id`)
                    REFERENCES `ci_relation_type` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`import_file_history_detail` ADD CONSTRAINT `FK_import_file_history_detail_1` FOREIGN KEY `FK_import_file_history_detail_1` (`import_file_history_id`)
                    REFERENCES `import_file_history` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`import_file_validation_attributes` ADD CONSTRAINT `FK_import_file_validation_attributes_1` FOREIGN KEY `FK_import_file_validation_attributes_1` (`validation_id`)
                    REFERENCES `import_file_validation` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_import_file_validation_attributes_2` FOREIGN KEY `FK_import_file_validation_attributes_2` (`attribute_id`)
                    REFERENCES `attribute` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`private_message` ADD CONSTRAINT `FK_private_message_1` FOREIGN KEY `FK_private_message_1` (`from_user_id`)
                    REFERENCES `user` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_private_message_2` FOREIGN KEY `FK_private_message_2` (`to_user_id`)
                    REFERENCES `user` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`queue_message` ADD CONSTRAINT `FK_queue_message_1` FOREIGN KEY `FK_queue_message_1` (`queue_id`)
                    REFERENCES `queue` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`reporting_history` ADD CONSTRAINT `FK_reporting_history_1` FOREIGN KEY `FK_reporting_history_1` (`reporting_id`)
                    REFERENCES `reporting` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`search_list` ADD CONSTRAINT `FK_search_list_1` FOREIGN KEY `FK_search_list_1` (`ci_type_id`)
                    REFERENCES `ci_type` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`search_list_attribute` ADD CONSTRAINT `FK_search_list_attribute_1` FOREIGN KEY `FK_search_list_attribute_1` (`search_list_id`)
                    REFERENCES `search_list` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`search_list_attribute` ADD CONSTRAINT `FK_search_list_attribute_2` FOREIGN KEY `FK_search_list_attribute_2` (`attribute_id`)
                    REFERENCES `attribute` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`theme_menu` ADD CONSTRAINT `FK_theme_menu_1` FOREIGN KEY `FK_theme_menu_1` (`theme_id`)
                    REFERENCES `theme` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_theme_menu_2` FOREIGN KEY `FK_theme_menu_2` (`menue_id`)
                    REFERENCES `menu` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`theme_privilege` ADD CONSTRAINT `FK_theme_privilege_1` FOREIGN KEY `FK_theme_privilege_1` (`theme_id`)
                    REFERENCES `theme` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`todo_items` ADD CONSTRAINT `FK_todo_items_1` FOREIGN KEY `FK_todo_items_1` (`user_id`)
                    REFERENCES `user` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`user` ADD CONSTRAINT `FK_user_1` FOREIGN KEY `FK_user_1` (`theme_id`)
                    REFERENCES `theme` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`user_project` ADD CONSTRAINT `FK_user_project_1` FOREIGN KEY `FK_user_project_1` (`user_id`)
                    REFERENCES `user` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_user_project_2` FOREIGN KEY `FK_user_project_2` (`project_id`)
                    REFERENCES `project` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`user_role` ADD CONSTRAINT `FK_user_role_1` FOREIGN KEY `FK_user_role_1` (`user_id`)
                    REFERENCES `user` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_user_role_2` FOREIGN KEY `FK_user_role_2` (`role_id`)
                    REFERENCES `role` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`workflow_transition` ADD CONSTRAINT `FK_workflow_transition_1` FOREIGN KEY `FK_workflow_transition_1` (`workflow_id`)
                    REFERENCES `workflow` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_workflow_transition_2` FOREIGN KEY `FK_workflow_transition_2` (`workflow_task_id`)
                    REFERENCES `workflow_task` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`workflow_arc` ADD CONSTRAINT `FK_workflow_arc_1` FOREIGN KEY `FK_workflow_arc_1` (`workflow_id`)
                    REFERENCES `workflow` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_workflow_arc_2` FOREIGN KEY `FK_workflow_arc_2` (`workflow_transition_id`)
                    REFERENCES `workflow_transition` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_workflow_arc_3` FOREIGN KEY `FK_workflow_arc_3` (`workflow_place_id`)
                    REFERENCES `workflow_place` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`workflow_case` ADD CONSTRAINT `FK_workflow_case_1` FOREIGN KEY `FK_workflow_case_1` (`workflow_id`)
                    REFERENCES `workflow` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`workflow_item` ADD CONSTRAINT `FK_workflow_item_1` FOREIGN KEY `FK_workflow_item_1` (`workflow_id`)
                    REFERENCES `workflow` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_workflow_item_2` FOREIGN KEY `FK_workflow_item_2` (`workflow_case_id`)
                    REFERENCES `workflow_case` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_workflow_item_3` FOREIGN KEY `FK_workflow_item_3` (`workflow_transition_id`)
                    REFERENCES `workflow_transition` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`workflow_log` ADD CONSTRAINT `FK_workflow_log_1` FOREIGN KEY `FK_workflow_log_1` (`workflow_case_id`)
                    REFERENCES `workflow_case` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                     ADD CONSTRAINT `FK_workflow_log_2` FOREIGN KEY `FK_workflow_log_2` (`workflow_item_id`)
                    REFERENCES `workflow_item` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`workflow_place` ADD CONSTRAINT `FK_workflow_place_1` FOREIGN KEY `FK_workflow_place_1` (`workflow_id`)
                    REFERENCES `workflow` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`workflow_token` ADD CONSTRAINT `FK_workflow_token_1` FOREIGN KEY `FK_workflow_token_1` (`workflow_id`)
                    REFERENCES `workflow` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_workflow_token_2` FOREIGN KEY `FK_workflow_token_2` (`workflow_case_id`)
                    REFERENCES `workflow_case` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT,
                     ADD CONSTRAINT `FK_workflow_token_3` FOREIGN KEY `FK_workflow_token_3` (`workflow_place_id`)
                    REFERENCES `workflow_place` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE RESTRICT
                ");

            $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables`.`workflow_trigger` ADD CONSTRAINT `FK_workflow_trigger_1` FOREIGN KEY `FK_workflow_trigger_1` (`workflow_id`)
                    REFERENCES `workflow` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");


            /*
             * VIEWS
             */
            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute AS
                select *
                from " . $this->dbName . "_tables.attribute
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_default_citype AS
                select *
                from " . $this->dbName . "_tables.attribute_default_citype
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_default_citype_attributes AS
                select *
                from " . $this->dbName . "_tables.attribute_default_citype_attributes
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_default_queries AS
                select *
                from " . $this->dbName . "_tables.attribute_default_queries
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_default_queries_parameter AS
                select *
                from " . $this->dbName . "_tables.attribute_default_queries_parameter
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_default_values AS
                select *
                from " . $this->dbName . "_tables.attribute_default_values
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_group AS
                select *
                from " . $this->dbName . "_tables.attribute_group
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_role AS
                select *
                from " . $this->dbName . "_tables.attribute_role
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_type AS
                select *
                from " . $this->dbName . "_tables.attribute_type
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci AS
                select *
                from " . $this->dbName . "_tables.ci
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_attribute AS
                select *
                from " . $this->dbName . "_tables.ci_attribute
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_event AS
                select *
                from " . $this->dbName . "_tables.ci_event
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_favourites AS
                select *
                from " . $this->dbName . "_tables.ci_favourites
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_highlight AS
                select *
                from " . $this->dbName . "_tables.ci_highlight
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_permission AS
                select *
                from " . $this->dbName . "_tables.ci_permission
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_project AS
                select *
                from " . $this->dbName . "_tables.ci_project
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_relation AS
                select *
                from " . $this->dbName . "_tables.ci_relation
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_relation_direction AS
                select *
                from " . $this->dbName . "_tables.ci_relation_direction
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_relation_type AS
                select *
                from " . $this->dbName . "_tables.ci_relation_type
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_ticket AS
                select *
                from " . $this->dbName . "_tables.ci_ticket
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_type AS
                select *
                from " . $this->dbName . "_tables.ci_type
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_type_attribute AS
                select *
                from " . $this->dbName . "_tables.ci_type_attribute
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_type_relation_type AS
                select *
                from " . $this->dbName . "_tables.ci_type_relation_type
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".cron AS
                select *
                from " . $this->dbName . "_tables.cron
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".h_ci AS
                select * from " . $this->dbName . "_history.ci
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".h_ci_attribute AS
                select * from " . $this->dbName . "_history.ci_attribute
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".h_ci_project AS
                select * from " . $this->dbName . "_history.ci_project
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".h_ci_relation AS
                select * from " . $this->dbName . "_history.ci_relation
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".h_history AS
                select *
                from " . $this->dbName . "_history.history
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".history AS
                select *
                from " . $this->dbName . "_history.history
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".import_file_ftp AS
                select *
                from " . $this->dbName . "_tables.import_file_ftp
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".import_file_history AS
                select *
                from " . $this->dbName . "_tables.import_file_history
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".import_file_history_detail AS
                select *
                from " . $this->dbName . "_tables.import_file_history_detail
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".import_file_validation AS
                select *
                from " . $this->dbName . "_tables.import_file_validation
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".import_file_validation_attributes AS
                select *
                from " . $this->dbName . "_tables.import_file_validation_attributes
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".import_mail AS
                select *
                from " . $this->dbName . "_tables.import_mail
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".mail AS
                select *
                from " . $this->dbName . "_tables.mail
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".menu AS
                select *
                from " . $this->dbName . "_tables.menu
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".notification AS
                select *
                from " . $this->dbName . "_tables.notification
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".private_message AS
                select *
                from " . $this->dbName . "_tables.private_message
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".project AS
                select *
                from " . $this->dbName . "_tables.project
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".queue AS
                select *
                from " . $this->dbName . "_tables.queue
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".queue_message AS
                select *
                from " . $this->dbName . "_tables.queue_message
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".reporting AS
                select *
                from " . $this->dbName . "_tables.reporting
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".reporting_history AS
                select *
                from " . $this->dbName . "_tables.reporting_history
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".reporting_mapping AS
                select *
                from " . $this->dbName . "_tables.reporting_mapping
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".role AS
                select *
                from " . $this->dbName . "_tables.role
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".search_list AS
                select *
                from " . $this->dbName . "_tables.search_list
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".search_list_attribute AS
                select *
                from " . $this->dbName . "_tables.search_list_attribute
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".stored_query AS
                select *
                from " . $this->dbName . "_tables.stored_query
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".templates AS
                select *
                from " . $this->dbName . "_tables.templates
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".theme AS
                select *
                from " . $this->dbName . "_tables.theme
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".theme_menu AS
                select *
                from " . $this->dbName . "_tables.theme_menu
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".theme_privilege AS
                select *
                from " . $this->dbName . "_tables.theme_privilege
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".todo_items AS
                select *
                from " . $this->dbName . "_tables.todo_items
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".user AS
                select *
                from " . $this->dbName . "_tables.user
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".user_history AS
                select *
                from " . $this->dbName . "_tables.user_history
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".user_history_action AS
                select *
                from " . $this->dbName . "_tables.user_history_action
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".user_project AS
                select *
                from " . $this->dbName . "_tables.user_project
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".user_role AS
                select *
                from " . $this->dbName . "_tables.user_role
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow AS
                select *
                from " . $this->dbName . "_tables.workflow
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_arc AS
                select *
                from " . $this->dbName . "_tables.workflow_arc
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_case AS
                select *
                from " . $this->dbName . "_tables.workflow_case
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_item AS
                select *
                from " . $this->dbName . "_tables.workflow_item
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_log AS
                select *
                from " . $this->dbName . "_tables.workflow_log
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_place AS
                select *
                from " . $this->dbName . "_tables.workflow_place
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_task AS
                select *
                from " . $this->dbName . "_tables.workflow_task
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_token AS
                select *
                from " . $this->dbName . "_tables.workflow_token
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_transition AS
                select *
                from " . $this->dbName . "_tables.workflow_transition
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_trigger AS
                select *
                from " . $this->dbName . "_tables.workflow_trigger
                WITH CASCADED CHECK OPTION
            ");


            /*
             * TABLES
             */

            $this->execute("
                CREATE TABLE IF NOT EXISTS `" . $this->dbName . "`.`api_session` (
                  `apikey` char(30) NOT NULL,
                  `user_id` int(10) unsigned NOT NULL,
                  `valid_from` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `valid_to` int(10) NOT NULL,
                  PRIMARY KEY (`apikey`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");

            $this->execute("
                CREATE TABLE IF NOT EXISTS `" . $this->dbName . "`.`password_reset` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `user_id` int(10) unsigned NOT NULL,
                  `hash` varchar(100) NOT NULL,
                  `valid_to` datetime NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
            ");

            $this->execute("
                CREATE TABLE IF NOT EXISTS `" . $this->dbName . "`.`search_result` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `session` char(45) NOT NULL,
                  `ci_id` int(10) unsigned NOT NULL,
                  `citype_id` int(10) unsigned NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `Session` (`session`)
                ) ENGINE=MEMORY DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
            ");

            $this->execute("
                CREATE TABLE IF NOT EXISTS `" . $this->dbName . "`.`search_session` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `timeout` int(32) unsigned NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
            ");

            $this->execute("
                CREATE TABLE IF NOT EXISTS `" . $this->dbName . "`.`user_session` (
                  `id` char(32) NOT NULL,
                  `modified` int(11) NOT NULL,
                  `lifetime` int(11) NOT NULL,
                  `user_id` int(10) DEFAULT NULL,
                  `ip_address` varchar(45) DEFAULT NULL,
                  `data` longtext NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=INNODB DEFAULT CHARSET=utf8
            ");

        } else {
            print "SKIPPED migration\n";
        }

    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DROP DATABASE " . $this->dbName . "_tables");
        $this->execute("DROP DATABASE " . $this->dbName . "_history");
    }
}