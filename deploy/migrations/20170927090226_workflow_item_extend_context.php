<?php

use PhinxExtend\AbstractPhinxMigration;

class WorkflowItemExtendContext extends AbstractPhinxMigration
{

    public function up()
    {
        $this->execute("ALTER TABLE " . $this->dbName . "_tables . workflow_item MODIFY COLUMN context MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
        $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".workflow_item AS
            select *
            from " . $this->dbName . "_tables.workflow_item
            WITH CASCADED CHECK OPTION
        ");

        $this->execute("ALTER TABLE " . $this->dbName . "_tables . workflow_case MODIFY COLUMN context MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
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
