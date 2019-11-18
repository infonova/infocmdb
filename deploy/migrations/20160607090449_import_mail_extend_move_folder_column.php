<?php

use PhinxExtend\AbstractPhinxMigration;

class ImportMailExtendMoveFolderColumn extends AbstractPhinxMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        // we can be sure that this column exists because of previous migrations
        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`import_mail` MODIFY COLUMN `move_folder` varchar(100) DEFAULT NULL");

        // extend 'move_folder' to 100 chars or create column if not exists
        if($this->hasColumn($this->dbName."_history", "import_mail", "move_folder")) {
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`import_mail` MODIFY COLUMN `move_folder` varchar(100) DEFAULT NULL");
        } else {
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`import_mail` ADD `move_folder` varchar(100) DEFAULT NULL  AFTER `ssl`");
        }

        // add protocol column to history table if missing
        if(!$this->hasColumn($this->dbName."_history", "import_mail", "protocol")) {
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`import_mail` ADD `protocol` varchar(10) NOT NULL DEFAULT 'POP3'  AFTER `host`");
        }



        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`import_mail_update`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.import_mail_update BEFORE UPDATE ON import_mail
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.import_mail(host, protocol, user, password, ci_field, `ssl`, move_folder, is_extended, ci_type_id, is_attach_body, body_attribute_id, attachment_attribute_id, is_ci_mail_enabled, note, execution_time, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.host, OLD.protocol, OLD.user, OLD.password, OLD.ci_field, OLD.ssl, OLD.move_folder, OLD.is_extended, OLD.ci_type_id, OLD.is_attach_body, OLD.body_attribute_id, OLD.attachment_attribute_id, OLD.is_ci_mail_enabled, OLD.note, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
        ");


        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`import_mail_delete`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.import_mail_delete BEFORE DELETE ON import_mail
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.import_mail(host, protocol, user, password, ci_field, `ssl`, move_folder, is_extended, ci_type_id, is_attach_body, body_attribute_id, attachment_attribute_id, is_ci_mail_enabled, note, execution_time, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.host, OLD.protocol, OLD.user, OLD.password, OLD.ci_field, OLD.ssl, OLD.move_folder,  OLD.is_extended, OLD.ci_type_id, OLD.is_attach_body, OLD.body_attribute_id, OLD.attachment_attribute_id, OLD.is_ci_mail_enabled, OLD.note, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
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
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
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

    }
    

        
}
