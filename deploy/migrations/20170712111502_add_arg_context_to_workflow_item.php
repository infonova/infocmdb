<?php

use PhinxExtend\AbstractPhinxMigration;


class AddArgContextToWorkflowItem extends AbstractPhinxMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        $workflow_item = $this->table('workflow_item');

        if ( !$workflow_item->hasColumn('arg_context')) {
            $this->execute(
                "ALTER TABLE `" . $this->dbName . "_tables`.`workflow_item` ADD `arg_context` TEXT COMMENT 'Parameter of script call' AFTER `workflow_transition_id`;"

            );
            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".workflow_item AS
                select *
                from " . $this->dbName . "_tables.workflow_item
                WITH CASCADED CHECK OPTION
            ");
        }
    }
}
