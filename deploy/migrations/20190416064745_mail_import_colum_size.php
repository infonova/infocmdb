<?php

use PhinxExtend\AbstractPhinxMigration;

class MailImportColumSize extends AbstractPhinxMigration
{
    public function up()
    {
        $this->execute("ALTER TABLE " . $this->dbName . "_tables . import_mail MODIFY COLUMN `user` varchar(255) NOT NULL");
        $this->execute("ALTER TABLE " . $this->dbName . "_tables . import_mail MODIFY COLUMN `password` varchar(255) NOT NULL");

        $this->execute("ALTER TABLE " . $this->dbName . "_history . import_mail MODIFY COLUMN `user` varchar(255) NOT NULL");
        $this->execute("ALTER TABLE " . $this->dbName . "_history . import_mail MODIFY COLUMN `password` varchar(255) NOT NULL");

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
