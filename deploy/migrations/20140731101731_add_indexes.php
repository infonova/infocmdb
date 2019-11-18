<?php

use PhinxExtend\AbstractPhinxMigration;

class AddIndexes extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $ciTypeTable = $this->table($this->dbName . "_tables.ci_type");
        if($ciTypeTable->hasIndex('name') === false)
        {
            $this->execute("CREATE INDEX `idx_ci_type_name`  ON `" . $this->dbName . "_tables`.`ci_type` (name(15))");
        }

        $ciRelationTypeTable = $this->table($this->dbName . "_tables.ci_relation_type");
        if($ciRelationTypeTable->hasIndex('name') === false)
        {
            $this->execute("CREATE INDEX `idx_ci_relation_type_name`  ON `" . $this->dbName . "_tables`.`ci_relation_type` (name(25))");
        }
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DROP INDEX `idx_ci_type_name`  ON `" . $this->dbName . "_tables`.`ci_type`");
        $this->execute("DROP INDEX `idx_ci_relation_type_name`  ON `" . $this->dbName . "_tables`.`ci_relation_type`");
    }
}