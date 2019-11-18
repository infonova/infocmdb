<?php

use PhinxExtend\AbstractPhinxMigration;

class AddMissingColumns extends AbstractPhinxMigration
{

	/**
	 * Migrate Up.
	 */
	public function up()
	{

		// init tables
		$ciProjectTable = $this->table('ci_project');
		$ciTypeRelationTypeTable = $this->table('ci_type_relation_type');
		$importMailTable = $this->table('import_mail');
		$attributeTable = $this->table('attribute');
		$ciTypeTable = $this->table('ci_type');

		// add missing columns
		if(!$ciProjectTable->hasColumn('history_id')) {
			$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci_project` ADD `history_id` int(10) NOT NULL  AFTER `project_id`");
			print "    added column: ci_project - history_id\n";
		}

		if(!$ciTypeRelationTypeTable->hasColumn('order_number')) {
			$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci_type_relation_type` ADD `order_number` tinyint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'free number for order by'  AFTER `ci_relation_type_id`");
			print "    added column: ci_type_relation_type - order_number\n";
		}

		if(!$importMailTable->hasColumn('protocol')) {
			$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`import_mail` ADD `protocol` varchar(10) NOT NULL DEFAULT 'POP3'  AFTER `host`");
			print "    added column: import_mail - protocol\n";
		}

		if(!$importMailTable->hasColumn('move_folder')) {
			$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`import_mail` ADD `move_folder` varchar(30) DEFAULT NULL  AFTER `ssl`");
			print "    added column: import_mail - move_folder\n";
		}

		if(!$attributeTable->hasColumn('is_unique_check')) {
			$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute` ADD `is_unique_check` enum('0','1') NOT NULL DEFAULT '0'  AFTER `is_event`");
			$this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute` ADD `is_unique_check` enum('0','1') NOT NULL DEFAULT '0'  AFTER `is_event`");
			print "    added column: attribute - is_unique_check\n";
		}

		if(!$attributeTable->hasColumn('tag')) {
			$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute` ADD `tag` varchar(100) DEFAULT NULL  AFTER `script_name`");
			$this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute` ADD `tag` varchar(100) DEFAULT NULL  AFTER `script_name`");
			print "    added column: attribute - tag\n";
		}

		if(!$ciTypeTable->hasColumn('tag')) {
			$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci_type` ADD `tag` varchar(100) DEFAULT NULL  AFTER `is_attribute_attach`");
			$this->execute("ALTER TABLE `" . $this->dbName . "_history`.`ci_type` ADD `tag` varchar(100) DEFAULT NULL  AFTER `is_attribute_attach`");
			print "    added column: citype - tag\n";
		}

		if(!$ciTypeTable->hasColumn('is_tab_enabled')) {
			$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci_type` ADD `is_tab_enabled` enum('0','1') NOT NULL DEFAULT '0' AFTER `tag`");
			$this->execute("ALTER TABLE `" . $this->dbName . "_history`.`ci_type` ADD `is_tab_enabled` enum('0','1') NOT NULL DEFAULT '0' AFTER `tag`");
			print "    added column: citype - is_tab_enabled\n";
		}


		// recreate tiggers
		$this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_update`");
		$this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_update BEFORE UPDATE ON attribute
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, script_name, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.script_name, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

		$this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_delete`");
		$this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_delete BEFORE DELETE ON attribute
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, script_name, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.script_name, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

		$this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_type_update`");
		$this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_type_update BEFORE UPDATE ON ci_type
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.ci_type(id, name, description, note, parent_ci_type_id, order_number, create_button_description, icon, default_project_id, default_attribute_id, default_sort_attribute_id, is_default_sort_asc, is_ci_attach, is_attribute_attach, tag, is_tab_enabled, is_event_enabled, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.parent_ci_type_id, OLD.order_number, OLD.create_button_description, OLD.icon, OLD.default_project_id, OLD.default_attribute_id, OLD.default_sort_attribute_id, OLD.is_default_sort_asc, OLD.is_ci_attach, OLD.is_attribute_attach, OLD.tag, OLD.is_tab_enabled, OLD.is_event_enabled, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
        ");



		$this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_type_delete`");
		$this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_type_delete BEFORE DELETE ON ci_type
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.ci_type(id, name, description, note, parent_ci_type_id, order_number, create_button_description, icon, default_project_id, default_attribute_id, default_sort_attribute_id, is_default_sort_asc, is_ci_attach, is_attribute_attach, tag, is_tab_enabled, is_event_enabled, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.parent_ci_type_id, OLD.order_number, OLD.create_button_description, OLD.icon, OLD.default_project_id, OLD.default_attribute_id, OLD.default_sort_attribute_id, OLD.is_default_sort_asc, OLD.is_ci_attach, OLD.is_attribute_attach, OLD.tag, OLD.is_tab_enabled, OLD.is_event_enabled, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
        ");







		// recreate views

		// ci_project
		$this->execute("
			CREATE OR REPLACE ALGORITHM=MERGE
			DEFINER=`root`@`localhost`
			SQL SECURITY DEFINER

			VIEW " . $this->dbName . ".ci_project AS
			select *
			from " . $this->dbName . "_tables.ci_project
			WITH CASCADED CHECK OPTION
		");

		// ci_type_relation_type
		$this->execute("
			CREATE OR REPLACE ALGORITHM=MERGE
			DEFINER=`root`@`localhost`
			SQL SECURITY DEFINER

			VIEW " . $this->dbName . ".ci_type_relation_type AS
			select *
			from " . $this->dbName . "_tables.ci_type_relation_type
			WITH CASCADED CHECK OPTION
		");

		// import_mail
		$this->execute("
			CREATE OR REPLACE ALGORITHM=MERGE
			DEFINER=`root`@`localhost`
			SQL SECURITY DEFINER

			VIEW " . $this->dbName . ".import_mail AS
			select *
			from " . $this->dbName . "_tables.import_mail
			WITH CASCADED CHECK OPTION
		");

		// attribute
		$this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER
                VIEW " . $this->dbName . ".attribute AS
                select *
                from " . $this->dbName . "_tables.attribute
                WITH CASCADED CHECK OPTION
       	");


	}

	/**
	 * Migrate Down.
	 */
	public function down()
	{

	}
}