<?php

use PhinxExtend\AbstractPhinxMigration;

class TempHistoryInsertTrigger extends AbstractPhinxMigration
{
    public function up()
    {
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`temp_history_insert`");

        $this->execute("
        CREATE  DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`temp_history_insert` BEFORE INSERT ON `temp_history`
        FOR EACH ROW SET NEW.datestamp = NOW()
        ");
    }
}
