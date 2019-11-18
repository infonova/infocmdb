<?php

use PhinxExtend\AbstractPhinxMigration;

class AttributeHint extends AbstractPhinxMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        // change 'hint' from varchar to text
        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute` MODIFY COLUMN `hint` TEXT(10000) DEFAULT NULL");
        $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute` MODIFY COLUMN `hint` TEXT(10000) DEFAULT NULL");

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
