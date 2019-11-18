<?php

use PhinxExtend\AbstractPhinxMigration;

class WorkflowType extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up() {
        $workflowTable = $this -> table($this -> dbName . '_tables.workflow');
        //default value for new workflows is null
        if($workflowTable -> hasColumn('script_lang')===false){
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`workflow` ADD COLUMN `script_lang` VARCHAR(10) DEFAULT 'perl' AFTER `status_message`");
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`workflow` ADD COLUMN `script_lang` VARCHAR(10) DEFAULT 'perl' AFTER `status_message`");

            #workflow - on update
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_update`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `". $this->dbName ."_tables`.`workflow_update` BEFORE UPDATE ON ". $this->dbName ."_tables.workflow FOR EACH ROW
                BEGIN          
                    DECLARE changed INTEGER;
                    SET changed = 0;
                    
                    IF NEW.user_id IS NULL THEN
                      SET NEW.user_id = 0;
                    END IF;
                    
                    IF (NEW.name IS NOT NULL AND STRCMP(NEW.name, OLD.name) <> 0) or
                    (NEW.description IS NOT NULL AND STRCMP(NEW.description, OLD.description) <> 0) or
                    (NEW.note IS NOT NULL AND STRCMP(NEW.note, OLD.note) <> 0) or
                    (NEW.execute_user_id IS NOT NULL AND STRCMP(NEW.execute_user_id, OLD.execute_user_id) <> 0) or
                    (NEW.is_async IS NOT NULL AND STRCMP(NEW.is_async, OLD.is_async) <> 0) or
                    (NEW.trigger_ci IS NOT NULL AND STRCMP(NEW.trigger_ci, OLD.trigger_ci) <> 0) or
                    (NEW.trigger_ci_type_change IS NOT NULL AND STRCMP(NEW.trigger_ci_type_change, OLD.trigger_ci_type_change) <> 0) or
                    (NEW.trigger_attribute IS NOT NULL AND STRCMP(NEW.trigger_attribute, OLD.trigger_attribute) <> 0) or
                    (NEW.trigger_project IS NOT NULL AND STRCMP(NEW.trigger_project, OLD.trigger_project) <> 0) or
                    (NEW.trigger_relation IS NOT NULL AND STRCMP(NEW.trigger_relation, OLD.trigger_relation) <> 0) or
                    (NEW.trigger_time IS NOT NULL AND STRCMP(NEW.trigger_time, OLD.trigger_time) <> 0) or
                    (NEW.trigger_fileimport IS NOT NULL AND STRCMP(NEW.trigger_fileimport, OLD.trigger_fileimport) <> 0) or
                    (NEW.execution_time IS NOT NULL AND STRCMP(NEW.execution_time, OLD.execution_time) <> 0) or
                    (NEW.is_active IS NOT NULL AND STRCMP(NEW.is_active, OLD.is_active) <> 0) or
                    (NEW.response_format IS NOT NULL AND STRCMP(NEW.response_format, OLD.response_format) <> 0) or
                    (NEW.script_lang IS NOT NULL AND STRCMP(NEW.script_lang, OLD.script_lang) <> 0)
                    THEN SET changed = 1;
                    END IF;
                    
                    IF changed > 0 THEN
                        INSERT INTO ". $this->dbName ."_history.workflow(id, name, description, note, execute_user_id, is_async, trigger_ci, trigger_attribute, trigger_project, trigger_relation, trigger_time, execution_time, status, status_message, is_active, user_id, valid_from, response_format, script_lang, user_id_delete, valid_to)
                        VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.execute_user_id, OLD.is_async, OLD.trigger_ci, OLD.trigger_attribute, OLD.trigger_project, OLD.trigger_relation, OLD.trigger_time, OLD.execution_time, OLD.status, OLD.status_message, OLD.is_active, OLD.user_id, OLD.valid_from, OLD.response_format, OLD.script_lang, NEW.user_id, NOW());
                        SET NEW.valid_from = NOW();
                    END IF;
                END;
            ");

            #workflow - on delete
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_delete`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `". $this->dbName ."_tables`.`workflow_delete` BEFORE DELETE ON ". $this->dbName ."_tables.workflow FOR EACH ROW
                BEGIN
                    INSERT INTO ". $this->dbName ."_history.workflow(id, name, description, note, execute_user_id, is_async, trigger_ci, trigger_attribute, trigger_project, trigger_relation, trigger_time, execution_time, status, status_message, is_active, user_id, valid_from, response_format, script_lang, user_id_delete, valid_to)
                    VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.execute_user_id, OLD.is_async, OLD.trigger_ci, OLD.trigger_attribute, OLD.trigger_project, OLD.trigger_relation, OLD.trigger_time, OLD.execution_time, OLD.status, OLD.status_message, OLD.is_active, OLD.user_id, OLD.valid_from, OLD.response_format, OLD.script_lang, '0', NOW());
                END;
            ");
        }

        //workflow - recreate view
        $this->execute("
			CREATE OR REPLACE ALGORITHM=MERGE
			DEFINER=`root`@`localhost`
			SQL SECURITY DEFINER

			VIEW " . $this->dbName . ".workflow AS
			select *
			from " . $this->dbName . "_tables.workflow
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
