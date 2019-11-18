<?php

use PhinxExtend\AbstractPhinxMigration;

class UpdateUserPassword extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`user` CHANGE COLUMN `password` `password` VARCHAR(110) NOT NULL DEFAULT '' COMMENT 'password for login'");
        $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`user` CHANGE COLUMN `password` `password` VARCHAR(110) NOT NULL DEFAULT '' COMMENT 'password for login'");
        $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".user AS
            select *
            from " . $this->dbName . "_tables.user
            WITH CASCADED CHECK OPTION
        ");
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        //no down-action, change does not affect application
    }
}