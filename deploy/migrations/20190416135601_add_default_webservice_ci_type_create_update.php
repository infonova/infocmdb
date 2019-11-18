<?php

use Phinx\Migration\AbstractMigration;

class AddDefaultWebserviceCiTypeCreateUpdate extends AbstractMigration
{
    public function up()
    {
        $this->down();
        $this->execute("
            INSERT IGNORE INTO stored_query
            (name, note, query, status, status_message, is_default, is_active, user_id, valid_from) 
            VALUES
            (
            'int_updateCIType',
            'update an existing citype. 
            argv1: ci_type.id 
            argv2: all set\'s (query = \"select...\", description = \"something\")',
            'update ci_type
set
:argv2:
where
id = :argv1:','1',NULL,'1','1','0',now()
            ),
            (
            'int_createCIType',
            'Create a CiType with an blank insert statement return: last_insert_id() argv1: fields (field1, field2) argv2: values (\'value\', \'value\')',
            'insert into ci_type (:argv1:) values (:argv2:);
select last_insert_id() as id
','1',NULL,'1','1','0',now()
            )
        ");

    }

    /**
     * Migrate Down.
     */

    public function down()
    {
        $this->execute("DELETE IGNORE FROM stored_query WHERE name in ('int_updateCIType', 'int_createCIType')");
    }
}
