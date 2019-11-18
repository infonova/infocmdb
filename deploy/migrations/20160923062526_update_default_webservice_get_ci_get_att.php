<?php

use Phinx\Migration\AbstractMigration;

class UpdateDefaultWebserviceGetCiGetAtt extends AbstractMigration
{
    public function up()
    {
        $this->execute("UPDATE stored_query SET is_default = '1', is_active = '1' WHERE name in ('int_getCi', 'int_getCiAttributes')");
    }
}
