
<?php

use PhinxExtend\AbstractPhinxMigration;


class AddRegexForFileImportToWorkflowTrigger extends AbstractPhinxMigration
{
    public function up() {
        $workflow_trigger = $this->table('workflow_trigger');

        if (!$workflow_trigger->hasColumn('fileimport_regex')) {
            $this->execute(
                "ALTER TABLE `" . $this->dbName . "_tables`.`workflow_trigger` ADD `fileimport_regex` TEXT NULL  COMMENT 'Contains the regex " .
                "which are compared with the fileimport filename to trigger pre or post fileimport triggers' AFTER `method`;"

            );
            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_trigger AS
                select *
                from " . $this->dbName . "_tables.workflow_trigger
                WITH CASCADED CHECK OPTION
            ");
            if ($workflow_trigger->hasColumn('mapping_id')) {
                $this->execute(
                    "ALTER TABLE `" . $this->dbName . "_tables`.`workflow_trigger` MODIFY COLUMN mapping_id INT(10) UNSIGNED NULL ;"
                );
            }
            if ($workflow_trigger->hasColumn('type') &&
                $workflow_trigger->hasColumn('method')) {
                $this->execute(
                    "ALTER TABLE `" . $this->dbName . "_tables`.`workflow_trigger` MODIFY COLUMN `type` ENUM('ci','relation','project','attribute','ci_type_change','fileimport') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;");
                $this->execute(
                    "ALTER TABLE `" . $this->dbName . "_tables`.`workflow_trigger` MODIFY COLUMN `method` ENUM('create','update','delete','after','before','before_and_after') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;"
                );
            }
        }

        $workflow = $this->table('workflow');

        if ( !$workflow->hasColumn('trigger_fileimport')) {
            $this->execute(
                "ALTER TABLE `" . $this->dbName . "_tables`.`workflow` ADD `trigger_fileimport` enum('0','1') DEFAULT '0' NOT NULL COMMENT 'If the workflow " .
                "triggers on file import' AFTER `trigger_time`;"

            );
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
}