<?php

use PhinxExtend\AbstractPhinxMigration;

class AddProceduresAndFunctions extends AbstractPhinxMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        //only add functions/procedures to new databases --> do NOT create an old version of a function/procedure
        if ($this->getCurrentMigrateVersion() == 1) {

            //first drop all functions and procedures
            $this->down();

            // HISTORY - search_simple
            $this->execute("
                CREATE DEFINER=`root`@`localhost` PROCEDURE `" . $this->dbName . "_history`.`search_simple`(search_add varchar(100), search_remove varchar(100), search_restrict varchar(100), project_list varchar(200), session_id varchar(100), max_search integer, relation_type_restrict varchar(100))
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
                    FROM ci_attribute ca
                    LEFT JOIN ci ci ON ca.ci_id = ci.id
                    WHERE ca.value_text like temp_res
                    GROUP BY ca.ci_id
                   ;


                    insert into " . $this->dbName . ".search_result(session, ci_id, citype_id)
                    SELECT session_id AS 'session', ci.id as 'ci_id', ci.ci_type_id as 'citype_id'
                    FROM attribute_default_values adv
                    LEFT JOIN ci_attribute ca ON ca.value_default = adv.id AND ca.value_default IS NOT NULL
                    LEFT JOIN ci ci ON ca.ci_id = ci.id
                    AND ci.id IS NOT NULL
                    AND ci.ci_type_id IS NOT NULL
                    WHERE adv.value like temp_res
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
                      FROM ci_attribute ca
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
                      FROM ci_attribute ca
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
                      SELECT p.ci_id FROM ci_project p
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


            // HISTORY - SPLIT_STR
            $this->execute("
                CREATE DEFINER=`root`@`localhost` FUNCTION `" . $this->dbName . "_history`.`SPLIT_STR`(
                  x VARCHAR(255),
                  delim VARCHAR(12),
                  pos INT
                ) RETURNS varchar(255) CHARSET utf8
                DETERMINISTIC
                RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(x, delim, pos),
                       LENGTH(SUBSTRING_INDEX(x, delim, pos -1)) + 1),
                       delim, '')
            ");


            // TABLES - create_history
            $this->execute("
                CREATE DEFINER=`root`@`localhost` FUNCTION `" . $this->dbName . "_tables`.`create_history`(
                  user_id int(10),
                  note VARCHAR(255)
                ) RETURNS int(10)
                    DETERMINISTIC
                BEGIN
                IF note IS NULL THEN
                  SET note = 'unknown';
                END IF;
                INSERT INTO " . $this->dbName . "_history.history (user_id, note) VALUES (user_id, note);
                RETURN LAST_INSERT_ID();

                END
            ");

            // TABLES - delete_ci_attribute
            $this->execute("
                CREATE DEFINER=`root`@`localhost` PROCEDURE `" . $this->dbName . "_tables`.delete_ci_attribute(ciAttributeId int(10), c_history_id int(10))
                BEGIN

                  declare ciId integer;
                  IF c_history_id IS NOT NULL THEN
                    START TRANSACTION;
                    SELECT ci_id INTO ciId FROM ci_attribute where id = ciAttributeId;
                    insert into `" . $this->dbName . "_tables`.temp_history(history_id, ci_id) VALUES(c_history_id, ciId);
                    COMMIT;
                  END IF;

                  START TRANSACTION;
                  -- delete attributes
                  DELETE FROM `" . $this->dbName . "_tables`.ci_attribute WHERE id = ciAttributeId;

                  -- remove history temp
                  IF c_history_id IS NOT NULL THEN
                    DELETE FROM `" . $this->dbName . "_tables`.temp_history where history_id = c_history_id and ci_id = ciId;
                  END IF;
                  COMMIT;

                END
            ");

            // TABLES - delete_ci
            $this->execute("
                CREATE DEFINER=`root`@`localhost` PROCEDURE `" . $this->dbName . "_tables`.delete_ci(ciId int(10), user_id int(10), history_message varchar(100))
                BEGIN

                  declare c_history_id integer;
                  START TRANSACTION;

                  select create_history(user_id, history_message) into c_history_id;
                  insert into temp_history(history_id, ci_id) VALUES(c_history_id, ciId);
                  COMMIT;

                  START TRANSACTION;
                  -- delete unimportant stuff
                  DELETE FROM `" . $this->dbName . "_tables`.ci_ticket WHERE ci_id = ciId;
                  DELETE FROM `" . $this->dbName . "_tables`.ci_event WHERE ci_id = ciId;
                  DELETE FROM `" . $this->dbName . "_tables`.ci_permission WHERE ci_id = ciId;
                  DELETE FROM `" . $this->dbName . "_tables`.ci_highlight WHERE ci_id = ciId;
                  DELETE FROM `" . $this->dbName . "_tables`.ci_favourites WHERE ci_id = ciId;

                  -- delete relation
                  DELETE FROM `" . $this->dbName . "_tables`.ci_relation WHERE ci_id_1 = ciId;
                  DELETE FROM `" . $this->dbName . "_tables`.ci_relation WHERE ci_id_2 = ciId;

                  -- delete projects
                  DELETE FROM `" . $this->dbName . "_tables`.ci_project WHERE ci_id = ciId;

                  -- delete attributes
                  DELETE FROM `" . $this->dbName . "_tables`.ci_attribute WHERE ci_id = ciId;

                  -- delete ci
                  DELETE FROM `" . $this->dbName . "_tables`.ci WHERE id = ciId;

                  -- remove history temp
                  DELETE FROM `" . $this->dbName . "_tables`.temp_history where history_id = c_history_id and ci_id = ciId;
                  COMMIT;

                END
            ");

            // TABLES - search_simple
            $this->execute("
                CREATE PROCEDURE " . $this->dbName . "_tables.`search_simple`(search_add varchar(100), search_remove varchar(100), search_restrict varchar(100), project_list varchar(200), session_id varchar(100), max_search integer, relation_type_restrict varchar(100))
                BEGIN

                  declare search_count        integer DEFAULT 1;
                  declare temp_res            varchar(100);
                  declare remove_strings      varchar(100);
                  declare add_strings         varchar(100);


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
                      #check if remove_strings is empty to don't exclude ci's with empty values
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

                END;
            ");

            // TABLES - SPLIT_STR
            $this->execute("
                CREATE DEFINER=`root`@`localhost` FUNCTION `" . $this->dbName . "_tables`.`SPLIT_STR`(
                  x VARCHAR(255),
                  delim VARCHAR(12),
                  pos INT
                ) RETURNS varchar(255) CHARSET utf8
                DETERMINISTIC
                RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(x, delim, pos),
                       LENGTH(SUBSTRING_INDEX(x, delim, pos -1)) + 1),
                       delim, '')
            ");

            // create_history
            $this->execute("
                CREATE DEFINER=`root`@`localhost` FUNCTION `" . $this->dbName . "`.`create_history`(
                  user_id int(10),
                  note VARCHAR(255)
                ) RETURNS int(10)
                    DETERMINISTIC
                BEGIN
                RETURN " . $this->dbName . "_tables.create_history(user_id, note);

                END
            ");

            // delete_ci_attribute
            $this->execute("
                CREATE DEFINER=`root`@`localhost` PROCEDURE `" . $this->dbName . "`.delete_ci_attribute(ciAttributeId int(10), c_history_id int(10))
                BEGIN
                call " . $this->dbName . "_tables.delete_ci_attribute(ciAttributeId, c_history_id);
                END
            ");

            // delete_ci
            $this->execute("
                CREATE DEFINER=`root`@`localhost` PROCEDURE `" . $this->dbName . "`.delete_ci(ciId int(10), user_id int(10), history_message varchar(100))
                BEGIN
                call " . $this->dbName . "_tables.delete_ci(ciId, user_id, history_message);

                END
            ");

            // search_simple
            $this->execute("
                CREATE DEFINER=`root`@`localhost` PROCEDURE `" . $this->dbName . "`.`search_simple`(search_add varchar(100), search_remove varchar(100), search_restrict varchar(100), project_list varchar(200), session_id varchar(100), max_search integer, ci_type_restriction varchar(200))
                BEGIN

                  call " . $this->dbName . "_tables.search_simple(search_add, search_remove, search_restrict, project_list, session_id, max_search, ci_type_restriction);

                END
            ");

            // search_simple_history
            $this->execute("
                CREATE DEFINER=`root`@`localhost` PROCEDURE `" . $this->dbName . "`.`search_simple_history`(search_add varchar(100), search_remove varchar(100), search_restrict varchar(100), project_list varchar(200), session_id varchar(100), max_search integer, ci_type_restriction varchar(200))
                BEGIN

                  call " . $this->dbName . "_history.search_simple(search_add, search_remove, search_restrict, project_list, session_id, max_search, ci_type_restriction);

                END
            ");

        } else {
            print "SKIPPED migration\n";
        }

    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("DROP PROCEDURE IF EXISTS `" . $this->dbName . "_history`.`search_simple`");
        $this->execute("DROP FUNCTION  IF EXISTS `" . $this->dbName . "_history`.`SPLIT_STR`");
        $this->execute("DROP FUNCTION  IF EXISTS `" . $this->dbName . "_tables`.`create_history`");
        $this->execute("DROP PROCEDURE IF EXISTS `" . $this->dbName . "_tables`.delete_ci_attribute");
        $this->execute("DROP PROCEDURE IF EXISTS `" . $this->dbName . "_tables`.delete_ci");
        $this->execute("DROP PROCEDURE IF EXISTS `" . $this->dbName . "_tables`.search_simple");
        $this->execute("DROP FUNCTION  IF EXISTS `" . $this->dbName . "_tables`.`SPLIT_STR`");
        $this->execute("DROP FUNCTION  IF EXISTS `" . $this->dbName . "`.`create_history`");
        $this->execute("DROP PROCEDURE IF EXISTS `" . $this->dbName . "`.delete_ci_attribute");
        $this->execute("DROP PROCEDURE IF EXISTS `" . $this->dbName . "`.delete_ci");
        $this->execute("DROP PROCEDURE IF EXISTS `" . $this->dbName . "`.search_simple");
        $this->execute("DROP PROCEDURE IF EXISTS `" . $this->dbName . "`.search_simple_history");
    }
}