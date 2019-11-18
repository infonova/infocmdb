<?php

/**
 *
 *
 *
 */
class Service_Config_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 501, $themeId);
    }

    public function scanDirectoryRecursive($directorypath, $dir = false)
    {
        $directory = scandir($directorypath);

        foreach ($directory as $file) {
            if (is_file($directorypath .'/'. $file)) {
                if ($dir)
                    $link = APPLICATION_URL . 'config/edit/dir/' . $dir . '/type/' . substr($file, 0, -(strlen('.ini')));
                else
                    $link = APPLICATION_URL . 'config/edit/type/' . substr($file, 0, -(strlen('.ini')));

                $files[] = array(
                    'title' => substr($file, 0, -(strlen('.ini'))),
                    'link'  => $link,
                );
            }
            if (is_dir($directorypath .'/'. $file)) {
                if (strpos($file, ".") !== 0) {
                    $files[$file] = $this->scanDirectoryRecursive($directorypath .'/'. $file, $file);
                }
            }
        }
        return $files;
    }
}