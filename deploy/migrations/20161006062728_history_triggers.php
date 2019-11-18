<?php

use PhinxExtend\AbstractPhinxMigration;

class HistoryTriggers extends AbstractPhinxMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->execute('SET autocommit = 1');

        echo "Update User-History-Trigger\n";
        // user update - do not write history for last_access, do not save passwords in history
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`user_update`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.user_update BEFORE UPDATE ON user
            FOR EACH ROW BEGIN
                
                DECLARE changed INTEGER;
                SET changed = 0;
                
                IF NEW.user_id IS NULL THEN
                    SET NEW.user_id = 0;
                END IF;
                
                IF (NEW.username IS NOT NULL AND STRCMP(NEW.username, OLD.username) <> 0) or
                (NEW.email IS NOT NULL AND STRCMP(NEW.email, OLD.email) <> 0) or
                (NEW.firstname IS NOT NULL AND STRCMP(NEW.firstname, OLD.firstname) <> 0) or
                (NEW.lastname IS NOT NULL AND STRCMP(NEW.lastname, OLD.lastname) <> 0) or
                (NEW.description IS NOT NULL AND STRCMP(NEW.description, OLD.description) <> 0) or
                (NEW.note IS NOT NULL AND STRCMP(NEW.note, OLD.note) <> 0) or
                (NEW.theme_id IS NOT NULL AND STRCMP(NEW.theme_id, OLD.theme_id) <> 0) or
                (NEW.language IS NOT NULL AND STRCMP(NEW.language, OLD.language) <> 0) or
                (NEW.layout IS NOT NULL AND STRCMP(NEW.layout, OLD.layout) <> 0) or
                (NEW.is_root IS NOT NULL AND STRCMP(NEW.is_root, OLD.is_root) <> 0) or
                (NEW.is_ci_delete_enabled IS NOT NULL AND STRCMP(NEW.is_ci_delete_enabled, OLD.is_ci_delete_enabled) <> 0) or
                (NEW.is_relation_edit_enabled IS NOT NULL AND STRCMP(NEW.is_relation_edit_enabled, OLD.is_relation_edit_enabled) <> 0) or
                (NEW.is_ldap_auth IS NOT NULL AND STRCMP(NEW.is_ldap_auth, OLD.is_ldap_auth) <> 0) or
                (NEW.is_active IS NOT NULL AND STRCMP(NEW.is_active, OLD.is_active) <> 0)
                THEN SET changed = 1;
                END IF;
                
                IF changed > 0 THEN	
                    INSERT INTO " . $this->dbName . "_history.user(id, username, password, email, firstname, lastname, description, note, theme_id, language, layout, is_root, is_ci_delete_enabled, is_relation_edit_enabled, is_ldap_auth, last_access, is_active, user_id, valid_from, user_id_delete, valid_to)
                    VALUES(OLD.id, OLD.username, '', OLD.email, OLD.firstname, OLD.lastname, OLD.description, OLD.note, OLD.theme_id, OLD.language, OLD.layout, OLD.is_root, OLD.is_ci_delete_enabled, OLD.is_relation_edit_enabled, OLD.is_ldap_auth, OLD.last_access, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());
                    SET NEW.valid_from = NOW();
                END IF;
            END;
        ");

        echo "Clearing User History...\n";
        $this->execute("DELETE FROM `" . $this->dbName . "_history`.`user` WHERE valid_to < DATE_SUB(NOW(), INTERVAL 14 DAY )");
        $this->execute("UPDATE `" . $this->dbName . "_history`.`user` SET password = ''");


        echo "Update stored_query-History-Trigger\n";
        // stored query - do not write history if status changes
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`stored_query_update`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.stored_query_update BEFORE UPDATE ON stored_query
            FOR EACH ROW BEGIN
            
                DECLARE changed INTEGER;
                SET changed = 0;
                
                IF NEW.user_id IS NULL THEN
                  SET NEW.user_id = 0;
                END IF;
                
                IF (NEW.name IS NOT NULL AND STRCMP(NEW.name, OLD.name) <> 0) or
                (NEW.note IS NOT NULL AND STRCMP(NEW.note, OLD.note) <> 0) or
                (NEW.query IS NOT NULL AND STRCMP(NEW.query, OLD.query) <> 0) or
                (NEW.is_default IS NOT NULL AND STRCMP(NEW.is_default, OLD.is_default) <> 0) or
                (NEW.is_active IS NOT NULL AND STRCMP(NEW.is_active, OLD.is_active) <> 0)
                THEN SET changed = 1;
                END IF;
                
                IF changed > 0 THEN
                  INSERT INTO " . $this->dbName . "_history.stored_query(id, name, note, query, status, status_message, is_default, is_active, user_id, valid_from, user_id_delete, valid_to)
                  VALUES(OLD.id, OLD.name, OLD.note, OLD.query, OLD.status, OLD.status_message, OLD.is_default, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());
                  SET NEW.valid_from = NOW();
                END IF;
            END;
        ");

        echo "To clear existing stored query history run:\n\n";
        echo "TRUNCATE TABLE `" . $this->dbName . "_history`.`stored_query`\n\n";

        $this->execute('SET autocommit = 0');
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        //no down-action
    }
}
