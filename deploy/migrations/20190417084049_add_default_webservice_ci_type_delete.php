<?php

use Phinx\Migration\AbstractMigration;

class AddDefaultWebserviceCiTypeDelete extends AbstractMigration
{
    public function up()
    {
        $this->down();
        $this->execute("
            INSERT IGNORE INTO stored_query
            (name, note, query, status, status_message, is_default, is_active, user_id, valid_from) 
            VALUES
            (
            'int_deleteCIType',
            'delete CiType. argv1: id',
            'DELETE FROM ci_type where id = :argv1:',
            '1',NULL,'1','1','0',now()
            )
        ");

    }

    /**
     * Migrate Down.
     */

    public function down()
    {
        $this->execute("DELETE IGNORE FROM stored_query WHERE name in ('int_deleteCIType')");
    }
}
