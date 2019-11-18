<?php

use PhinxExtend\AbstractPhinxMigration;

class CiTypeSql extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $workflowTable = $this->table('ci_type');
        if(!$workflowTable->hasColumn('query')) {

            //ci_type - add column: query
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci_type` ADD `query` TEXT NULL DEFAULT NULL AFTER `icon`");
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`ci_type` ADD `query` TEXT NULL DEFAULT NULL AFTER `icon`");

            //recreate view
            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_type AS
                select *
                from " . $this->dbName . "_tables.ci_type
                WITH CASCADED CHECK OPTION
            ");

        }

        #ci_type - on update
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_type_update`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_type_update BEFORE UPDATE ON ci_type
              FOR EACH ROW BEGIN
                IF NEW.user_id IS NULL THEN
                  SET NEW.user_id = 0;
                END IF;

                INSERT INTO " . $this->dbName . "_history.ci_type(id, name, description, note, parent_ci_type_id, order_number, create_button_description, icon, query, default_project_id, default_attribute_id, default_sort_attribute_id, is_default_sort_asc, is_ci_attach, is_attribute_attach, tag, is_tab_enabled, is_event_enabled, is_active, user_id, valid_from, user_id_delete, valid_to)
                VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.parent_ci_type_id, OLD.order_number, OLD.create_button_description, OLD.icon, OLD.query, OLD.default_project_id, OLD.default_attribute_id, OLD.default_sort_attribute_id, OLD.is_default_sort_asc, OLD.is_ci_attach, OLD.is_attribute_attach, OLD.tag, OLD.is_tab_enabled, OLD.is_event_enabled, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());
                SET NEW.valid_from = NOW();
              END
        ");

        #ci_type - on delete
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_type_delete`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_type_delete BEFORE DELETE ON ci_type
              FOR EACH ROW BEGIN
                INSERT INTO " . $this->dbName . "_history.ci_type(id, name, description, note, parent_ci_type_id, order_number, create_button_description, icon, query, default_project_id, default_attribute_id, default_sort_attribute_id, is_default_sort_asc, is_ci_attach, is_attribute_attach, tag, is_tab_enabled, is_event_enabled, is_active, user_id, valid_from, user_id_delete, valid_to)
                VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.parent_ci_type_id, OLD.order_number, OLD.create_button_description, OLD.icon, OLD.query, OLD.default_project_id, OLD.default_attribute_id, OLD.default_sort_attribute_id, OLD.is_default_sort_asc, OLD.is_ci_attach, OLD.is_attribute_attach, OLD.tag, OLD.is_tab_enabled, OLD.is_event_enabled, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
              END
        ");
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        //recreate view
        $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".ci_type AS
            select *
            from " . $this->dbName . "_tables.ci_type
            WITH CASCADED CHECK OPTION
        ");

    }
}