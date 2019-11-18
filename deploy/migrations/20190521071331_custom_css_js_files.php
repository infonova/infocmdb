<?php

use Phinx\Migration\AbstractMigration;

class CustomCssJsFiles extends AbstractMigration
{
    public function up()
    {
        $individualConfigLocation = __DIR__ . '/../../application/configs/individualization.ini';
        if (!is_writable($individualConfigLocation)) {
            echo "WARNING: " . $individualConfigLocation . " is not writeable\n\n";
            echo "If needed manually add custom js/css files according dist file \n";
            return;
        }

        $individualConfig = file_get_contents($individualConfigLocation);

        $customCssLocation = __DIR__ . '/../../public/css/custom.css';
        if (is_file($customCssLocation) && strpos($individualConfig, 'custom.css') === false) {
            echo "   individualization.ini   --> Adding new config line for autoloading  custom.css \n";
            $this->addConfigLine($individualConfig, "css.load.1.path = 'css/custom.css'");

        }

        $customJsLocation = __DIR__ . '/../../public/js/custom.js';
        if (is_file($customJsLocation) && strpos($individualConfig, 'custom.js') === false) {
            echo "   individualization.ini   --> Adding new config line for autoloading  custom.js \n";
            $this->addConfigLine($individualConfig, "js.load.1.path = 'js/custom.js'");
        }

        file_put_contents($individualConfigLocation, $individualConfig);
    }

    private function addConfigLine(&$config, $line)
    {
        $configLines    = preg_split('/\r\n|\r|\n/', $config);
        $newConfigLines = $config;
        $changed        = false;

        $i = 0;
        foreach ($configLines as $configLine) {
            if (preg_match('/^\[staging|testing|development/', $configLine)) {
                $newConfigLines =
                    array_merge(
                        array_slice($configLines, 0, $i, true),
                        array($line),
                        array_slice($configLines, $i, null, true)
                    );;
                $changed = true;
                break;
            }

            $i++;
        }

        $config = implode("\n", $newConfigLines);

        return $changed;
    }
}
