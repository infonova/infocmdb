<?php

use PhinxExtend\AbstractPhinxMigration;

class CiAttributeIdxValueText extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
		$table = $this->table($this->dbName . '_tables.ci_attribute');
		if($table->hasIndex('value_ci') === false)
		{
			$this->execute("CREATE INDEX `idx_value_ci`  ON `" . $this->dbName . "_tables`.`ci_attribute` (value_ci(7))");
		}
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DROP INDEX `idx_value_ci`  ON `" . $this->dbName . "_tables`.`ci_attribute`");
    }
}