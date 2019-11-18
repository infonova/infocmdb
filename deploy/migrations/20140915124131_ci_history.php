<?php

use PhinxExtend\AbstractPhinxMigration;

class CiHistory extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        //drop ci_update-trigger
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_update`");

        //create ci_update-trigger with check if data has changed (ci_type or icon)
        $this->execute("
CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_update` BEFORE UPDATE ON " . $this->dbName . "_tables.ci FOR EACH ROW
BEGIN
    DECLARE changed INTEGER;
		SET changed = 0;

    IF (NEW.ci_type_id IS NOT NULL AND STRCMP(NEW.ci_type_id, OLD.ci_type_id) <> 0) OR
    (NEW.icon IS NOT NULL AND STRCMP(NEW.icon, OLD.icon) <> 0)
			THEN SET changed = 1;
		END IF;

    IF changed > 0 THEN

    	IF NEW.history_id IS NULL OR NEW.history_id = 0 OR NEW.history_id = OLD.history_id THEN
   		SET NEW.history_id = create_history('0', 'ci updated');
   	END IF;

      INSERT INTO " . $this->dbName . "_history.ci(id, ci_type_id, icon, history_id, valid_from, history_id_delete, valid_to)
      VALUES(OLD.id, OLD.ci_type_id, OLD.icon, OLD.history_id, OLD.valid_from, NEW.history_id, NOW());

      SET NEW.valid_from = NOW();

    END IF;
  END;
        ");
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        //no down needed
    }
}