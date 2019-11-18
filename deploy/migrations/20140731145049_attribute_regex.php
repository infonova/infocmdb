<?php

use PhinxExtend\AbstractPhinxMigration;

class AttributeRegex extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        //workflow - add column: trigger_ci_type_change
        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute` MODIFY COLUMN `regex` VARCHAR(400) DEFAULT NULL");
        $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute` MODIFY COLUMN `regex` VARCHAR(400) DEFAULT NULL");

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

    /**
     * Migrate Down.
     */
    public function down()
    {
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
}