<?php

use PhinxExtend\AbstractPhinxMigration;

class AddLockTable extends AbstractPhinxMigration {
    public function up() {
        $this->execute("CREATE TABLE " . $this->dbName . ".`lock` (
                                id INT NOT NULL AUTO_INCREMENT,
                                lock_type varchar(10) NOT NULL,
                                resource_id INT NOT NULL,
                                held_by INT NOT NULL,
                                locked_since DATETIME NOT NULL,
                                valid_until DATETIME NOT NULL,
                                CONSTRAINT lock_PK PRIMARY KEY (id)
                            )
                            ENGINE=MEMORY
                            DEFAULT CHARSET=utf8
                            COLLATE=utf8_general_ci ;
                            CREATE INDEX idx_lock_type_resource_id USING BTREE ON " . $this->dbName . ".`lock` (lock_type (5),resource_id) ;
                      ");
    }
}
