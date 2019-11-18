<?php

use PhinxExtend\AbstractPhinxMigration;

class WorkflowCiTypeTrigger extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $workflowTable = $this->table($this->dbName . '_tables.workflow');
        if($workflowTable->hasColumn('trigger_ci_type_change') === false) {

            //workflow - add column: trigger_ci_type_change
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`workflow` ADD COLUMN `trigger_ci_type_change` ENUM('0','1') NOT NULL AFTER `trigger_ci`");
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`workflow` ADD COLUMN `trigger_ci_type_change` ENUM('0','1') NOT NULL AFTER `trigger_ci`");

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
        //workflow_trigger - change type enum
        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`workflow_trigger` MODIFY COLUMN `type` ENUM('ci','relation','project','attribute','ci_type_change') NOT NULL;");

        //workflow_trigger - recreate view
            $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".workflow_trigger AS
            select *
            from " . $this->dbName . "_tables.workflow_trigger
            WITH CASCADED CHECK OPTION
         ");
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $workflowTable = $this->table($this->dbName . '_tables.workflow');
        if($workflowTable->hasColumn('trigger_ci_type_change')) {

            //workflow - add column: trigger_ci_type_change
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`workflow` DROP `trigger_ci_type_change`");
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`workflow` DROP `trigger_ci_type_change`");

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

        //workflow_trigger - change type enum
        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`workflow_trigger` MODIFY COLUMN `type` ENUM('ci','relation','project','attribute') NOT NULL;");

        //workflow_trigger - recreate view
        $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".workflow_trigger AS
            select *
            from " . $this->dbName . "_tables.workflow_trigger
            WITH CASCADED CHECK OPTION
         ");

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
}