<?php

use PhinxExtend\AbstractPhinxMigration;

class AddTriggers extends AbstractPhinxMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $triggerCheck = $this->fetchRow("
            SELECT trigger_schema, trigger_name
            FROM information_schema.triggers
            WHERE TRIGGER_SCHEMA = '" . $this->dbName . "_tables'
            AND TRIGGER_NAME = 'attribute_insert'
        ");

        //only handle triggers if migration version matches
        if ($this->getCurrentMigrateVersion() == 2 && $triggerCheck === false) {

            //first drop all triggers
            $this->down();

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_history`.`history_insert` BEFORE INSERT ON `history`
            FOR EACH ROW SET NEW.datestamp = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`attribute_default_citype_attributes_insert` BEFORE INSERT ON `attribute_default_citype_attributes`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER " . $this->dbName . "_history.attribute_default_citype_attributes_insert BEFORE INSERT ON " . $this->dbName . "_history.attribute_default_citype_attributes
            FOR EACH ROW SET NEW.valid_to = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_citype_attributes_update BEFORE UPDATE ON attribute_default_citype_attributes
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_citype_attributes (id, attribute_default_citype_id, attribute_id, `condition`, order_number, valid_from, valid_to)
            VALUES(OLD.id, OLD.attribute_default_citype_id, OLD.attribute_id, OLD.condition, OLD.order_number, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_citype_attributes_delete BEFORE DELETE ON attribute_default_citype_attributes
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_citype_attributes (id, attribute_default_citype_id, attribute_id, `condition`, order_number, valid_from, valid_to)
            VALUES(OLD.id, OLD.attribute_default_citype_id, OLD.attribute_id, OLD.condition, OLD.order_number, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`attribute_default_citype_insert` BEFORE INSERT ON `attribute_default_citype`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER " . $this->dbName . "_history.attribute_default_citype_insert BEFORE INSERT ON " . $this->dbName . "_history.attribute_default_citype
            FOR EACH ROW SET NEW.valid_to = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_citype_update BEFORE UPDATE ON attribute_default_citype
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_citype (id, attribute_id, ci_type_id, join_attribute_id_from, join_attribute_id_to, join_order, valid_from, valid_to)
            VALUES(OLD.id, OLD.attribute_id, OLD.ci_type_id, OLD.join_attribute_id_from, OLD.join_attribute_id_to, OLD.join_order, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_citype_delete BEFORE DELETE ON attribute_default_citype
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_citype (id, attribute_id, ci_type_id, join_attribute_id_from, join_attribute_id_to, join_order, valid_from, valid_to)
            VALUES(OLD.id, OLD.attribute_id, OLD.ci_type_id, OLD.join_attribute_id_from, OLD.join_attribute_id_to, OLD.join_order, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`attribute_default_queries_parameter_insert` BEFORE INSERT ON `attribute_default_queries_parameter`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER " . $this->dbName . "_history.attribute_default_queries_parameter_insert BEFORE INSERT ON " . $this->dbName . "_history.attribute_default_queries_parameter
            FOR EACH ROW SET NEW.valid_to = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_queries_parameter_update BEFORE UPDATE ON attribute_default_queries_parameter
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_queries_parameter (id, queries_id, parameter, order_number, valid_from, valid_to)
            VALUES(OLD.id, OLD.queries_id, OLD.parameter, OLD.order_number, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_queries_parameter_delete BEFORE DELETE ON attribute_default_queries_parameter
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_queries_parameter (id, queries_id, parameter, order_number, valid_from, valid_to)
            VALUES(OLD.id, OLD.queries_id, OLD.parameter, OLD.order_number, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`attribute_default_queries_insert` BEFORE INSERT ON `attribute_default_queries`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER " . $this->dbName . "_history.attribute_default_queries_insert BEFORE INSERT ON " . $this->dbName . "_history.attribute_default_queries
            FOR EACH ROW SET NEW.valid_to = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_queries_update BEFORE UPDATE ON attribute_default_queries
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_queries (id, attribute_id, query, valid_from, valid_to)
            VALUES (OLD.id, OLD.attribute_id, OLD.query, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_queries_delete BEFORE DELETE ON attribute_default_queries
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_queries (id, attribute_id, query, valid_from, valid_to)
            VALUES (OLD.id, OLD.attribute_id, OLD.query, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`attribute_default_values_insert` BEFORE INSERT ON `attribute_default_values`
            FOR EACH ROW SET NEW.valid_from = NOW()

            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER " . $this->dbName . "_history.attribute_default_values_insert BEFORE INSERT ON " . $this->dbName . "_history.attribute_default_values
            FOR EACH ROW SET NEW.valid_to = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_values_update BEFORE UPDATE ON attribute_default_values
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_values (id, attribute_id, value, order_number, is_active, valid_from, valid_to)
            VALUES (OLD.id, OLD.attribute_id, OLD.value, OLD.order_number, OLD.is_active, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_default_values_delete BEFORE DELETE ON attribute_default_values
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_default_values (id, attribute_id, value, order_number, is_active, valid_from, valid_to)
            VALUES (OLD.id, OLD.attribute_id, OLD.value, OLD.order_number, OLD.is_active, OLD.valid_from, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`attribute_group_insert` BEFORE INSERT ON `attribute_group`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_group_update BEFORE UPDATE ON attribute_group
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.attribute_group(id, name, description, note, order_number, parent_attribute_group_id, is_duplicate_allow, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.order_number, OLD.parent_attribute_group_id, OLD.is_duplicate_allow , OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_group_delete BEFORE DELETE ON attribute_group
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute_group(id, name, description, note, order_number, parent_attribute_group_id, is_duplicate_allow, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.order_number, OLD.parent_attribute_group_id, OLD.is_duplicate_allow , OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`attribute_insert` BEFORE INSERT ON `attribute`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_update BEFORE UPDATE ON attribute
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, script_name, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to, historicize)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.script_name, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW(), OLD.historicize);

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_delete BEFORE DELETE ON attribute
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, script_name, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to, historicize)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.script_name, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW(), OLD.historicize);
            END;
            ");

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
            END;
            ");

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
            END;
            ");

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
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`ci_event_insert` BEFORE INSERT ON `ci_event`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER " . $this->dbName . "_history.ci_event_insert BEFORE INSERT ON " . $this->dbName . "_history.ci_event
            FOR EACH ROW SET NEW.valid_to = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_event_update BEFORE UPDATE ON ci_event
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.ci_event Select OLD;
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_event_delete BEFORE DELETE ON ci_event
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.ci_event Select OLD;
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`ci_project_insert` BEFORE INSERT ON `ci_project`
            FOR EACH ROW BEGIN
            IF NEW.history_id IS NULL OR NEW.history_id = 0 THEN
            SET NEW.history_id = create_history('0', 'ci_project created');
            END IF;
            SET NEW.valid_from = NOW();

            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_project_update BEFORE UPDATE ON ci_project
            FOR EACH ROW BEGIN
            IF NEW.history_id IS NULL OR NEW.history_id = 0 OR NEW.history_id = OLD.history_id THEN
            SET NEW.history_id = create_history('0', 'ci_project updated');
            END IF;

            INSERT INTO " . $this->dbName . "_history.ci_project(id, ci_id, project_id, history_id, valid_from, history_id_delete, valid_to)
            VALUES(OLD.id, OLD.ci_id, OLD.project_id, OLD.history_id, OLD.valid_from, NEW.history_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_project_delete BEFORE DELETE ON ci_project
            FOR EACH ROW BEGIN
            DECLARE c_history_id INTEGER;

            SELECT history_id INTO c_history_id FROM temp_history WHERE (datestamp <= NOW() and datestamp > (NOW() - INTERVAL 1 MINUTE))  AND ci_id = OLD.ci_id order by datestamp desc  limit 1;

            if c_history_id IS NULL OR c_history_id <= 0 THEN
            select create_history('0', 'ci_project deleted') into c_history_id;
            END IF;

            INSERT INTO " . $this->dbName . "_history.ci_project(id, ci_id, project_id, history_id, valid_from, history_id_delete, valid_to)
            VALUES(OLD.id, OLD.ci_id, OLD.project_id, OLD.history_id, OLD.valid_from, c_history_id, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`ci_relation_type_insert` BEFORE INSERT ON `ci_relation_type`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_relation_type_update BEFORE UPDATE ON ci_relation_type
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.ci_relation_type(id, name, description, description_optional, note, color, visualize, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.description_optional, OLD.note, OLD.color, OLD.visualize, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_relation_type_delete BEFORE DELETE ON ci_relation_type
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.ci_relation_type(id, name, description, description_optional, note, color, visualize, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.description_optional, OLD.note, OLD.color, OLD.visualize, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`ci_relation_insert` BEFORE INSERT ON `ci_relation`
            FOR EACH ROW BEGIN
            IF NEW.history_id IS NULL OR NEW.history_id = 0 THEN
            SET NEW.history_id = create_history('0', 'ci_relation created');
            END IF;
            SET NEW.valid_from = NOW();

            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_relation_update BEFORE UPDATE ON ci_relation
            FOR EACH ROW BEGIN
            IF NEW.history_id IS NULL OR NEW.history_id = 0 OR NEW.history_id = OLD.history_id THEN
            SET NEW.history_id = create_history('0', 'ci_relation updated');
            END IF;

            INSERT INTO " . $this->dbName . "_history.ci_relation(id, ci_relation_type_id, ci_id_1, ci_id_2, attribute_id_1, attribute_id_2, direction, weighting, color, note, history_id, valid_from, history_id_delete, valid_to)
            VALUES(OLD.id, OLD.ci_relation_type_id, OLD.ci_id_1, OLD.ci_id_2, OLD.attribute_id_1, OLD.attribute_id_2, OLD.direction, OLD.weighting, OLD.color, OLD.note, OLD.history_id, OLD.valid_from, NEW.history_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_relation_delete BEFORE DELETE ON ci_relation
            FOR EACH ROW BEGIN
            DECLARE c_history_id INTEGER;

            SELECT history_id INTO c_history_id FROM temp_history WHERE (datestamp <= NOW() and datestamp > (NOW() - INTERVAL 1 MINUTE))  AND (ci_id = OLD.ci_id_1 or ci_id = OLD.ci_id_2) order by datestamp desc  limit 1;

            if c_history_id IS NULL OR c_history_id <= 0 THEN
            select create_history('0', 'ci_relation deleted') into c_history_id;
            END IF;

            INSERT INTO " . $this->dbName . "_history.ci_relation(id, ci_relation_type_id, ci_id_1, ci_id_2, attribute_id_1, attribute_id_2, direction, weighting, color, note, history_id, valid_from, history_id_delete, valid_to)
            VALUES(OLD.id, OLD.ci_relation_type_id, OLD.ci_id_1, OLD.ci_id_2, OLD.attribute_id_1, OLD.attribute_id_2, OLD.direction, OLD.weighting, OLD.color, OLD.note, OLD.history_id, OLD.valid_from, c_history_id, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`ci_ticket_insert` BEFORE INSERT ON `ci_ticket`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER " . $this->dbName . "_history.ci_ticket_insert BEFORE INSERT ON " . $this->dbName . "_history.ci_ticket
            FOR EACH ROW SET NEW.valid_to = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_ticket_update BEFORE UPDATE ON ci_ticket
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.ci_ticket Select OLD;
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_ticket_delete BEFORE DELETE ON ci_ticket
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.ci_ticket Select OLD;
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`ci_type_insert` BEFORE INSERT ON `ci_type`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_type_update BEFORE UPDATE ON ci_type
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.ci_type(id, name, description, note, parent_ci_type_id, order_number, create_button_description, icon, query, default_project_id, default_attribute_id, default_sort_attribute_id, is_default_sort_asc, is_ci_attach, is_attribute_attach, tag, is_tab_enabled, is_event_enabled, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.parent_ci_type_id, OLD.order_number, OLD.create_button_description, OLD.icon, OLD.query, OLD.default_project_id, OLD.default_attribute_id, OLD.default_sort_attribute_id, OLD.is_default_sort_asc, OLD.is_ci_attach, OLD.is_attribute_attach, OLD.tag, OLD.is_tab_enabled, OLD.is_event_enabled, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_type_delete BEFORE DELETE ON ci_type
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.ci_type(id, name, description, note, parent_ci_type_id, order_number, create_button_description, icon, query, default_project_id, default_attribute_id, default_sort_attribute_id, is_default_sort_asc, is_ci_attach, is_attribute_attach, tag, is_tab_enabled, is_event_enabled, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.parent_ci_type_id, OLD.order_number, OLD.create_button_description, OLD.icon, OLD.query, OLD.default_project_id, OLD.default_attribute_id, OLD.default_sort_attribute_id, OLD.is_default_sort_asc, OLD.is_ci_attach, OLD.is_attribute_attach, OLD.tag, OLD.is_tab_enabled, OLD.is_event_enabled, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`ci_insert` BEFORE INSERT ON `ci`
            FOR EACH ROW BEGIN
            IF NEW.history_id IS NULL OR NEW.history_id = 0 THEN
            SET NEW.history_id = create_history('0', 'ci created');
            END IF;
            SET NEW.valid_from = NOW();

            END;
            ");

            $this->execute("
            CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_update` BEFORE UPDATE ON " . $this->dbName . "_tables.ci FOR EACH ROW
            BEGIN
            DECLARE changed INTEGER;
            SET changed = 0;

            IF (NEW.ci_type_id IS NOT NULL AND STRCMP(NEW.ci_type_id, OLD.ci_type_id) <> 0) OR
            (NEW.icon IS NOT NULL AND STRCMP(NEW.icon, OLD.icon) <> 0)
            THEN SET changed = 1;
            END IF;

            IF changed > 0 THEN

            IF NEW.history_id IS NULL OR NEW.history_id = 0 OR NEW.history_id = OLD.history_id THEN
            SET NEW.history_id = create_history('0', 'ci updated');
            END IF;

            INSERT INTO " . $this->dbName . "_history.ci(id, ci_type_id, icon, history_id, valid_from, history_id_delete, valid_to)
            VALUES(OLD.id, OLD.ci_type_id, OLD.icon, OLD.history_id, OLD.valid_from, NEW.history_id, NOW());

            SET NEW.valid_from = NOW();

            END IF;
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.ci_delete BEFORE DELETE ON ci
            FOR EACH ROW BEGIN
            DECLARE c_history_id INTEGER;

            SELECT history_id INTO c_history_id FROM temp_history WHERE (datestamp <= NOW() and datestamp > (NOW() - INTERVAL 1 MINUTE))  AND ci_id = OLD.id order by datestamp desc  limit 1;

            if c_history_id IS NULL OR c_history_id <= 0 THEN
            select create_history('0', 'ci deleted') into c_history_id;
            END IF;

            INSERT INTO " . $this->dbName . "_history.ci(id, ci_type_id, icon, history_id, valid_from, history_id_delete, valid_to)
            VALUES(OLD.id, OLD.ci_type_id, OLD.icon, OLD.history_id, OLD.valid_from, c_history_id, NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`import_mail_insert` BEFORE INSERT ON `import_mail`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.import_mail_update BEFORE UPDATE ON import_mail
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.import_mail(host, user, password, ci_field, `ssl`, is_extended, ci_type_id, is_attach_body, body_attribute_id, attachment_attribute_id, is_ci_mail_enabled, note, execution_time, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.host, OLD.user, OLD.password, OLD.ci_field, OLD.ssl, OLD.is_extended, OLD.ci_type_id, OLD.is_attach_body, OLD.body_attribute_id, OLD.attachment_attribute_id, OLD.is_ci_mail_enabled, OLD.note, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.import_mail_delete BEFORE DELETE ON import_mail
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.import_mail(host, user, password, ci_field, `ssl`, is_extended, ci_type_id, is_attach_body, body_attribute_id, attachment_attribute_id, is_ci_mail_enabled, note, execution_time, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.host, OLD.user, OLD.password, OLD.ci_field, OLD.ssl, OLD.is_extended, OLD.ci_type_id, OLD.is_attach_body, OLD.body_attribute_id, OLD.attachment_attribute_id, OLD.is_ci_mail_enabled, OLD.note, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`mail_insert` BEFORE INSERT ON `mail`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.mail_update BEFORE UPDATE ON mail
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.mail(name, description, note, subject, body, template, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.name, OLD.description, OLD.note, OLD.subject, OLD.body, OLD.template, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.mail_delete BEFORE DELETE ON mail
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.mail(name, description, note, subject, body, template, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.name, OLD.description, OLD.note, OLD.subject, OLD.body, OLD.template, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`project_insert` BEFORE INSERT ON `project`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.project_update BEFORE UPDATE ON project
            FOR EACH ROW BEGIN

            SET NEW.user_id = 0;


            INSERT INTO " . $this->dbName . "_history.project(id, name, description, note, order_number, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.order_number, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.project_delete BEFORE DELETE ON project
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.project(id, name, description, note, order_number, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.order_number, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`queue_message_insert` BEFORE INSERT ON `queue_message`
            FOR EACH ROW SET NEW.creation_time = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`reporting_insert` BEFORE INSERT ON `reporting`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.reporting_update BEFORE UPDATE ON reporting
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.reporting(id, name, description, `trigger`, input, output, transport, note, statement, script, execution_time, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.trigger, OLD.input, OLD.output, OLD.transport, OLD.note, OLD.statement, OLD.script, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.reporting_delete BEFORE DELETE ON reporting
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.reporting(id, name, description, `trigger`, input, output, transport, note, statement, script, execution_time, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.trigger, OLD.input, OLD.output, OLD.transport, OLD.note, OLD.statement, OLD.script, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`role_insert` BEFORE INSERT ON `role`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.role_update BEFORE UPDATE ON role
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.role(id, name, description, note, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.role_delete BEFORE DELETE ON role
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.role(id, name, description, note, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());  END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`stored_query_insert` BEFORE INSERT ON `stored_query`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.stored_query_update BEFORE UPDATE ON stored_query
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.stored_query(id, name, note, query, status, status_message, is_default, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.note, OLD.query, OLD.status, OLD.status_message, OLD.is_default, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.stored_query_delete BEFORE DELETE ON stored_query
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.stored_query(id, name, note, query, status, status_message, is_default, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.note, OLD.query, OLD.status, OLD.status_message, OLD.is_default, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE  DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`temp_history_insert` BEFORE INSERT ON `temp_history`
            FOR EACH ROW SET NEW.datestamp = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`templates_insert` BEFORE INSERT ON `templates`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.templates_update BEFORE UPDATE ON templates
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.templates(id, name, description, note, file,  user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.file, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.templates_delete BEFORE DELETE ON templates
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.templates(id, name, description, note, file,  user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.file, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`theme_insert` BEFORE INSERT ON `theme`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.theme_update BEFORE UPDATE ON theme
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.theme(id, name, description, note, menu_id, is_wildcard_enabled, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.menu_id, OLD.is_wildcard_enabled, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.theme_delete BEFORE DELETE ON theme
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.theme(id, name, description, note, menu_id, is_wildcard_enabled, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.menu_id, OLD.is_wildcard_enabled, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;

            CREATE  DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`user_history_action_insert` BEFORE INSERT ON `user_history_action`
            FOR EACH ROW SET NEW.access = NOW()

            CREATE  DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`user_history_insert` BEFORE INSERT ON `user_history`
            FOR EACH ROW SET NEW.access = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`user_insert` BEFORE INSERT ON `user`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.user_update BEFORE UPDATE ON user
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.user(id, username, password, email, firstname, lastname, description, note, theme_id, language, layout, is_root, is_ci_delete_enabled, is_relation_edit_enabled, is_ldap_auth, last_access, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.username, OLD.password, OLD.email, OLD.firstname, OLD.lastname, OLD.description, OLD.note, OLD.theme_id, OLD.language, OLD.layout, OLD.is_root, OLD.is_ci_delete_enabled, OLD.is_relation_edit_enabled, OLD.is_ldap_auth, OLD.last_access, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.user_delete BEFORE DELETE ON user
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.user(id, username, password, email, firstname, lastname, description, note, theme_id, language, layout, is_root, is_ci_delete_enabled, is_relation_edit_enabled, is_ldap_auth, last_access, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.username, OLD.password, OLD.email, OLD.firstname, OLD.lastname, OLD.description, OLD.note, OLD.theme_id, OLD.language, OLD.layout, OLD.is_root, OLD.is_ci_delete_enabled, OLD.is_relation_edit_enabled, OLD.is_ldap_auth, OLD.last_access, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`workflow_insert` BEFORE INSERT ON `workflow`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.workflow_update BEFORE UPDATE ON workflow
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.workflow(id, name, description, note, execute_user_id, is_async, trigger_ci, trigger_attribute, trigger_project, trigger_relation, trigger_time, execution_time, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.execute_user_id, OLD.is_async, OLD.trigger_ci, OLD.trigger_attribute, OLD.trigger_project, OLD.trigger_relation, OLD.trigger_time, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.workflow_delete BEFORE DELETE ON workflow
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.workflow(id, name, description, note, execute_user_id, is_async, trigger_ci, trigger_attribute, trigger_project, trigger_relation, trigger_time, execution_time, is_active, user_id, valid_from, user_id_delete, valid_to)
            VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.execute_user_id, OLD.is_async, OLD.trigger_ci, OLD.trigger_attribute, OLD.trigger_project, OLD.trigger_relation, OLD.trigger_time, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW());
            END;
            ");

            $this->execute("
            CREATE  DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`workflow_case_insert` BEFORE INSERT ON `workflow_case`
            FOR EACH ROW SET NEW.created = NOW()
            ");

            $this->execute("
            CREATE  DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`workflow_item_insert` BEFORE INSERT ON `workflow_item`
            FOR EACH ROW SET NEW.created = NOW()
            ");

            $this->execute("
            CREATE  DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`workflow_log_insert` BEFORE INSERT ON `workflow_log`
            FOR EACH ROW SET NEW.created = NOW()
            ");

            $this->execute("
            CREATE  DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`workflow_token_insert` BEFORE INSERT ON `workflow_token`
            FOR EACH ROW SET NEW.created = NOW()
            ");

            $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`workflow_token_update` BEFORE UPDATE ON `workflow_token`
            FOR EACH ROW SET NEW.finished = NOW()
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
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_history`.`history_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_citype_attributes_insert`");
        $this->execute("DROP TRIGGER IF EXISTS  " . $this->dbName . "_history.attribute_default_citype_attributes_insert");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_citype_attributes_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_citype_attributes_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_citype_insert`");
        $this->execute("DROP TRIGGER IF EXISTS  " . $this->dbName . "_history.attribute_default_citype_insert");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_citype_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_citype_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_queries_parameter_insert`");
        $this->execute("DROP TRIGGER IF EXISTS  " . $this->dbName . "_history.attribute_default_queries_parameter_insert");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_queries_parameter_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_queries_parameter_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_queries_insert`");
        $this->execute("DROP TRIGGER IF EXISTS  " . $this->dbName . "_history.attribute_default_queries_insert");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_queries_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_queries_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_values_insert`");
        $this->execute("DROP TRIGGER IF EXISTS  " . $this->dbName . "_history.attribute_default_values_insert");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_values_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_default_values_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_group_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_group_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_group_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_attribute_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_attribute_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_attribute_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_event_insert`");
        $this->execute("DROP TRIGGER IF EXISTS  " . $this->dbName . "_history.ci_event_insert");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_event_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_event_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_project_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_project_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_project_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_relation_type_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_relation_type_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_relation_type_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_relation_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_relation_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_relation_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_ticket_insert`");
        $this->execute("DROP TRIGGER IF EXISTS  " . $this->dbName . "_history.ci_ticket_insert");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_ticket_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_ticket_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_type_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_type_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_type_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`import_mail_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`import_mail_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`import_mail_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`mail_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`mail_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`mail_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`project_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`project_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`project_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`queue_message_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`reporting_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`reporting_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`reporting_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`role_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`role_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`role_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`stored_query_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`stored_query_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`stored_query_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`temp_history_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`templates_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`templates_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`templates_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`theme_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`theme_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`theme_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`user_history_action_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`user_history_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`user_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`user_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`user_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_delete`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_case_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_item_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_log_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_token_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_token_update`");
    }
}