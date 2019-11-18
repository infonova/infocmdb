<?php
/**
 * Created by PhpStorm.
 * User: martina.reiter
 * Date: 15.09.2015
 * Time: 14:37
 */
use PhinxExtend\AbstractPhinxMigration;

class WorkflowAddResponseFormat extends AbstractPhinxMigration{

    /**
     * Migrate Up.
     */

    public function up() {
        $workflowTable = $this -> table($this -> dbName . '_tables.workflow');
        //default value for new workflows is json
        if($workflowTable -> hasColumn('response_format')===false){
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`workflow` ADD COLUMN `response_format` VARCHAR(15) NOT NULL DEFAULT 'json'");
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`workflow` ADD COLUMN `response_format` VARCHAR(15) NOT NULL DEFAULT 'json'");

            //default value for existing workflows is plain
            $this->execute("UPDATE `" . $this->dbName . "_tables`.`workflow` set response_format = 'plain'");

            #workflow - on update
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_update`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `". $this->dbName ."_tables`.`workflow_update` BEFORE UPDATE ON ". $this->dbName ."_tables.workflow FOR EACH ROW
                BEGIN
                IF NEW.user_id IS NULL THEN
                SET NEW.user_id = 0;
                END IF;

                INSERT INTO ". $this->dbName ."_history.workflow(id, name, description, note, execute_user_id, is_async, trigger_ci, trigger_attribute, trigger_project, trigger_relation, trigger_time, execution_time, is_active, user_id, valid_from, response_format, user_id_delete, valid_to)
                VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.execute_user_id, OLD.is_async, OLD.trigger_ci, OLD.trigger_attribute, OLD.trigger_project, OLD.trigger_relation, OLD.trigger_time, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, OLD.response_format, NEW.user_id, NOW());

                SET NEW.valid_from = NOW();
                END;
            ");

            #workflow - on delete
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`workflow_delete`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `". $this->dbName ."_tables`.`workflow_delete` BEFORE DELETE ON ". $this->dbName ."_tables.workflow FOR EACH ROW
                BEGIN
                INSERT INTO ". $this->dbName ."_history.workflow(id, name, description, note, execute_user_id, is_async, trigger_ci, trigger_attribute, trigger_project, trigger_relation, trigger_time, execution_time, is_active, user_id, valid_from, response_format, user_id_delete, valid_to)
                VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.execute_user_id, OLD.is_async, OLD.trigger_ci, OLD.trigger_attribute, OLD.trigger_project, OLD.trigger_relation, OLD.trigger_time, OLD.execution_time, OLD.is_active, OLD.user_id, OLD.valid_from, OLD.response_format, '0', NOW());
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