<?php

use PhinxExtend\AbstractPhinxMigration;

class MailImportInboxFolder extends AbstractPhinxMigration
{
    public function up()
    {
        $this->down();

        $this->execute("ALTER TABLE " . $this->dbName . "_tables . import_mail ADD COLUMN `inbox_folder` varchar(25) NULL AFTER `port`");
        $this->execute("ALTER TABLE " . $this->dbName . "_history . import_mail ADD COLUMN `inbox_folder` varchar(25) NULL AFTER `port`");

        $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".import_mail AS
                select *
                from " . $this->dbName . "_tables.import_mail
                WITH CASCADED CHECK OPTION
        ");
    }

    public function down()
    {
        $table = $this->table('import_mail');

        if ($table->hasColumn('inbox_folder')) {
            $this->execute("ALTER TABLE " . $this->dbName . "_tables . import_mail DROP COLUMN `inbox_folder`");
            $this->execute("ALTER TABLE " . $this->dbName . "_history . import_mail DROP COLUMN `inbox_folder`");
        }

        $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".import_mail AS
                select *
                from " . $this->dbName . "_tables.import_mail
                WITH CASCADED CHECK OPTION
        ");
    }
}
