<?php

use PhinxExtend\AbstractPhinxMigration;

class ValidationAttributeAddProjectIdCiTypeId extends AbstractPhinxMigration {
    /**
     * Migrate Up.
     */
    public function up() {

    	$validationAttributeTable = $this->table('import_file_validation_attributes');
        
    	if( !$validationAttributeTable->hasColumn('project_id')) {
                echo "adding project_id to import_file_validation_attributes";
    		$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`import_file_validation_attributes` ADD `project_id` INT(10) UNSIGNED DEFAULT NULL");
    	}
    	if( !$validationAttributeTable->hasColumn('ci_type_id')) {
                echo "adding ci_type_id to import_file_validation_attributes";
    		$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`import_file_validation_attributes` ADD `ci_type_id` INT(10) UNSIGNED DEFAULT NULL");
    	}
        echo "recreating view for import_file_validation_attributes";
        $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".import_file_validation_attributes AS
            select *
            from " . $this->dbName . "_tables.import_file_validation_attributes
            WITH CASCADED CHECK OPTION
        ");
    
    }


}

?>    	 
