<?php

/**
 * Class Util_Upload
 *
 */
class Util_FileUpload
{

    /**
     * Get the absolute path to the folder containing
     *
     * @return string path to executable folder
     */
    public static function getUploadPath($type)
    {
        $configFileUpload = new Util_Config('fileupload.ini', APPLICATION_ENV);

        $useDefaultPath = $configFileUpload->getValue('file.upload.path.default', true);
        $typePath       = $configFileUpload->getValue('file.upload.' . $type . '.folder', $type);

        if ($useDefaultPath) {
            $uploadsDir = $configFileUpload->getValue('file.upload.path.folder', '_uploads');
            $path       = APPLICATION_PUBLIC . $uploadsDir;
        } else {
            $path = $configFileUpload->getValue('file.upload.path.custom');
        }

        $path .='/'. $typePath;

        if (!is_dir($path)) {
            @mkdir($path, 0777);
        }

        return $path;
    }

    /**
     * Move a file located in temp folder
     *
     * @param   string $tmpFileHash filename in temp folder
     * @param   string $rawFilename new filename without path
     * @param   string $type        upload type (attachment, icon , ...)
     * @param array    $options
     * @return bool|string sanitized filename or false if moving file failed
     * @throws Zend_Config_Exception
     */
    public static function processTmpFile($tmpFileHash, $rawFilename, $type, $options = array())
    {
        $tmpFilePath       = self::getTmpUploadPath() . '/' . $tmpFileHash;
        $sanitizedFilename = self::sanitizeFilename($rawFilename);

        $destPath = self::getUploadPath($type);
        if ($type === 'attachment') {
            if (isset($options['ci_id'])) {
                $destPath .= '/' . $options['ci_id'];
            } else {
                return false;
            }
        }

        if (isset($options['prefix_date']) && $options['prefix_date'] === true) {
            $sanitizedFilename = date("YmdHms\_") . $sanitizedFilename;
        }

        if(!is_dir($destPath)) {
            mkdir($destPath);
        }

        $destPath .= '/' . $sanitizedFilename;

        $result = rename($tmpFilePath, $destPath);

        if ($result === true) {
            return $sanitizedFilename;
        }

        return false;
    }

    /**
     * Get upload path according config
     *
     * @return bool|string
     * @throws Zend_Config_Exception
     */
    public static function getTmpUploadPath()
    {
        $fileuploadConfig = new Util_Config('fileupload.ini', APPLICATION_ENV);

        $uploadPath     = $fileuploadConfig->getValue('file.upload.tmp', '../data/tmp', Util_Config::STRING);
        $fullUploadPath = realpath($uploadPath);

        if ($fullUploadPath === false) {
            $fullUploadPath = realpath(APPLICATION_PATH . '/' . $uploadPath);
        }

        if ($fullUploadPath === false) {
            throw new Zend_Config_Exception("Invalid tmp upload path (file.upload.tmp) in fileupload.ini");
        }

        return $fullUploadPath;
    }

    /**
     * Sanitize filename for safe processing
     *
     * @param $rawFilename
     * @return string sanitized filename
     */
    public static function sanitizeFilename($rawFilename)
    {
        $replaceChars = array(
            ' '  => '_',
            'Ä'  => 'Ae',
            'Ö'  => 'Oe',
            'Ü'  => 'Ue',
            'ä'  => 'ae',
            'ö'  => 'oe',
            'ü'  => 'ue',
            'ß'  => 'ss',
            '!'  => '',
            '§'  => 'PARAGRAPH',
            '$'  => 'DOLLAR',
            '€'  => 'EURO',
            '%'  => 'PERCENT',
            '='  => '',
            '#'  => '',
            '\'' => '',
            '"'  => '',
            ','  => '',
            ';'  => '',
            '<'  => '',
            '>'  => '',
        );

        $newFilename = str_replace(array_keys($replaceChars), $replaceChars, $rawFilename);
        $newFilename = filter_var($newFilename, FILTER_SANITIZE_SPECIAL_CHARS);

        return $newFilename;
    }


}