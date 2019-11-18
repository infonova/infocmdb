<?php

use PhinxExtend\AbstractPhinxMigration;

class ReportingCreateColumnMailContent extends AbstractPhinxMigration
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
	$reportingTable = $this->table('reporting');

	if(!$reportingTable->hasColumn('mail_content')) {
                echo "adding mail_content to reporting\n";
		$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`reporting` ADD COLUMN `mail_content` TEXT(10000) DEFAULT NULL");
	}
        
        echo "recreating view for reporting\n";
        $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".reporting AS
            select *
            from " . $this->dbName . "_tables.reporting
            WITH CASCADED CHECK OPTION
        ");
    }
}
