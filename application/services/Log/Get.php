<?php

/**
 *
 *
 *
 */
class Service_Log_Get extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3901, $themeId);
    }

    public function scanDirectoryRecursive($directorypath, $dir = false)
    {
        $directory = scandir($directorypath);

        foreach ($directory as $file) {
            if (is_file($directorypath .'/'. $file)) {
                if (strpos($file, ".") !== 0) {
                    $link = $directorypath .'/'. (($dir) ? $dir .'/': '') . $file;

                    $files[] = array(
                        'title' => $file,
                        'link'  => $link,
                    );
                }
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