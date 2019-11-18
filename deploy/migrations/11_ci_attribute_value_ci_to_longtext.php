<?php

use PhinxExtend\AbstractPhinxMigration;

class CiAttributeValueCiToLongtext extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
		// init tables
		$ciAttributeTable = $this->table('ci_attribute');
		$migrate = false;

		$columns = $ciAttributeTable->getColumns();
		foreach($columns as $column) {
			if($column->getName() == 'value_ci') {
				if($column->getType() == 'integer') {
					$migrate = true;
				}
				break;
			}
		}

		if($migrate === true) {
			// change value_ci column
			$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci_attribute` MODIFY COLUMN `value_ci` longtext COMMENT 'ci mapping'");
			$this->execute("ALTER TABLE `" . $this->dbName . "_history`.`ci_attribute` MODIFY COLUMN `value_ci` longtext COMMENT 'ci mapping'");

			// recreate view
			$this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci_attribute AS
                select *
                from " . $this->dbName . "_tables.ci_attribute
                WITH CASCADED CHECK OPTION
            ");

			// migrate sql-dropdown attributes
			$attributes = $this->fetchAll("SELECT id FROM attribute WHERE attribute_type_id = 21");
			foreach($attributes as $attribute) {
				// move value_text column to value_ci column
				$this->execute("update ci_attribute set value_ci = value_text where attribute_id = " . $attribute['id'] . " AND value_text IS NOT NULL AND value_text != ''");

				// set value_text to null --> clean data
				$this->execute("update ci_attribute set value_text = null where attribute_id = " . $attribute['id']);
			}

			print "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
			print "TODO: manual task\n";
			print "  check webservices and workflows in CMDB!\n";
			print "  value of sql dropdowns moved from value_text to value_ci!\n";
			print "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
		}


    }

    /**
     * Migrate Down.
     */
    public function down()
    {

    }
}