<?php

use PhinxExtend\AbstractPhinxMigration;

class AttributeAddOptionSeparator extends AbstractPhinxMigration
{


    public function up()
    {
        $attribute = $this->table('attribute');

        if ( !$attribute->hasColumn('display_style')) {
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute` ADD `display_style` VARCHAR(20) NULL DEFAULT NULL COMMENT 'style of attribute'");
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute` ADD `display_style` VARCHAR(20) NULL DEFAULT NULL COMMENT 'style of attribute'");

            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute AS
                select *
                from " . $this->dbName . "_tables.attribute
                WITH CASCADED CHECK OPTION
            ");

            //attribute - on delete
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_delete`");
            $this->execute("
                CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_delete BEFORE DELETE ON attribute
                    FOR EACH ROW BEGIN
                        INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, script_name, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to, historicize, display_style)
                        VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.script_name, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW(), OLD.historicize, OLD.display_style);
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
    
                    INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, script_name, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to, historicize, display_style)
                    VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.script_name, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW(), OLD.historicize, OLD.display_style);
                    SET NEW.valid_from = NOW();
                  END
            ");
        }
    }
}
