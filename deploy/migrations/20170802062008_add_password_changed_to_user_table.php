<?php

use PhinxExtend\AbstractPhinxMigration;


class AddPasswordChangedToUserTable extends AbstractPhinxMigration
{
    public function up()
    {
        $user_table = $this->table('user');

        if (!$user_table->hasColumn('password_changed')) {
            $this->execute(
                "ALTER TABLE `" . $this->dbName . "_tables`.`user` ADD " .
                "`password_changed` DATETIME NULL COMMENT 'Date when the user has changed his password.' AFTER `secret`;"
            );
            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".user AS
                select *
                from " . $this->dbName . "_tables.user
                WITH CASCADED CHECK OPTION
            ");
            $this->execute("UPDATE `" . $this->dbName . "_tables`.`user` SET password_changed = NOW();");
        }

        if (!$user_table->hasColumn('password_expire_off')) {
            $this->execute(
                "ALTER TABLE `" . $this->dbName . "_tables`.`user` ADD " .
                "`password_expire_off` TINYINT(1) DEFAULT 0 NULL COMMENT 'Turns off password expiration for this user. API users f.e.' AFTER `password_changed`;"
            );
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
}
