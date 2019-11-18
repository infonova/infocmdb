<?php
use PhinxExtend\AbstractPhinxMigration;

class WebserviceGetWorkflowContext extends AbstractPhinxMigration
{
    public function up()
    {
        $this->down();
        $this->execute("INSERT INTO stored_query
(name, note, query, status, is_default, is_active, user_id, valid_from)
VALUES('int_getWorkflowContext', 'Get the workflow context by instanceID:<argv1>', 'SELECT
    wc.context AS WorkflowContext
FROM
    workflow_case wc
WHERE
    wc.id = :argv1:', '1', '1', '1', 0, now())
    ");
    }

    public function down()
    {
        $this->execute("DELETE IGNORE FROM stored_query WHERE name in ('int_getWorkflowContext')");
    }
}
