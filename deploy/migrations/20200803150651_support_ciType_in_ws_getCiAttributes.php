<?php

use PhinxExtend\AbstractPhinxMigration;

class SupportCiTypeInWsGetCiAttributes extends AbstractPhinxMigration
{
    public function up()
    {
        $this->down();
        $this->execute("
            UPDATE `" . $this->dbName . "_tables`.`stored_query` sq SET sq.query = 'select
   cia.ci_id as ci_id,
   cia.id as ci_attribute_id,
   a.id as attribute_id,
   a.name as attribute_name,
   a.description as attribute_description,
   at.name as attribute_type,
   case at.name
       when ''input''           then cia.value_text
       when ''textarea''        then cia.value_text
       when ''textEdit''        then cia.value_text
       when ''password''        then cia.value_text
       when ''queryPersist''    then cia.value_text
       when ''ciTypePersist''   then cia.value_text
       when ''date''            then date_format(cia.value_date, ''%Y-%m-%d'')
       when ''dateTime''        then cia.value_date
       when ''zahlungsmittel''  then cia.value_text
       when ''select''
           then
           (
               select attribute_default_values.value
               from ci_attribute
               inner join attribute on ci_attribute.attribute_id = attribute.id
               inner join attribute_default_values on ci_attribute.value_default = attribute_default_values.id
               where ci_attribute.value_default = cia.value_default and ci_attribute.ci_id = cia.ci_id
               group by attribute_default_values.id
           )
       when ''checkbox''
           then
           (
               select attribute_default_values.value
               from ci_attribute
               inner join attribute on ci_attribute.attribute_id = attribute.id
               inner join attribute_default_values on ci_attribute.value_default = attribute_default_values.id
               where ci_attribute.value_default = cia.value_default and ci_attribute.ci_id = cia.ci_id
               group by attribute_default_values.id
           )
       when ''radio''
           then
           (
               select attribute_default_values.value
               from ci_attribute
               inner join attribute on ci_attribute.attribute_id = attribute.id
               inner join attribute_default_values on ci_attribute.value_default = attribute_default_values.id
               where ci_attribute.value_default = cia.value_default and ci_attribute.ci_id = cia.ci_id
               group by attribute_default_values.id
           )
       when ''executeable''           then cia.value_text
       when ''selectQuery''           then cia.value_ci
       when ''ciType''                then cia.value_ci
       else ''#todo''
   end
       as value,
   cia.valid_from as modified_at
from ci ci
inner join ci_attribute cia on ci.id = cia.ci_id
inner join attribute a on a.id = cia.attribute_id
inner join attribute_type at on at.id = a.attribute_type_id
where ci.id in (:argv1:)
  and a.attribute_type_id not in ( 13 /* script */, 15 /* Query */ )
'
            WHERE sq.name = 'int_getCiAttributes'
        ");
    }

    public function down()
    {
        $this->execute("
            UPDATE `" . $this->dbName . "_tables`.`stored_query` sq SET sq.query = 'select
   cia.ci_id as ci_id,
   cia.id as ci_attribute_id,
   a.id as attribute_id,
   a.name as attribute_name,
   a.description as attribute_description,
   at.name as attribute_type,
   case at.name
       when ''input''           then cia.value_text
       when ''textarea''        then cia.value_text
       when ''textEdit''        then cia.value_text
       when ''password''        then cia.value_text
       when ''queryPersist''    then cia.value_text
       when ''ciTypePersist''   then cia.value_text
       when ''date''            then date_format(cia.value_date, ''%Y-%m-%d'')
       when ''dateTime''        then cia.value_date
       when ''zahlungsmittel''  then cia.value_text
       when ''select''
           then
           (
               select attribute_default_values.value
               from ci_attribute
               inner join attribute on ci_attribute.attribute_id = attribute.id
               inner join attribute_default_values on ci_attribute.value_default = attribute_default_values.id
               where ci_attribute.value_default = cia.value_default and ci_attribute.ci_id = cia.ci_id
               group by attribute_default_values.id
           )
       when ''checkbox''
           then
           (
               select attribute_default_values.value
               from ci_attribute
               inner join attribute on ci_attribute.attribute_id = attribute.id
               inner join attribute_default_values on ci_attribute.value_default = attribute_default_values.id
               where ci_attribute.value_default = cia.value_default and ci_attribute.ci_id = cia.ci_id
               group by attribute_default_values.id
           )
       when ''radio''
           then
           (
               select attribute_default_values.value
               from ci_attribute
               inner join attribute on ci_attribute.attribute_id = attribute.id
               inner join attribute_default_values on ci_attribute.value_default = attribute_default_values.id
               where ci_attribute.value_default = cia.value_default and ci_attribute.ci_id = cia.ci_id
               group by attribute_default_values.id
           )
       when ''executeable''           then cia.value_text
       when ''selectQuery''           then cia.value_ci
       else ''#todo''
   end
       as value,
   cia.valid_from as modified_at
from ci ci
inner join ci_attribute cia on ci.id = cia.ci_id
inner join attribute a on a.id = cia.attribute_id
inner join attribute_type at on at.id = a.attribute_type_id
where ci.id in (:argv1:)
  and a.attribute_type_id not in ( 13 /* script */, 15 /* Query */ )
'
            WHERE sq.name = 'int_getCiAttributes'
        ");
    }
}
