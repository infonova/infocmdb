<?php

use PhinxExtend\AbstractPhinxMigration;

class IdxAttributeName extends AbstractPhinxMigration
{
    
    /**
     * Migrate Up.
     */
    public function up()
    {
		$table = $this->table($this->dbName . '_tables.attribute');
		if($table->hasIndex('name') === false)
		{
			$this->execute("CREATE UNIQUE INDEX `idx_attribute_name`  
							ON `" . $this->dbName . "_tables`.`attribute` (name) 
							COMMENT ''");
		}
    }

    /**
     * Migrate Down.
     */
    public function down()
    {

    }
}