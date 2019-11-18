<?php

use PhinxExtend\AbstractPhinxMigration;


class AddUserSettingsToUserTable extends AbstractPhinxMigration
{
    public function up()
    {
        $user = $this->table('user');

        if ( !$user->hasColumn('settings')) {
            $this->execute(
                "ALTER TABLE " . $this->dbName . "_tables.`user` ADD settings TEXT NULL COMMENT 'json containing user settings' ;"
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
