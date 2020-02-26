<?php

use Phinx\Migration\AbstractMigration;

class AddDefaultWebservicesToManageProjects extends AbstractMigration
{
    public function up()
    {
        $int_createProject = <<<'EOD'
insert into project 
(name, description, note, order_number, is_active, user_id, valid_from)
values 
(:argv1:, :argv2:, :argv3:, :argv4:, ''1'', :argv5:, NOW());

select last_insert_id() as id;
EOD;
        $int_addUserProjectMapping = <<<'EOD'
insert into user_project (user_id, project_id) 
select :argv1:, :argv2:
from dual 
where not exists (
    select id 
    from user_project 
    where user_id = :argv1: 
    and project_id = :argv2:
);

select last_insert_id() as id;
EOD;
        $int_getProjectCiMappings = <<<'EOD'
select cip.ci_id as id
from ci_project cip 
where cip.project_id = :argv1:;
EOD;
        $int_updateProject  = <<<'EOD'
update project 
set 
    name = :argv2:, 
    description = :argv3:, 
    note = :argv4:, 
    order_number = :argv5:, 
    is_active = ''1'', 
    user_id = :argv6:, 
    valid_from = NOW()
where id = :argv1:;
EOD;
        $int_deleteProject  = <<<'EOD'
delete from user_project
where project_id = :argv1:;

delete from ci_project
where project_id = :argv1:;

delete from project
where id = :argv1:;
EOD;

        $this->down();
        $this->execute("
            INSERT IGNORE INTO stored_query
            (name, note, query, status, status_message, is_default, is_active, user_id, valid_from) VALUES
            ('int_createProject', 'create an project\nargv1: name\nargv2: description\nargv3: note\nargv4: orderNumber\nargv5: userId', '$int_createProject', '1', NULL, '1', '1', '0', now()),
            ('int_addUserProjectMapping', 'add project-mapping to a user\nargv1: userId\nargv2: projectId', '$int_addUserProjectMapping', '1', NULL, '1', '1', '0', now()),
            ('int_getProjectCiMappings', 'get all ci ids for a given project\nargv1: projectId', '$int_getProjectCiMappings', '1', NULL, '1', '1', '0', now()),
            ('int_updateProject ', 'updates an project\nargv1: projectId\nargv2: name\nargv3: description\nargv4: note\nargv5: orderNumber\nargv6: userId', '$int_updateProject', '1', NULL, '1', '1', '0', now()),
            ('int_deleteProject ', 'delete an project including the user <--> project mapping and ci <--> project mapping\nargv1: projectId', '$int_deleteProject', '1', NULL, '1', '1', '0', now())
        ");

    }

    public function down()
    {
        $this->execute("
DELETE IGNORE FROM stored_query 
WHERE name in (
    'int_createProject', 
    'int_addUserProjectMapping', 
    'int_getProjectCiMappings', 
    'int_updateProject', 
    'int_deleteProject'
)");
    }
}
