<?php

use PhinxExtend\AbstractPhinxMigration;

class SearchValueCiSupport extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->execute("DROP PROCEDURE IF EXISTS `" . $this->dbName . "_tables`.`search_simple`");
        $this->execute("
        CREATE DEFINER=`root`@`localhost` PROCEDURE `" . $this->dbName . "_tables`.`search_simple`(search_add varchar(100), search_remove varchar(100), search_restrict varchar(100), project_list varchar(200), session_id varchar(100), max_search integer, relation_type_restrict varchar(100))
        BEGIN
          declare search_count        integer DEFAULT 1;
          declare temp_res            varchar(100);
        
          set search_count = 1;
          add_loop:
          LOOP
            IF CHAR_LENGTH(search_add) <= 0
            THEN
              LEAVE add_loop;
            END IF;
        
            SELECT TRIM(SPLIT_STR(search_add, '|', search_count)) INTO temp_res;
            
            IF CHAR_LENGTH(temp_res) <= 0
            THEN
              LEAVE add_loop;
            END IF;
        
            insert into " . $this->dbName . ".search_result(session, ci_id, citype_id)
            SELECT session_id AS 'session', ci.id as 'ci_id', ci.ci_type_id as 'citype_id'
            FROM `" . $this->dbName . "_tables`.ci_attribute ca
            LEFT JOIN `" . $this->dbName . "_tables`.ci ci ON ca.ci_id = ci.id
            WHERE ca.value_text like temp_res
            GROUP BY ca.ci_id
           ;
        
            insert into " . $this->dbName . ".search_result(session, ci_id, citype_id)
            SELECT session_id AS 'session', ci.id as 'ci_id', ci.ci_type_id as 'citype_id'
            FROM `" . $this->dbName . "_tables`.attribute_default_values adv
            LEFT JOIN `" . $this->dbName . "_tables`.ci_attribute ca ON ca.value_default = adv.id AND ca.value_default IS NOT NULL
            LEFT JOIN `" . $this->dbName . "_tables`.ci ci ON ca.ci_id = ci.id
            AND ci.id IS NOT NULL
            AND ci.ci_type_id IS NOT NULL
            WHERE adv.value like temp_res
            GROUP BY ca.ci_id
           ;
           
            insert into `" . $this->dbName . "`.search_result(session, ci_id, citype_id)
            SELECT session_id AS 'session', ci.id as 'ci_id', ci.ci_type_id as 'citype_id'
            FROM 
            (
                SELECT * FROM `" . $this->dbName . "_tables`.ci_attribute WHERE value_ci IN 
                (
                    SELECT ci_id FROM `" . $this->dbName . "`.search_result WHERE session = session_id
                )
            ) ca
            LEFT JOIN `" . $this->dbName . "_tables`.ci ci ON ca.ci_id = ci.id
            AND ci.id IS NOT NULL
            AND ci.ci_type_id IS NOT NULL
            GROUP BY ca.ci_id
           ;
            set search_count = search_count + 1;
            IF search_count > max_search
            THEN
              LEAVE add_loop;
            END IF;
          END LOOP;
          set search_count = 1;
          remove_loop:
          LOOP
            IF CHAR_LENGTH(search_remove) <= 0
            THEN
              LEAVE remove_loop;
            END IF;
            
            SELECT TRIM(SPLIT_STR(search_remove, '|', search_count)) INTO temp_res;
        
            IF CHAR_LENGTH(temp_res) <= 0
            THEN
              LEAVE remove_loop;
            END IF;
        
            
            DELETE FROM " . $this->dbName . ".search_result
            WHERE " . $this->dbName . ".search_result.session = session_id
            AND " . $this->dbName . ".search_result.ci_id IN (
              SELECT ca.ci_id
              FROM `" . $this->dbName . "_tables`.ci_attribute ca
              WHERE ( ca.value_text like temp_res or ca.value_default IN (SELECT adv.id from attribute_default_values adv where adv.value like temp_res))
            );
            set search_count = search_count + 1;
            IF search_count > max_search
            THEN
              LEAVE remove_loop;
            END IF;
          END LOOP;
        
        
          set search_count = 1;
          
          restrict_loop:
          LOOP
            IF CHAR_LENGTH(search_restrict) <= 0
            THEN
              LEAVE restrict_loop;
            END IF;
        
            SELECT TRIM(SPLIT_STR(search_restrict, '|', search_count)) INTO temp_res;
            
            IF CHAR_LENGTH(temp_res) <= 0
            THEN
              LEAVE restrict_loop;
            END IF;
        
            DELETE FROM " . $this->dbName . ".search_result
            WHERE " . $this->dbName . ".search_result.session = session_id
            AND " . $this->dbName . ".search_result.ci_id NOT IN (
              SELECT ca.ci_id
              FROM `" . $this->dbName . "_tables`.ci_attribute ca
              WHERE ( ca.value_text like temp_res or ca.value_default IN (SELECT adv.id from attribute_default_values adv where adv.value like temp_res))
            );
        
            set search_count = search_count + 1;
            IF search_count > max_search
            THEN
              LEAVE restrict_loop;
            END IF;
          END LOOP;
        
          
          IF project_list IS NOT NULL THEN
            DELETE FROM " . $this->dbName . ".search_result
            WHERE " . $this->dbName . ".search_result.session = session_id
            AND " . $this->dbName . ".search_result.ci_id NOT IN (
              SELECT p.ci_id FROM `" . $this->dbName . "_tables`.ci_project p
              WHERE project_list REGEXP CONCAT('(^|,)',p.project_id,'(,|$)')
          );
          END IF;
         IF relation_type_restrict != '' THEN
             DELETE FROM " . $this->dbName . ".search_result
             WHERE " . $this->dbName . ".search_result.session = session_id
             AND " . $this->dbName . ".search_result.ci_id NOT IN (
                 SELECT cir.id FROM " . $this->dbName . ".ci cir
                 INNER JOIN " . $this->dbName . ".ci_type ctr ON cir.ci_type_id = ctr.id
                 INNER JOIN " . $this->dbName . ".ci_type_relation_type ctrtr ON ctr.id = ctrtr.ci_type_id AND ctrtr.ci_relation_type_id like relation_type_restrict
             );
          END IF;
          
        END
        ");
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        //no down-action, change does not affect application
    }
}