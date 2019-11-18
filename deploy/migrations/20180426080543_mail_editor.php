<?php

use PhinxExtend\AbstractPhinxMigration;

class MailEditor extends AbstractPhinxMigration
{

    public function up()
    {
        $mail = $this->table('mail');

        if ( !$mail->hasColumn('editor')) {
            $this->execute("ALTER TABLE " . $this->dbName . "_tables .  mail ADD COLUMN editor VARCHAR(12) NULL AFTER mime_type");
            $this->execute("ALTER TABLE " . $this->dbName . "_history . mail ADD COLUMN editor VARCHAR(12) NULL AFTER mime_type");
            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER
    
                VIEW " . $this->dbName . ".mail AS
                select *
                from " . $this->dbName . "_tables.mail
                WITH CASCADED CHECK OPTION
            ");

            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.mail_update");
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.mail_delete");

            $this->execute("
                CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.mail_update BEFORE UPDATE ON mail
                FOR EACH ROW BEGIN
                IF NEW.user_id IS NULL THEN
                SET NEW.user_id = 0;
                END IF;
    
                INSERT INTO " . $this->dbName . "_history.mail(name, description, note, subject, mime_type, editor, body, template, user_id, valid_from, user_id_delete, valid_to)
                VALUES(OLD.name, OLD.description, OLD.note, OLD.subject, OLD.mime_type, OLD.editor, OLD.body, OLD.template, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());
    
                SET NEW.valid_from = NOW();
                END;
            ");

            $this->execute("
                CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.mail_delete BEFORE DELETE ON mail
                FOR EACH ROW BEGIN
                INSERT INTO " . $this->dbName . "_history.mail(name, description, note, subject, mime_type, editor, body, template, user_id, valid_from, user_id_delete, valid_to)
                VALUES(OLD.name, OLD.description, OLD.note, OLD.subject, OLD.mime_type, OLD.editor, OLD.body, OLD.template, OLD.user_id, OLD.valid_from, '0', NOW());
                END;
            ");
        }
    }
}
