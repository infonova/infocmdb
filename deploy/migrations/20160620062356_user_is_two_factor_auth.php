<?php

use PhinxExtend\AbstractPhinxMigration;

class UserIsTwoFactorAuth extends AbstractPhinxMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     */
    public function up()
    {
        $user = $this->table('user');
        $change = false;
        
        if ( !$user->hasColumn('is_two_factor_auth')) {
            echo "adding is_two_factor_auth to user\n";
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`user` ADD `is_two_factor_auth` BOOLEAN NOT NULL DEFAULT false COMMENT 'flag if user has two factor auth activated'");
            $change = true;
        }
        if ( !$user->hasColumn('secret')) {
            echo "adding secret to user\n";
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`user` ADD `secret` VARCHAR(100) COMMENT 'secret for 2 factor auth'");
            $change = true;
        }
        
        if( $change ) {
            echo "recreating view for table user\n";
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
