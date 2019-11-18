<?php

use PhinxExtend\AbstractPhinxMigration;

class AddDefaultWebservices extends AbstractPhinxMigration{

    /**
     * Migrate Up.
     */

    public function up()
    {
        $this->down();
        $this->execute("
            INSERT INTO stored_query
            (name, note, query, status, status_message, is_default, is_active, user_id, valid_from) VALUES
            ('int_getCiIdByCiAttributeId', 'returns the ciid of a specific ci_attribute-row', 'select ci_id from ci_attribute where id = :argv1:', '1', NULL, '1', '1', 0, '2015-11-24 17:00:36'),
            ('int_deleteCiAttribute', 'delete a ci_attribute-row by id', 'call delete_ci_attribute(:argv1:, :argv2:)', '1', NULL, '1', '1', 0, '2015-11-04 09:40:50'),
            ('int_getAttributeDefaultOptionId', 'return the id of a specific attribute and value', 'select id from attribute_default_values where attribute_id = :argv1: and value = '':argv2:''', '1', NULL, '1', '1', 0, '2015-10-28 07:53:29'),
            ('int_getCiAttributeId', 'returns the id of the first ci_attribute-row with the specific ci_id and attribute_id', 'select id from ci_attribute where ci_id = :argv1: and attribute_id = :argv2: order by id asc limit 1', '1', NULL, '1', '1', 0, '2015-11-25 09:36:58'),
            ('int_updateCiAttribute', 'updates a specific ci_attribute_row argv1 = ci_attribute-ID argv2 = column argv3 = value argv4 = history_id', 'update ci_attribute set :argv2: = '':argv3:'', history_id = :argv4: where id = :argv1:', '1', NULL, '1', '1', 0, '2015-11-25 09:36:58'),
            ('int_createCiAttribute', 'creates a ci_attribute-row', 'insert into ci_attribute (ci_id, attribute_id, history_id) values (:argv1:, :argv2:, :argv3:);\nselect last_insert_id() as v', '1', NULL, '1', '1', 0, '2015-11-25 08:52:11'),
            ('int_createHistory', 'creates an History-ID', 'insert into history (user_id, note) values ('':argv1:'', '':argv2:'');\nselect last_insert_id() as v', '1', NULL, '1', '1', 0, '2015-11-25 09:36:58'),
            ('int_getNumberOfCiAttributes', 'returns the number of values for a specific attribute of a CI', 'select count(*) as v from ci_attribute where ci_id = :argv1: and attribute_id = :argv2:', '1', NULL, '1', '1', 0, '2015-11-25 09:36:58'),
            ('int_getAttributeDefaultOption', 'returns the value of an option', 'select value as v from attribute_default_values where id = :argv1:', '1', NULL, '1', '1', 0, '2015-11-25 09:36:46'),
            ('int_getAttributeIdByAttributeName', 'returns the id of an attribute', 'select id from attribute where name = '':argv1:''', '1', NULL, '1', '1', 0, '2015-11-25 09:36:58'),
            ('int_getCiAttributeValue', 'get the value of a ci_attribute entry by ci_id and attribute_id', 'select id, :argv3: as v from ci_attribute where ci_id = :argv1: and attribute_id = :argv2:', '1', NULL, '1', '1', 0, '2015-11-25 09:36:47'),
            ('int_getListOfCiIdsOfCiType', 'returns all CIIDs of a specific CI-Type', 'select id as ciid from ci where ci_type_id = :argv1:', '1', NULL, '1', '1', 0, '2015-11-25 09:04:05'),
            ('int_createAttributeGroup', 'create an attribute-group', 'insert into attribute_group (:argv1:) values (:argv2:);\nselect last_insert_id() as id', '1', NULL, '1', '1', 0, '2015-11-09 12:41:14'),
            ('int_getAttributeGroupIdByAttributeGroupName', 'returns the id of an attribute group', 'select id from attribute_group where name = '':argv1:''', '1', NULL, '1', '1', 0, '2015-11-19 10:43:01'),
            ('int_createAttribute', 'create an attribute', 'insert into attribute (:argv1:) values (:argv2:);\nselect last_insert_id() as id', '1', NULL, '1', '1', 0, '2015-11-09 12:41:16'),
            ('int_getRoleIdByRoleName', 'returns the id of a role', 'select id from role where name = '':argv1:''', '1', NULL, '1', '1', 0, '2015-11-09 12:41:16'),
            ('int_setAttributeRole', 'set permisson for an attribute', 'delete from attribute_role where attribute_id = :argv1: and role_id = :argv2:;\ninsert into attribute_role (attribute_id, role_id, permission_read, permission_write) values (:argv1:, :argv2:, '':argv3:'', '':argv4:'')', '1', NULL, '1', '1', 0, '2015-11-09 12:41:17'),
            ('int_getCiTypeIdByCiTypeName', 'returns the id for the CI-Type', 'select id from ci_type where name = '':argv1:''', '1', NULL, '1', '1', 0, '2015-11-24 06:00:41'),
            ('int_setCiTypeOfCi', 'set the ci_type of a CI', 'update ci set ci_type_id = :argv2:, history_id = :argv3: where id = :argv1:', '1', NULL, '1', '1', 0, '2015-11-24 06:00:41'),
            ('int_getCiTypeOfCi', 'returns the ci-type of a CI', 'select :argv2: from ci_type where id = (     select ci_type_id from ci where id = :argv1: )', '1', NULL, '1', '1', 0, '2016-01-11 13:11:39'),
            ('int_getCiIdByCiAttributeValue', 'returns the ci_id by a specific attribute_id and value', 'select ci_id from ci_attribute where attribute_id = :argv1: and :argv3: = '':argv2:''', '1', NULL, '1', '1', 0, '2015-11-26 10:37:42'),
            ('int_getProjectIdByProjectName', 'returns the id of the project with the given name', 'select id from project where name = '':argv1:''', '1', NULL, '1', '1', 0, '2015-11-26 12:33:04'),
            ('int_addCiProjectMapping', 'add project-mapping to a ci', 'insert into ci_project (ci_id, project_id, history_id) select :argv1:, :argv2:, :argv3: from dual where not exists(select id from ci_project where ci_id = :argv1: and project_id = :argv2:)', '1', NULL, '1', '1', 0, '2015-11-26 12:33:04'),
            ('int_removeCiProjectMapping', 'removes a ci project mapping', 'delete from ci_project where ci_id = :argv1: and project_id = :argv2:', '1', NULL, '1', '1', 0, '2015-11-26 12:39:05'),
            ('int_createCi', 'create a CI', 'insert into ci (ci_type_id, icon, history_id) values (:argv1:, '':argv2:'', :argv3:);\nset @ci_id = ( select last_insert_id() );\nselect * from ci where id = @ci_id;', '1', NULL, '1', '1', 0, '2015-11-26 12:33:04'),
            ('int_deleteCi', 'delete a CI with all dependencies', 'call delete_ci(:argv1:, :argv2:, '':argv3:'')', '1', NULL, '1', '1', 0, '2016-01-07 11:08:35'),
            ('int_createCiRelation', 'inserts a relation: argv1 = ci_id_1 argv2 = ci_id_2 argv3 = ci_relation_type_id argv4 = direction', 'insert into ci_relation (ci_id_1, ci_id_2, ci_relation_type_id, direction) VALUES (:argv1:, :argv2:, :argv3:, :argv4:)', '1', NULL, '1', '1', 0, '2016-01-07 11:31:37'),
            ('int_getCiRelationTypeIdByRelationTypeName', 'returns the id of a relation-type', 'select id from ci_relation_type where name = '':argv1:''', '1', NULL, '1', '1', 0, '2016-01-07 11:31:35'),
            ('int_getCiRelationCount', 'returns the number of relations with the given parameters', 'select count(*) as c from ci_relation where ((ci_id_1 = :argv1: and ci_id_2 = :argv2:) or (ci_id_1 = :argv2: and ci_id_2 = :argv1:)) and ci_relation_type_id = :argv3:', '1', NULL, '1', '1', 0, '2016-01-07 10:11:21'),
            ('int_deleteCiRelation', 'delete a specific ci-relation', 'delete from ci_relation where( (ci_id_1 = :argv1: and ci_id_2 = :argv2:) or  (ci_id_1 = :argv2: and ci_id_2 = :argv1:)) and  ci_relation_type_id = :argv3: limit 1', '1', NULL, '1', '1', 0, '2016-01-07 11:31:32'),
            ('int_deleteCiRelationsByCiRelationType_directedFrom', 'deletes all ci-relations with a specific relation-type of a specific CI (direction: from CI)', 'delete from ci_relation  where ci_relation_type_id = :argv2:  and (   (ci_id_1 = :argv1: and direction = 1) or (ci_id_2 = :argv1: and direction = 2)   )', '1', NULL, '1', '1', 0, '2016-01-07 11:31:30'),
            ('int_deleteCiRelationsByCiRelationType_directedTo', 'deletes all ci-relations with a specific relation-type of a specific CI (direction: to CI)', 'delete from ci_relation  where ci_relation_type_id = :argv2:  and (   (ci_id_2 = :argv1: and direction = 1) or (ci_id_1 = :argv1: and direction = 2)   )', '1', NULL, '1', '1', 0, '2016-01-07 11:31:28'),
            ('int_deleteCiRelationsByCiRelationType_directionList', 'deletes all ci-relations with a specific relation-type of a specific CI', 'delete from ci_relation where ci_relation_type_id = :argv2:  and (ci_id_1 = :argv1: or ci_id_2 = :argv1:) and direction in (:argv3:)', '1', NULL, '1', '1', 0, '2016-01-07 11:31:27'),
            ('int_getUserIdByUsername', 'returns the ID of a infoCMDB-User', 'select id from user where username = '':argv1:''', '1', NULL, '1', '1', 0, '2016-01-07 11:31:25'),
            ('int_getListOfCiIdsByCiRelation_directionList', 'returns all related CI-IDs of a specific relation-type', 'select      if(ci_id_1 = :argv1:, ci_id_2, ci_id_1) as ci_id  from ci_relation where ci_relation_type_id = :argv2:  and direction in (:argv3:)  and (ci_id_1 = :argv1: or ci_id_2 = :argv1:)', '1', NULL, '1', '1', 0, '2016-01-11 14:55:18'),
            ('int_getListOfCiIdsByCiRelation_directedFrom', 'returns all related CI-IDs of a specific relation-type (direction: from CI)', 'select   if(ci_id_1 = :argv1:, ci_id_2, ci_id_1) as ci_id from ci_relation  where ci_relation_type_id = :argv2: and (   (ci_id_1 = :argv1: and direction = 1)   or   (ci_id_2 = :argv1: and direction = 2)   ) ', '1', NULL, '1', '1', 0, '2016-01-11 15:00:29'),
            ('int_getListOfCiIdsByCiRelation_directedTo', 'returns all related CI-IDs of a specific relation-type (direction: to CI)', 'select   if(ci_id_1 = :argv1:, ci_id_2, ci_id_1) as ci_id from ci_relation  where ci_relation_type_id = :argv2: and (   (ci_id_2 = :argv1: and direction = 1)   or   (ci_id_1 = :argv1: and direction = 2)   ) ', '1', NULL, '1', '1', 0, '2016-01-11 14:55:37')

        ");

    }

    /**
     * Migrate Down.
     */

    public function down()
    {
        $this->execute("DELETE FROM stored_query WHERE is_default = '1' AND name LIKE 'int_%'");
    }

}