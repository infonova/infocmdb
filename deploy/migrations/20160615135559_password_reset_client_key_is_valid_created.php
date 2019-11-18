<?php

use Phinx\Migration\AbstractMigration;

class PasswordResetClientKeyIsValidCreated extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     */
    // creating columns is_valid and session_key 
    // is_valid -> true if request has not yet been handled
    // client_key -> when user opens password reset link -> write a string to client_key to prevent multiple people from completing request
    public function change()
    {
        $table = $this->table('password_reset');
        if ( !$table->hasColumn('is_valid')) {
            $table->addColumn('is_valid', 'boolean');
        }
        
        if ( !$table->hasColumn('client_key')) {
            $table->addColumn('client_key', 'string');
        }
        if ( !$table->hasColumn('created')) {
            $table->addColumn('created', 'timestamp', array('default' => 'CURRENT_TIMESTAMP') );
        }        
                
        $table->update();
    }
}
