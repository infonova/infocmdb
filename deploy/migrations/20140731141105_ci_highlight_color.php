<?php

use PhinxExtend\AbstractPhinxMigration;

class CiHighlightColor extends AbstractPhinxMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        //change column color from enum to varchar
        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci_highlight` CHANGE `color` `color` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''");

        //recreate view
        $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".ci_highlight AS
            select *
            from " . $this->dbName . "_tables.ci_highlight
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