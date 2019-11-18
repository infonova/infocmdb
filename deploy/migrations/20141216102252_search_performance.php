<?php

use PhinxExtend\AbstractPhinxMigration;

class SearchPerformance extends AbstractPhinxMigration
{
    
    /**
     * Migrate Up.
     */
    public function up()
    {
		$table = $this->table($this->dbName . '_tables.attribute_default_values');
		if($table->hasIndex('value') === false)
		{
			$this->execute("CREATE INDEX `idx_attribute_default_values_value`  
				ON `" . $this->dbName . "_tables`.`attribute_default_values` (value(10)) 
				COMMENT ''"
			);
		}
		
        $this->execute("DROP PROCEDURE IF EXISTS " . $this->dbName . "_tables.search_simple;");
		$this->execute("CREATE PROCEDURE " . $this->dbName . "_tables.`search_simple`(search_add varchar(100), search_remove varchar(100), search_restrict varchar(100), project_list varchar(200), session_id varchar(100), max_search integer, relation_type_restrict varchar(100))
BEGIN

  declare search_count        integer DEFAULT 1;
  declare temp_res            varchar(100);
  declare remove_strings      varchar(100);
  declare add_strings         varchar(100);
  declare bDone               integer;


  set search_count = 1;

  add_loop:
  LOOP

    IF CHAR_LENGTH(search_add) <= 0
    THEN
      LEAVE add_loop;
    END IF;


    SELECT TRIM(SPLIT_STR(search_add, '|', search_count)) INTO add_strings;
    SELECT TRIM(SPLIT_STR(search_remove, '|', search_count)) INTO remove_strings;


    IF CHAR_LENGTH(add_strings) <= 0
    THEN
      LEAVE add_loop;
    END IF;


    insert into " . $this->dbName . ".search_result(session, ci_id, citype_id)
    SELECT session_id AS 'session', ci.id as 'ci_id', ci.ci_type_id as 'citype_id'
    FROM `" . $this->dbName . "_tables`.ci_attribute ca
    LEFT JOIN `" . $this->dbName . "_tables`.ci ci ON ca.ci_id = ci.id
    WHERE ca.value_text like add_strings
    AND ca.ci_id NOT IN (
      SELECT ca2.ci_id
      FROM `" . $this->dbName . "_tables`.ci_attribute ca2
      WHERE ca2.ci_id = ca.ci_id
      AND ( remove_strings != '' AND ca2.value_text LIKE remove_strings )
    )
    GROUP BY ca.ci_id
   ;



    insert into " . $this->dbName . ".search_result(session, ci_id, citype_id)
    SELECT session_id AS 'session', ci.id as 'ci_id', ci.ci_type_id as 'citype_id'
    FROM `" . $this->dbName . "_tables`.attribute_default_values adv
    LEFT JOIN `" . $this->dbName . "_tables`.ci_attribute ca ON ca.value_default = adv.id AND ca.value_default IS NOT NULL
    LEFT JOIN `" . $this->dbName . "_tables`.ci ci ON ca.ci_id = ci.id
    AND ci.id IS NOT NULL
    AND ci.ci_type_id IS NOT NULL
    WHERE adv.value like add_strings
    AND adv.value not like remove_strings
    GROUP BY ca.ci_id
   ;

    VALUE_CI_BLOCK: BEGIN

    declare r_ci_id             integer;
    declare r_citype_id         integer;
    DECLARE value_ci_curs CURSOR
    FOR
	SELECT ci.id as 'ci_id', ci.ci_type_id as 'citype_id'
    FROM ci_attribute ca
    JOIN `" . $this->dbName . "`.search_result sr on sr.ci_id = ca.ci_id
    LEFT JOIN `" . $this->dbName . "_tables`.ci ci ON ca.ci_id = ci.id
    WHERE sr.session = session_id
		AND ci.id IS NOT NULL
		AND ci.ci_type_id IS NOT NULL 
    GROUP BY ca.ci_id
    ;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET bDone = 1;

    OPEN value_ci_curs;
    value_ci_loop: LOOP
    IF bDone = 1 THEN
      LEAVE value_ci_loop;
    END IF;
    FETCH value_ci_curs INTO r_ci_id, r_citype_id;
    IF r_ci_id is not null and r_citype_id is not null then
      insert into `" . $this->dbName . "`.search_result(session, ci_id, citype_id) VALUES (session_id, r_ci_id, r_citype_id);
    END IF;
    END LOOP value_ci_loop;
    CLOSE value_ci_curs;

    END VALUE_CI_BLOCK;

    set search_count = search_count + 1;

    IF search_count > max_search
    THEN
      LEAVE add_loop;
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



  -- project_list
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
  
-- Löschen von doppelt hinzugefügten Einträgen
-- Ursache hierfür sind mehrfach matches in attributen
	DELETE n1
	FROM	" . $this->dbName . ".search_result n1, 
			" . $this->dbName . ".search_result n2 
	WHERE n1.id > n2.id 
		AND n1.session = session_id
		AND (
		n1.session = n2.session 
		and n1.ci_id = n2.ci_id
		and n1.citype_id = n2.citype_id );
END");
    }

    /**
     * Migrate Down.
     */
    public function down()
    {

    }
}