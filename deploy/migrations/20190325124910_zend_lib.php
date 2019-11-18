<?php

use Phinx\Migration\AbstractMigration;

class ZendLib extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $libDir     = __DIR__ . '/../../library/';
        $zendLibDir = $libDir . 'Zend';

        if (is_dir($zendLibDir)) {
            echo "    ==> Found existing Zend installation in library folder --> moving to library/archive\n";

            if (!is_dir($libDir . 'archive')) {
                mkdir($libDir . 'archive');
            }

            rename($zendLibDir, $libDir . 'archive/Zend');
        }


        if (is_dir("/usr/share/pear/Zend/")) {
            echo "\e[41m" . "    ==> WARNING - Existing Zend installation found - remove: /usr/share/pear/Zend/" . "\e[0m\n";
            echo "    Try: pear uninstall zend/zend\n";
        }
    }

    public function down()
    {

    }
}