<?php

use PhinxExtend\AbstractPhinxMigration;

class AddListQuery extends AbstractPhinxMigration
{
    public function up()
    {
        $this->down();

        $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute_default_queries` ADD `list_query` text AFTER `query`");
        $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute_default_queries` ADD `list_query` text AFTER `query`");

        $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_default_queries AS
                select *
                from " . $this->dbName . "_tables.attribute_default_queries
                WITH CASCADED CHECK OPTION
        ");
    }

    public function down()
    {
        $table = $this->table('attribute_default_queries');

        if ($table->hasColumn('list_query')) {
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute_default_queries` DROP COLUMN `list_query`");
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute_default_queries` DROP COLUMN `list_query`");
        }

        $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".attribute_default_queries AS
                select *
                from " . $this->dbName . "_tables.attribute_default_queries
                WITH CASCADED CHECK OPTION
        ");
    }
}
