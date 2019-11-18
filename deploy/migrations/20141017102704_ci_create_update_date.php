<?php

use PhinxExtend\AbstractPhinxMigration;

class CiCreateUpdateDate extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $ciTable = $this->table('ci');

        //recreate view
        $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".ci AS
            select *
            from " . $this->dbName . "_tables.ci
            WITH CASCADED CHECK OPTION
        ");

        if(!$ciTable->hasColumn('created_at')) {

            /////////////////
            // ADD COLUMNS //
            /////////////////
            echo "Add Columns\n";
            //add created_at- and updated_at-columns
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci` ADD `created_at` DATETIME NULL COMMENT 'will be set on create of row'");
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci` ADD `updated_at` DATETIME NULL COMMENT 'will be set on update of a CI or dependencies'");


            echo "Add Missing Indexes\n";

            /////////////////////////////////////////////////
            // ADD MISSING INDEXES FOR CI-RELATION-HISTORY //
            /////////////////////////////////////////////////

            //combined index for ci_id_1 and ci_id_2
            $ciId12Check = $this->fetchRow("
                SELECT count(*) c, INDEX_NAME FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci_relation'
                AND (COLUMN_NAME = 'ci_id_2' OR COLUMN_NAME = 'ci_id_1')
                GROUP BY INDEX_NAME HAVING c > 1
            ");
            $ciId12IndexCounter = $ciId12Check[0];
            $ciId12IndexName = $ciId12Check[1];
            if($ciId12IndexCounter != 2) { //2 columns index
                echo "Adding Index to ci-relation-history: 'ci_id_12'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci_relation ADD INDEX ci_id_12 (ci_id_1, ci_id_2)");
                $ciId12IndexName = "ci_id_12";
            }

            //index for ci_id_2
            $ciId2Check = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci_relation'
                AND COLUMN_NAME = 'ci_id_2'
                AND INDEX_NAME != '".$ciId12IndexName."'
            ");
            $ciId2IndexCounter = $ciId2Check[0];
            if($ciId2IndexCounter == 0) {
                echo "Adding Index to ci-relation-history: 'ci_id_2'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci_relation ADD INDEX (ci_id_2)");
            }


            //index for history_id
            $historyIdCheck = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci_relation'
                AND COLUMN_NAME = 'history_id'
            ");
            $historyIdIndexCounter = $historyIdCheck[0];
            if($historyIdIndexCounter == 0) {
                echo "Adding Index to ci-relation-history: 'history_id'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci_relation ADD INDEX history_id (history_id)");
            }

            //index for history_id_delete
            $historyIdDeleteCheck = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci_relation'
                AND COLUMN_NAME = 'history_id_delete'
            ");
            $historyIdDeleteCounter = $historyIdDeleteCheck[0];
            if($historyIdDeleteCounter == 0) {
                echo "Adding Index to ci-relation-history: 'history_id_delete'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci_relation ADD INDEX history_id_delete (history_id_delete)");
            }


            ////////////////////////////////////////////////
            // ADD MISSING INDEXES FOR CI-PROJECT-HISTORY //
            ////////////////////////////////////////////////

            //index for history_id
            $historyIdCheck = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci_project'
                AND COLUMN_NAME = 'history_id'
            ");
            $historyIdCounter = $historyIdCheck[0];
            if($historyIdCounter == 0) {
                echo "Adding Index to ci-project-history: 'history_id'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci_project ADD INDEX history_id (history_id)");
            }

            //index for history_id_delete
            $historyIdDeleteCheck = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci_project'
                AND COLUMN_NAME = 'history_id_delete'
            ");
            $historyIdDeleteCounter = $historyIdDeleteCheck[0];
            if($historyIdDeleteCounter == 0) {
                echo "Adding Index to ci-project-history: 'history_id_delete'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci_project ADD INDEX history_id_delete (history_id_delete)");
            }

            //index for ci_id
            $ciIdCheck = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci_project'
                AND COLUMN_NAME = 'ci_id'
            ");
            $ciIdIndexCounter = $ciIdCheck[0];
            if($ciIdIndexCounter == 0) {
                echo "Adding Index to ci-project-history: 'ci_id'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci_project ADD INDEX ci_id (ci_id)");
            }

            //index for project_id
            $projectIdCheck = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci_project'
                AND COLUMN_NAME = 'project_id'
            ");
            $projectIdIndexCounter = $projectIdCheck[0];
            if($projectIdIndexCounter == 0) {
                echo "Adding Index to ci-project-history: 'project_id'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci_project ADD INDEX project_id (project_id)");
            }

            ////////////////////////////////////////
            // ADD MISSING INDEXES FOR CI-HISTORY //
            ////////////////////////////////////////

            //index for history_id
            $historyIdCheck = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci'
                AND COLUMN_NAME = 'history_id'
            ");
            $historyIdCounter = $historyIdCheck[0];
            if($historyIdCounter == 0) {
                echo "Adding Index to ci-history: 'history_id'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci ADD INDEX history_id (history_id)");
            }

            //index for history_id_delete
            $historyIdDeleteCheck = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci'
                AND COLUMN_NAME = 'history_id_delete'
            ");
            $historyIdDeleteCounter = $historyIdDeleteCheck[0];
            if($historyIdDeleteCounter == 0) {
                echo "Adding Index to ci-history: 'history_id_delete'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci ADD INDEX history_id_delete (history_id_delete)");
            }

            //index for id
            $IdCheck = $this->fetchRow("
                SELECT count(*) c FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = '" . $this->dbName . "_history' AND TABLE_NAME = 'ci'
                AND COLUMN_NAME = 'id'
            ");
            $IdIndexCounter = $IdCheck[0];
            if($IdIndexCounter == 0) {
                echo "Adding Index to ci: 'id'\n";
                $this->execute("ALTER TABLE " . $this->dbName . "_history.ci ADD INDEX (id)");
            }

            /////////////////////
            // UPDATE TRIGGERS //
            /////////////////////
            echo "Update Triggers\n";
            //ci_attribute - insert
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_attribute_insert`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_attribute_insert` BEFORE INSERT ON " . $this->dbName . "_tables.ci_attribute FOR EACH ROW
                BEGIN
                  DECLARE c_historicize CHAR;
                  DECLARE row_date VARCHAR(19);
                  SELECT historicize INTO c_historicize FROM attribute WHERE id = NEW.attribute_id;
                
                  IF c_historicize != '0' THEN BEGIN
                      IF NEW.history_id IS NULL OR NEW.history_id = 0 THEN
                          SET NEW.history_id = create_history('0', 'ci_attribute created');
                      END IF;
                      SET row_date = NOW();
                      UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = NEW.ci_id;
                      SET NEW.valid_from = row_date;
                  END; END IF;
                END;
            ");
            
            //ci_attribute - update
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_attribute_update`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_attribute_update` BEFORE UPDATE ON " . $this->dbName . "_tables.ci_attribute FOR EACH ROW
                BEGIN
                    DECLARE c_historicize CHAR;
                  DECLARE row_date VARCHAR(19);
                    SELECT historicize INTO c_historicize FROM attribute WHERE id = OLD.attribute_id;

                    IF c_historicize != '0' THEN BEGIN

                        DECLARE changed INTEGER;
                        SET changed = 0;

                        IF (NEW.value_text IS NOT NULL AND STRCMP(NEW.value_text, OLD.value_text) <> 0) or
                        (NEW.value_date IS NOT NULL AND STRCMP(NEW.value_date, OLD.value_date) <> 0) or
                        (NEW.value_default IS NOT NULL AND STRCMP(NEW.value_default, OLD.value_default) <> 0) or
                        (NEW.value_ci IS NOT NULL AND STRCMP(NEW.value_ci, OLD.value_ci) <> 0) or
                        (NEW.note IS NOT NULL AND STRCMP(NEW.note, OLD.note) <> 0)
                            THEN SET changed = 1;
                        END IF;

                        IF changed > 0 THEN

                            IF NEW.history_id IS NULL OR NEW.history_id = 0 OR NEW.history_id = OLD.history_id THEN
                                SET NEW.history_id = create_history('0', 'ci_attribute updated ');
                            END IF;

                            INSERT INTO " . $this->dbName . "_history.ci_attribute(id, ci_id, attribute_id, value_text, value_date, value_default, value_ci, note, is_initial, history_id, valid_from, history_id_delete, valid_to)
                            VALUES(OLD.id, OLD.ci_id, OLD.attribute_id, OLD.value_text, OLD.value_date, OLD.value_default, OLD.value_ci, OLD.note, OLD.is_initial, OLD.history_id, OLD.valid_from, NEW.history_id, NOW());

                            SET row_date = NOW();
                            UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = NEW.ci_id;
                            SET NEW.valid_from = row_date;
                        END IF;
                    END; END IF;
                  END;
            ");
            
            //ci_attribute - delete
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_attribute_delete`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_attribute_delete` BEFORE DELETE ON " . $this->dbName . "_tables.ci_attribute FOR EACH ROW
                BEGIN
                    DECLARE c_historicize CHAR;
                    DECLARE row_date VARCHAR(19);
                    SELECT historicize INTO c_historicize FROM attribute WHERE id = OLD.attribute_id;

                    IF c_historicize != '0' THEN BEGIN
                        DECLARE c_history_id INTEGER;
                        SELECT history_id INTO c_history_id FROM temp_history WHERE (datestamp <= NOW() and datestamp > (NOW() - INTERVAL 1 MINUTE))  AND ci_id = OLD.ci_id order by datestamp desc limit 1;
                        IF c_history_id IS NULL OR c_history_id <= 0 THEN
                            SELECT create_history('0', 'ci_attribute deleted') INTO c_history_id;
                        END IF;

                        SET row_date = NOW();
                        UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = OLD.ci_id;
                        INSERT INTO " . $this->dbName . "_history.ci_attribute(id, ci_id, attribute_id, value_text, value_date, value_default, value_ci, note, is_initial, history_id, valid_from, history_id_delete, valid_to)
                        VALUES(OLD.id, OLD.ci_id, OLD.attribute_id, OLD.value_text, OLD.value_date, OLD.value_default, OLD.value_ci, OLD.note, OLD.is_initial, OLD.history_id, OLD.valid_from, c_history_id, row_date);
                    END; END IF;
                  END;
            ");

            //ci_project - insert
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_project_insert`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_project_insert` BEFORE INSERT ON " . $this->dbName . "_tables.ci_project FOR EACH ROW
                BEGIN
                    DECLARE row_date VARCHAR(19);
                    
                    IF NEW.history_id IS NULL OR NEW.history_id = 0 THEN
                        SET NEW.history_id = create_history('0', 'ci_project created');
                    END IF;
                    SET row_date = NOW();
                    UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = NEW.ci_id;
                    SET NEW.valid_from = row_date;
                 
                 END;
            ");
            
            //ci_project - update
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_project_update`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_project_update` BEFORE UPDATE ON " . $this->dbName . "_tables.ci_project FOR EACH ROW
                BEGIN
                    DECLARE row_date VARCHAR(19);
                    
                    IF NEW.history_id IS NULL OR NEW.history_id = 0 OR NEW.history_id = OLD.history_id THEN
                        SET NEW.history_id = create_history('0', 'ci_project updated');
                    END IF;
                    
                    INSERT INTO " . $this->dbName . "_history.ci_project(id, ci_id, project_id, history_id, valid_from, history_id_delete, valid_to)
                    VALUES(OLD.id, OLD.ci_id, OLD.project_id, OLD.history_id, OLD.valid_from, NEW.history_id, NOW());
                
                    SET row_date = NOW();
                    UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = NEW.ci_id;
                    SET NEW.valid_from = row_date;
                  END;
            ");
            
            //ci_project - delete
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_project_delete`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_project_delete` BEFORE DELETE ON " . $this->dbName . "_tables.ci_project FOR EACH ROW
                BEGIN	
                    DECLARE c_history_id INTEGER;
                    DECLARE row_date VARCHAR(19);
                    
                    SELECT history_id INTO c_history_id FROM temp_history WHERE (datestamp <= NOW() and datestamp > (NOW() - INTERVAL 1 MINUTE))  AND ci_id = OLD.ci_id order by datestamp desc  limit 1;
                  
                    if c_history_id IS NULL OR c_history_id <= 0 THEN
                      select create_history('0', 'ci_project deleted') into c_history_id;
                    END IF;
                    
                    SET row_date = NOW();
                    UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = OLD.ci_id;
                    INSERT INTO " . $this->dbName . "_history.ci_project(id, ci_id, project_id, history_id, valid_from, history_id_delete, valid_to)
                    VALUES(OLD.id, OLD.ci_id, OLD.project_id, OLD.history_id, OLD.valid_from, c_history_id, row_date);
                  END;
            ");
            
            //ci - insert
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_insert`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_insert` BEFORE INSERT ON " . $this->dbName . "_tables.ci FOR EACH ROW
                BEGIN
                    DECLARE row_date VARCHAR(19);

                    IF NEW.history_id IS NULL OR NEW.history_id = 0 THEN
                        SET NEW.history_id = create_history('0', 'ci created');
                    END IF;
                    SET row_date = NOW();
                    SET NEW.created_at = row_date;
                    SET NEW.updated_at = row_date;
                    SET NEW.valid_from = row_date;
                 
                 END;
            ");

            //ci - update
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_update`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_update` BEFORE UPDATE ON " . $this->dbName . "_tables.ci FOR EACH ROW
                BEGIN
                    DECLARE changed INTEGER;
                    DECLARE row_date VARCHAR(19);
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
                
                      SET row_date = NOW();
                      SET NEW.updated_at = row_date;
                      SET NEW.valid_from = row_date;
                
                    END IF;
                  END;
            ");

            //TODO: ci - delete  -->  how to set dates after restore??

            //ci_relation - insert
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_relation_insert`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_relation_insert` BEFORE INSERT ON " . $this->dbName . "_tables.ci_relation FOR EACH ROW
                BEGIN
                    DECLARE row_date VARCHAR(19);
                    
                    IF NEW.history_id IS NULL OR NEW.history_id = 0 THEN
                        SET NEW.history_id = create_history('0', 'ci_relation created');
                    END IF;

                    SET row_date = NOW();
                    UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = NEW.ci_id_1;
                    UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = NEW.ci_id_2;
                    SET NEW.valid_from = row_date;
                 
                 END;
            ");

            //ci_relation - update --> ignore --> should not be updated per GUI

            //ci_relation - delete
            $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`ci_relation_delete`");
            $this->execute("
                CREATE DEFINER=`root`@`localhost` TRIGGER `" . $this->dbName . "_tables`.`ci_relation_delete` BEFORE DELETE ON " . $this->dbName . "_tables.ci_relation FOR EACH ROW
                BEGIN	
                    DECLARE c_history_id INTEGER;
                    DECLARE row_date VARCHAR(19);
                    
                    SELECT history_id INTO c_history_id FROM temp_history WHERE (datestamp <= NOW() and datestamp > (NOW() - INTERVAL 1 MINUTE))  AND (ci_id = OLD.ci_id_1 or ci_id = OLD.ci_id_2) order by datestamp desc  limit 1;
                  
                    if c_history_id IS NULL OR c_history_id <= 0 THEN
                      select create_history('0', 'ci_relation deleted') into c_history_id;
                    END IF;

                    SET row_date = NOW();
                    UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = OLD.ci_id_1;
                    UPDATE " . $this->dbName . "_tables.ci SET updated_at = row_date WHERE id = OLD.ci_id_2;
                    INSERT INTO " . $this->dbName . "_history.ci_relation(id, ci_relation_type_id, ci_id_1, ci_id_2, attribute_id_1, attribute_id_2, direction, weighting, color, note, history_id, valid_from, history_id_delete, valid_to)
                    VALUES(OLD.id, OLD.ci_relation_type_id, OLD.ci_id_1, OLD.ci_id_2, OLD.attribute_id_1, OLD.attribute_id_2, OLD.direction, OLD.weighting, OLD.color, OLD.note, OLD.history_id, OLD.valid_from, c_history_id, row_date);
                  END;
            ");

            //////////////////////
            // RECREATE CI-VIEW //
            //////////////////////
            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".ci AS
                select *
                from " . $this->dbName . "_tables.ci
                WITH CASCADED CHECK OPTION
            ");


            //////////////////
            // MIGRATE DATA //
            //////////////////
            echo "Migrate Data\n";
            $cis = $this->fetchAll("SELECT id FROM ci");
            foreach($cis as $ci) {
                $ci = $ci[0];
                $created_at = $this->fetchRow("
                    select * from (
                      select valid_from from h_ci where id = ".$ci."
                      union
                      select valid_from from ci where id = ".$ci."
                    ) a order by valid_from asc limit 1
                ");
                $created_at = $created_at[0];

                $updated_at = $this->fetchRow("
                    SELECT * FROM (
                      select valid_from as 'valid' from ci_attribute where ci_id = ".$ci."
                      union all
                      select valid_to as 'valid'  from h_ci_attribute where ci_id = ".$ci."
                    
                      UNION ALL
                    
                      select valid_from as 'valid' from ci_project where ci_id = ".$ci."
                      union all
                      select valid_to as 'valid'  from h_ci_project where ci_id = ".$ci."
                    
                      UNION ALL
                    
                      select valid_from as 'valid' from ci where id = ".$ci."
                      union all
                      select valid_to as 'valid'  from h_ci where id = ".$ci."
                    
                      UNION ALL
                    
                      select valid_from as 'valid' from ci_relation where ci_id_1 = ".$ci."
                      union all
                      select valid_to as 'valid'  from h_ci_relation where ci_id_1 = ".$ci."
                    
                      UNION ALL
                    
                      select valid_from as 'valid' from ci_relation where ci_id_2 = ".$ci."
                      union all
                      select valid_to as 'valid'  from h_ci_relation where ci_id_2 = ".$ci."
                    ) result
                    ORDER BY result.valid DESC
                    LIMIT 1    
                ");
                $updated_at = $updated_at[0];

                $update_stmt = "UPDATE ci SET created_at = '".$created_at."', updated_at = '".$updated_at."' WHERE id = ".$ci;
                #print $update_stmt."\n";
                $this->execute($update_stmt);
            }
        }

    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $ciTable = $this->table('ci');
        if($ciTable->hasColumn('created_at')) {
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`ci` DROP `created_at`, DROP `updated_at`");
        }

        //recreate view
        $this->execute("
            CREATE OR REPLACE ALGORITHM=MERGE
            DEFINER=`root`@`localhost`
            SQL SECURITY DEFINER

            VIEW " . $this->dbName . ".ci AS
            select *
            from " . $this->dbName . "_tables.ci
            WITH CASCADED CHECK OPTION
        ");

    }
}