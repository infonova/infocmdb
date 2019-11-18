<?php

use PhinxExtend\AbstractPhinxMigration;

class WorkflowSolveStatus extends AbstractPhinxMigration
{
    public function up()
    {
        $table = $this->table('workflow_case');

        if (!$table->hasColumn('solve_status')) {
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`workflow_case` ADD COLUMN `solve_status` TINYINT(1) DEFAULT 0 NOT NULL AFTER `status`");
            $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".workflow_case AS
            select *
            from " . $this->dbName . "_tables.workflow_case
            WITH CASCADED CHECK OPTION
        ");
        }
    }
}
