<?php

use PhinxExtend\AbstractPhinxMigration;

class MultiselectCountable extends AbstractPhinxMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute` MODIFY COLUMN is_multiselect enum('0','1','2') DEFAULT '0' NOT NULL COMMENT '0:dropdown,1:multiselect,2:multiselect with counter'");
        $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute` MODIFY COLUMN is_multiselect enum('0','1','2') DEFAULT '0' NOT NULL COMMENT '0:dropdown,1:multiselect,2:multiselect with counter'");

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
