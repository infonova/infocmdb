<?php

use PhinxExtend\AbstractPhinxMigration;

class ApiV2 extends AbstractPhinxMigration
{
    public function up()
    {
        $this->down();
        $this->execute("ALTER TABLE `" . $this->dbName . "`.`api_session` MODIFY COLUMN `apikey` varchar(60) NOT NULL");
        $this->execute("ALTER TABLE `" . $this->dbName . "`.`api_session` ADD COLUMN `api_version` tinyint(1) NOT NULL DEFAULT 1");
        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`user` ADD COLUMN `api_secret` varchar(10) NULL AFTER `secret`");

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

    public function down()
    {
        $apiSession = $this->table('api_session');
        $user       = $this->table('user');

        if ($apiSession->hasColumn('api_version')) {
            $this->execute("ALTER TABLE `" . $this->dbName . "`.`api_session` DROP COLUMN `api_version`");
        }


        if ($user->hasColumn('api_secret')) {
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`user` DROP COLUMN `api_secret`");
        }

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
}
