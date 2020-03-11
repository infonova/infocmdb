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

        $this->execute("
                CREATE OR REPLACE DEFINER = 'root'@localhost 
                    TRIGGER `" . $this->dbName . "_tables`.attribute_default_queries_update 
                    BEFORE UPDATE 
                    ON attribute_default_queries
                    FOR EACH ROW 
                BEGIN
                    INSERT INTO " . $this->dbName . "_history.attribute_default_queries (id, attribute_id, query, list_query, valid_from, valid_to)
                    VALUES (OLD.id, OLD.attribute_id, OLD.query, OLD.list_query, OLD.valid_from, NOW());
                END;
        ");
        $this->execute("
                CREATE OR REPLACE DEFINER = 'root'@localhost 
                    TRIGGER `" . $this->dbName . "_tables`.attribute_default_queries_delete 
                    BEFORE DELETE 
                    ON attribute_default_queries
                    FOR EACH ROW 
                BEGIN
                    INSERT INTO " . $this->dbName . "_history.attribute_default_queries (id, attribute_id, query, list_query, valid_from, valid_to)
                    VALUES (OLD.id, OLD.attribute_id, OLD.query, OLD.list_query, OLD.valid_from, NOW());
                END;
        ");
    }

    public function down()
    {
        $table = $this->table('attribute_default_queries');

        $this->execute("
                CREATE OR REPLACE DEFINER = 'root'@localhost 
                    TRIGGER `" . $this->dbName . "_tables`.attribute_default_queries_update 
                    BEFORE UPDATE 
                    ON attribute_default_queries
                    FOR EACH ROW 
                BEGIN
                    INSERT INTO " . $this->dbName . "_history.attribute_default_queries (id, attribute_id, query, valid_from, valid_to)
                    VALUES (OLD.id, OLD.attribute_id, OLD.query, OLD.valid_from, NOW());
                END;
        ");
        $this->execute("
                CREATE OR REPLACE DEFINER = 'root'@localhost 
                    TRIGGER `" . $this->dbName . "_tables`.attribute_default_queries_delete 
                    BEFORE DELETE 
                    ON attribute_default_queries
                    FOR EACH ROW 
                BEGIN
                    INSERT INTO " . $this->dbName . "_history.attribute_default_queries (id, attribute_id, query, valid_from, valid_to)
                    VALUES (OLD.id, OLD.attribute_id, OLD.query, OLD.valid_from, NOW());
                END;
        ");

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
