<?php

use Phinx\Migration\AbstractMigration;

class AddDefaultWebservicesProject extends AbstractMigration
{

    public function up()
    {
        $this->down();
        $this->execute("
            INSERT IGNORE INTO stored_query
            (name, note, query, status, status_message, is_default, is_active, user_id, valid_from) 
            VALUES
            (
            'int_getCiProjectMappings',
            'Get all Projects for a given CI',
            'select p.id, p.name from ci_project cip join project p on cip.project_id = p.id where cip.ci_id = :argv1:',
            '1',NULL,'1','1','0',now()
            ),
            (
            'int_getProjects','Retrieve all CMDB Projects','select id, name from project','1',NULL,'1','1','0',now()
            )
        ");

    }

    /**
     * Migrate Down.
     */

    public function down()
    {
        $this->execute("DELETE IGNORE FROM stored_query WHERE name in ('int_getCiProjectMappings', 'int_getProjects')");
    }
}
