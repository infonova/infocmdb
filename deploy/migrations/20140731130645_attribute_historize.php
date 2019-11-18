<?php

use PhinxExtend\AbstractPhinxMigration;

class AttributeHistorize extends AbstractPhinxMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $attributeTable = $this->table('attribute');
        if(!$attributeTable->hasColumn('historicize')) {
            #attribute - add column
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute` ADD `historicize` ENUM( '1', '0' ) NOT NULL DEFAULT '1'");
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute` ADD `historicize` ENUM( '1', '0' ) NOT NULL DEFAULT '1'");

            //recreate view
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

        //attribute - on delete
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_delete`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_delete BEFORE DELETE ON attribute
                FOR EACH ROW BEGIN
                    INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, script_name, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to, historicize)
                    VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.script_name, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW(), OLD.historicize);
                END
        ");

        //attribute - on update
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_update`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_update BEFORE UPDATE ON attribute
              FOR EACH ROW BEGIN
                IF NEW.user_id IS NULL THEN
                  SET NEW.user_id = 0;
                END IF;

                INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, script_name, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to, historicize)
                VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.script_name, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW(), OLD.historicize);
                SET NEW.valid_from = NOW();
              END
        ");

        //ci_attribute - on delete
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_attribute_delete`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_attribute_delete BEFORE DELETE ON ci_attribute
              FOR EACH ROW BEGIN
                DECLARE c_historicize CHAR;
                SELECT historicize INTO c_historicize FROM attribute WHERE id = OLD.attribute_id;

                IF c_historicize != '0' THEN BEGIN
                    DECLARE c_history_id INTEGER;
                    SELECT history_id INTO c_history_id FROM temp_history WHERE (datestamp <= NOW() and datestamp > (NOW() - INTERVAL 1 MINUTE))  AND ci_id = OLD.ci_id order by datestamp desc limit 1;
                    IF c_history_id IS NULL OR c_history_id <= 0 THEN
                        SELECT create_history('0', 'ci_attribute deleted') INTO c_history_id;
                    END IF;
                    INSERT INTO " . $this->dbName . "_history.ci_attribute(id, ci_id, attribute_id, value_text, value_date, value_default, value_ci, note, is_initial, history_id, valid_from, history_id_delete, valid_to)
                    VALUES(OLD.id, OLD.ci_id, OLD.attribute_id, OLD.value_text, OLD.value_date, OLD.value_default, OLD.value_ci, OLD.note, OLD.is_initial, OLD.history_id, OLD.valid_from, c_history_id, NOW());
                END; END IF;
              END
        ");

        //ci_attribute - on update
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_attribute_update`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_attribute_update BEFORE UPDATE ON ci_attribute
              FOR EACH ROW BEGIN
                DECLARE c_historicize CHAR;
                SELECT historicize INTO c_historicize FROM attribute WHERE id = OLD.attribute_id;

                IF c_historicize != '0' THEN BEGIN

                    DECLARE changed INTEGER;
                    SET changed = 0;

                    IF (NEW.value_text IS NOT NULL AND STRCMP(NEW.value_text, OLD.value_text) <> 0) or
                    (NEW.value_date IS NOT NULL AND STRCMP(NEW.value_date, OLD.value_date) <> 0) or
                    (NEW.value_default IS NOT NULL AND STRCMP(NEW.value_default, OLD.value_default) <> 0) or
                    (NEW.value_ci IS NOT NULL AND STRCMP(NEW.value_ci, OLD.value_ci) <> 0) or
                    (NEW.note IS NOT NULL AND STRCMP(NEW.note, OLD.note) <> 0)
                        THEN SET changed = 1;
                    END IF;

                    IF changed > 0 THEN

                        IF NEW.history_id IS NULL OR NEW.history_id = 0 OR NEW.history_id = OLD.history_id THEN
                            SET NEW.history_id = create_history('0', 'ci_attribute updated ');
                        END IF;

                        INSERT INTO " . $this->dbName . "_history.ci_attribute(id, ci_id, attribute_id, value_text, value_date, value_default, value_ci, note, is_initial, history_id, valid_from, history_id_delete, valid_to)
                        VALUES(OLD.id, OLD.ci_id, OLD.attribute_id, OLD.value_text, OLD.value_date, OLD.value_default, OLD.value_ci, OLD.note, OLD.is_initial, OLD.history_id, OLD.valid_from, NEW.history_id, NOW());

                        SET NEW.valid_from = NOW();
                    END IF;
                END; END IF;
              END
        ");

        //ci_attribute - on insert
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_attribute_insert`");
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`ci_attribute_insert` BEFORE INSERT ON `ci_attribute`
             FOR EACH ROW BEGIN
                DECLARE c_historicize CHAR;
                SELECT historicize INTO c_historicize FROM attribute WHERE id = NEW.attribute_id;

                IF c_historicize != '0' THEN BEGIN
                    IF NEW.history_id IS NULL OR NEW.history_id = 0 THEN
                        SET NEW.history_id = create_history('0', 'ci_attribute created');
                    END IF;
                    SET NEW.valid_from = NOW();
                END; END IF;
             END
        ");
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        //no down-action
    }
}