<?php

use Phinx\Migration\AbstractMigration;

class AddDefaultWebserviceGetCiGetCiAtt extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     */
    public function up()
    {
        $this->down();
        $this->execute("
            INSERT IGNORE INTO stored_query
            (name, note, query, status, status_message, is_default, is_active, user_id, valid_from) VALUES
            ('int_getCi','Retrieve all informations about a ci','select 
ci.id as ci_id,
ci.ci_type_id as ci_type_id,
ct.name as ci_type,
group_concat(distinct p.name) as project,
group_concat(distinct p.id) as project_id
from ci
join ci_project cp on cp.ci_id = ci.id
join project p on p.id = cp.project_id
join ci_attribute cia on cia.ci_id = ci.id
join attribute a on a.id = cia.attribute_id
join attribute_type at on at.id = a.attribute_type_id
join ci_type ct on ct.id = ci.ci_type_id
where ci.id in (:argv1:)
group by ci.id','1',NULL,'1','1','0',now()),
('int_getCiAttributes','get all attributes for given ci (:argv1:)','select 
cia.ci_id as ci_id,
cia.id as ci_attribute_id,
a.id as attribute_id,
a.name as attribute_name,
a.description as attribute_description,
at.name as attribute_type,
case at.name 
         when \'input\'           then cia.value_text 
         when \'textarea\'        then cia.value_text 
         when \'textEdit\'        then cia.value_text 
         when \'password\'        then cia.value_text 
         when \'queryPersist\'    then cia.value_text 
         when \'ciTypePersist\'   then cia.value_text 
         when \'date\'            then date_format(cia.value_date, \'%Y-%m-%d\') 
         when \'dateTime\'        then cia.value_date 
         when \'zahlungsmittel\'  then cia.value_text 
         when \'select\'
         then 
         (
            select attribute_default_values.value 
            from ci_attribute 
            inner join attribute on ci_attribute.attribute_id = attribute.id 
            inner join attribute_default_values on ci_attribute.value_default = attribute_default_values.id 
            where ci_attribute.value_default = cia.value_default and ci_attribute.ci_id = cia.ci_id 
            group by attribute_default_values.id
         )
         when \'checkbox\'
         then 
         (
            select attribute_default_values.value 
            from ci_attribute 
            inner join attribute on ci_attribute.attribute_id = attribute.id 
            inner join attribute_default_values on ci_attribute.value_default = attribute_default_values.id 
            where ci_attribute.value_default = cia.value_default and ci_attribute.ci_id = cia.ci_id 
            group by attribute_default_values.id
         )
         when \'radio\'
         then 
         (
            select attribute_default_values.value 
            from ci_attribute 
            inner join attribute on ci_attribute.attribute_id = attribute.id 
            inner join attribute_default_values on ci_attribute.value_default = attribute_default_values.id 
            where ci_attribute.value_default = cia.value_default and ci_attribute.ci_id = cia.ci_id 
            group by attribute_default_values.id
         )
         when \'executeable\'           then cia.value_text 
         else \'#todo\'
     end 
     as value,
cia.valid_from as modified_at
from ci ci
inner join ci_attribute cia on ci.id = cia.ci_id
inner join attribute a on a.id = cia.attribute_id
inner join attribute_type at on at.id = a.attribute_type_id
where ci.id in (:argv1:)
    and a.attribute_type_id not in ( 13 /* script */, 15 /* Query */ )
','1',NULL,'1','1','0',now())
        ");

    }

    /**
     * Migrate Down.
     */

    public function down()
    {
        $this->execute("DELETE IGNORE FROM stored_query WHERE name in ('int_getCi', 'int_getCiAttributes')");
    }

}
